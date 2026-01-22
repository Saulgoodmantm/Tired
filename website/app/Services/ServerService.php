<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Server Management Service
 * =============================================================================
 * Handles deployments, domain management, and server operations
 * =============================================================================
 */

namespace App\Services;

class ServerService
{
    private string $appDir;
    private string $nginxDir;
    private string $logsDir;
    private string $deployUser;

    public function __construct()
    {
        $this->appDir = '/home/deploy/tiredprod';
        $this->nginxDir = '/etc/nginx/sites-available';
        $this->logsDir = '/var/log/nginx';
        $this->deployUser = 'deploy';
    }

    /**
     * Deploy from a specific branch
     */
    public function deploy(string $branch = 'main'): array
    {
        $output = [];
        $returnCode = 0;

        // Sanitize branch name
        $branch = preg_replace('/[^a-zA-Z0-9\-_\/.]/', '', $branch);

        $script = "{$this->appDir}/vps/deploy_pull.sh";

        if (!file_exists($script)) {
            return [
                'success' => false,
                'error' => 'Deploy script not found',
                'output' => '',
            ];
        }

        // Run deploy script
        $command = "cd {$this->appDir} && bash {$script} " . escapeshellarg($branch) . " 2>&1";
        exec($command, $output, $returnCode);

        // Log deployment
        $this->logDeployment($branch, $returnCode === 0, implode("\n", $output));

        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'branch' => $branch,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get available branches from remote
     */
    public function getBranches(): array
    {
        $output = [];
        exec("cd {$this->appDir} && git fetch --all --prune 2>&1 && git branch -r 2>&1", $output);

        $branches = [];
        foreach ($output as $line) {
            $line = trim($line);
            if (preg_match('/origin\/(.+)$/', $line, $matches)) {
                $branch = trim($matches[1]);
                if ($branch !== 'HEAD' && strpos($branch, '->') === false) {
                    $branches[] = $branch;
                }
            }
        }

        return array_unique($branches);
    }

    /**
     * Get current branch
     */
    public function getCurrentBranch(): string
    {
        $output = [];
        exec("cd {$this->appDir} && git rev-parse --abbrev-ref HEAD 2>&1", $output);
        return trim($output[0] ?? 'unknown');
    }

    /**
     * Get last deploy info
     */
    public function getLastDeploy(): ?array
    {
        $logFile = "{$this->appDir}/website/storage/logs/deploy.log";

        if (!file_exists($logFile)) {
            return null;
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return null;
        }

        $lastLine = end($lines);
        $data = json_decode($lastLine, true);

        return $data;
    }

    /**
     * Log deployment
     */
    private function logDeployment(string $branch, bool $success, string $output): void
    {
        $logFile = "{$this->appDir}/website/storage/logs/deploy.log";
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'branch' => $branch,
            'success' => $success,
            'commit' => $this->getCurrentCommit(),
            'output' => substr($output, 0, 5000), // Limit output size
        ]) . "\n";

        file_put_contents($logFile, $entry, FILE_APPEND);
    }

    /**
     * Get current commit hash
     */
    public function getCurrentCommit(): string
    {
        $output = [];
        exec("cd {$this->appDir} && git rev-parse --short HEAD 2>&1", $output);
        return trim($output[0] ?? 'unknown');
    }

    /**
     * Get server status
     */
    public function getServerStatus(): array
    {
        $status = [
            'status' => 'Online',
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'uptime' => $this->getUptime(),
            'services' => [
                'nginx' => $this->isServiceRunning('nginx'),
                'php-fpm' => $this->isServiceRunning('php8.2-fpm'),
            ],
            'php_version' => PHP_VERSION,
        ];

        // Check database connection
        try {
            \App\Utils\Database::query("SELECT 1");
            $status['services']['postgresql'] = true;
        } catch (\Exception $e) {
            $status['services']['postgresql'] = false;
        }

        return $status;
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A';
        }

        $output = [];
        exec("free -m | awk 'NR==2{printf \"%dMB / %dMB (%.1f%%)\", \$3, \$2, \$3/\$2*100}'", $output);
        return $output[0] ?? 'N/A';
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A';
        }

        $output = [];
        exec("df -h / | awk 'NR==2{printf \"%s / %s (%s)\", \$3, \$2, \$5}'", $output);
        return $output[0] ?? 'N/A';
    }

    /**
     * Get server uptime
     */
    private function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A';
        }

        $output = [];
        exec("uptime -p", $output);
        return str_replace('up ', '', $output[0] ?? 'N/A');
    }

    /**
     * Check if a service is running
     */
    private function isServiceRunning(string $service): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return true;
        }

        $output = [];
        exec("systemctl is-active " . escapeshellarg($service) . " 2>&1", $output);
        return trim($output[0] ?? '') === 'active';
    }

    /**
     * Get configured domains
     */
    public function getDomains(): array
    {
        $domains = [];

        if (!is_dir($this->nginxDir)) {
            return $domains;
        }

        $files = glob("{$this->nginxDir}/*");
        foreach ($files as $file) {
            $name = basename($file);
            if ($name === 'default') continue;

            $content = file_get_contents($file);
            $hasSSL = strpos($content, 'ssl_certificate') !== false;

            $domains[] = [
                'name' => $name,
                'ssl' => $hasSSL,
                'enabled' => file_exists("/etc/nginx/sites-enabled/{$name}"),
            ];
        }

        return $domains;
    }

    /**
     * Add a new domain
     */
    public function addDomain(string $domain): array
    {
        // Validate domain
        $domain = strtolower(trim($domain));
        if (!preg_match('/^[a-z0-9][a-z0-9\-]*[a-z0-9]?\.[a-z]{2,}$/', $domain) &&
            !preg_match('/^[a-z0-9]+\.[a-z]{2,}$/', $domain)) {
            return ['success' => false, 'error' => 'Invalid domain format'];
        }

        $configFile = "{$this->nginxDir}/{$domain}";

        if (file_exists($configFile)) {
            return ['success' => false, 'error' => 'Domain already exists'];
        }

        // Create nginx config
        $config = $this->generateNginxConfig($domain);
        file_put_contents($configFile, $config);

        // Enable site
        symlink($configFile, "/etc/nginx/sites-enabled/{$domain}");

        // Test and reload nginx
        $output = [];
        exec("nginx -t 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            // Rollback
            unlink("/etc/nginx/sites-enabled/{$domain}");
            unlink($configFile);
            return ['success' => false, 'error' => 'Invalid nginx config: ' . implode("\n", $output)];
        }

        exec("systemctl reload nginx 2>&1", $output);

        return ['success' => true, 'domain' => $domain];
    }

    /**
     * Remove a domain
     */
    public function removeDomain(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $configFile = "{$this->nginxDir}/{$domain}";
        $enabledFile = "/etc/nginx/sites-enabled/{$domain}";

        if (!file_exists($configFile)) {
            return ['success' => false, 'error' => 'Domain not found'];
        }

        // Remove symlink and config
        if (file_exists($enabledFile)) {
            unlink($enabledFile);
        }
        unlink($configFile);

        // Reload nginx
        exec("systemctl reload nginx 2>&1");

        return ['success' => true];
    }

    /**
     * Enable SSL for a domain
     */
    public function enableSSL(string $domain): array
    {
        $domain = strtolower(trim($domain));

        // Run certbot
        $output = [];
        $command = "certbot --nginx -d " . escapeshellarg($domain) . " --non-interactive --agree-tos -m admin@{$domain} 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'error' => 'Certbot failed',
                'output' => implode("\n", $output),
            ];
        }

        return ['success' => true, 'domain' => $domain];
    }

    /**
     * Get logs
     */
    public function getLogs(string $type, int $lines = 100): string
    {
        $logFile = match ($type) {
            'deploy' => "{$this->appDir}/website/storage/logs/deploy.log",
            'error' => "{$this->logsDir}/error.log",
            'access' => "{$this->logsDir}/access.log",
            'php' => '/var/log/php8.2-fpm.log',
            default => '',
        };

        if (!$logFile || !file_exists($logFile)) {
            return "Log file not found: {$type}";
        }

        // Get last N lines
        $output = [];
        exec("tail -n " . (int)$lines . " " . escapeshellarg($logFile) . " 2>&1", $output);

        return implode("\n", $output);
    }

    /**
     * Generate nginx config for a domain
     */
    private function generateNginxConfig(string $domain): string
    {
        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};

    root {$this->appDir}/website/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;

    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\. { deny all; }
    location ~ /\.env { deny all; }
    location ~ /(config|storage|migrations|app)/ { deny all; }

    access_log /var/log/nginx/{$domain}.access.log;
    error_log /var/log/nginx/{$domain}.error.log;
}
NGINX;
    }

    /**
     * Handle GitHub webhook
     */
    public function handleGitHubWebhook(string $payload, string $signature, string $secret): array
    {
        // Verify signature
        $expectedSig = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSig, $signature)) {
            return ['success' => false, 'error' => 'Invalid signature'];
        }

        $data = json_decode($payload, true);

        if (!$data) {
            return ['success' => false, 'error' => 'Invalid JSON'];
        }

        // Get branch from ref
        $ref = $data['ref'] ?? '';
        $branch = str_replace('refs/heads/', '', $ref);

        if (!$branch) {
            return ['success' => false, 'error' => 'No branch in payload'];
        }

        // Only deploy main branch by default (can be configured)
        $autoDeploy = ['main', 'master', 'production'];

        if (!in_array($branch, $autoDeploy)) {
            return [
                'success' => true,
                'message' => "Branch {$branch} not in auto-deploy list",
                'deployed' => false,
            ];
        }

        // Deploy
        $result = $this->deploy($branch);

        return array_merge($result, ['deployed' => true]);
    }
}
