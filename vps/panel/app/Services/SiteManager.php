<?php
/**
 * Site Manager Service
 * Manages websites in TiredProductions folder
 */

declare(strict_types=1);

class SiteManager
{
    private string $sitesRoot;
    private string $configFile;
    private array $sites = [];

    public function __construct()
    {
        $this->sitesRoot = $_ENV['SITES_ROOT'] ?? '/home/deploy/TiredProductions';
        $this->configFile = CONFIG_PATH . '/sites.json';
        $this->loadSites();
    }

    /**
     * Load sites from config
     */
    private function loadSites(): void
    {
        if (file_exists($this->configFile)) {
            $data = json_decode(file_get_contents($this->configFile), true);
            $this->sites = $data['sites'] ?? [];
        }
    }

    /**
     * Save sites to config
     */
    private function saveSites(): bool
    {
        $data = [
            'sites' => $this->sites,
            'defaults' => [
                'php_version' => '8.2',
                'ssl_enabled' => true,
                'auto_deploy' => false,
            ],
        ];
        
        return file_put_contents($this->configFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Get all sites
     */
    public function getAll(): array
    {
        return $this->sites;
    }

    /**
     * Get site by ID
     */
    public function getById(string $id): ?array
    {
        foreach ($this->sites as $site) {
            if ($site['id'] === $id) {
                return $site;
            }
        }
        return null;
    }

    /**
     * Get site by domain
     */
    public function getByDomain(string $domain): ?array
    {
        foreach ($this->sites as $site) {
            if ($site['domain'] === $domain) {
                return $site;
            }
        }
        return null;
    }

    /**
     * Add a new site
     */
    public function add(array $data): array
    {
        // Validate required fields
        $required = ['name', 'domain', 'repo'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: $field"];
            }
        }

        // Generate ID from domain
        $id = preg_replace('/[^a-z0-9]/', '', strtolower($data['domain']));
        
        // Check if already exists
        if ($this->getById($id)) {
            return ['success' => false, 'error' => 'Site already exists'];
        }

        $site = [
            'id' => $id,
            'name' => $data['name'],
            'domain' => $data['domain'],
            'path' => $this->sitesRoot . '/' . $data['domain'],
            'repo' => $data['repo'],
            'branch' => $data['branch'] ?? 'main',
            'php_version' => $data['php_version'] ?? '8.2',
            'nginx_config' => '/etc/nginx/sites-available/' . $data['domain'],
            'ssl_enabled' => $data['ssl_enabled'] ?? true,
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        $this->sites[] = $site;
        
        if (!$this->saveSites()) {
            return ['success' => false, 'error' => 'Failed to save configuration'];
        }

        return ['success' => true, 'site' => $site];
    }

    /**
     * Update a site
     */
    public function update(string $id, array $data): array
    {
        $index = null;
        foreach ($this->sites as $i => $site) {
            if ($site['id'] === $id) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return ['success' => false, 'error' => 'Site not found'];
        }

        // Update allowed fields
        $allowedFields = ['name', 'repo', 'branch', 'php_version', 'ssl_enabled'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $this->sites[$index][$field] = $data[$field];
            }
        }
        
        $this->sites[$index]['updated_at'] = date('c');

        if (!$this->saveSites()) {
            return ['success' => false, 'error' => 'Failed to save configuration'];
        }

        return ['success' => true, 'site' => $this->sites[$index]];
    }

    /**
     * Delete a site
     */
    public function delete(string $id): array
    {
        $this->sites = array_values(array_filter(
            $this->sites,
            fn($site) => $site['id'] !== $id
        ));

        if (!$this->saveSites()) {
            return ['success' => false, 'error' => 'Failed to save configuration'];
        }

        return ['success' => true];
    }

    /**
     * Deploy a site
     */
    public function deploy(string $id): array
    {
        $site = $this->getById($id);
        if (!$site) {
            return ['success' => false, 'error' => 'Site not found'];
        }

        $ssh = new SSHService();
        $path = $site['path'];
        $branch = $site['branch'];

        // Clone or pull
        if (!is_dir($path)) {
            // Clone repository
            $result = $ssh->execute("git clone {$site['repo']} {$path}");
            if (!$result['success']) {
                return ['success' => false, 'error' => 'Failed to clone repository', 'output' => $result['output']];
            }
        }

        // Pull latest
        $result = $ssh->execute("cd {$path} && git fetch --all && git checkout {$branch} && git reset --hard origin/{$branch}");
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to pull latest', 'output' => $result['output']];
        }

        // Run migrations if available
        $migrationFile = $path . '/website/migrate.php';
        if (file_exists($migrationFile)) {
            $ssh->execute("php {$migrationFile}");
        }

        // Clear caches
        $cacheDir = $path . '/website/storage/cache';
        if (is_dir($cacheDir)) {
            $ssh->execute("rm -rf {$cacheDir}/*");
        }

        // Restart PHP-FPM
        $ssh->execute("sudo systemctl restart php{$site['php_version']}-fpm");

        // Update timestamp
        $this->update($id, []);

        return ['success' => true, 'output' => $result['output']];
    }

    /**
     * Get site logs
     */
    public function getLogs(string $id, string $type = 'access', int $lines = 100): array
    {
        $site = $this->getById($id);
        if (!$site) {
            return ['success' => false, 'error' => 'Site not found'];
        }

        $domain = $site['domain'];
        $logFile = $type === 'error' 
            ? "/var/log/nginx/{$domain}.error.log"
            : "/var/log/nginx/{$domain}.access.log";

        $ssh = new SSHService();
        $result = $ssh->execute("sudo tail -{$lines} {$logFile}");

        return [
            'success' => $result['success'],
            'logs' => $result['output'],
            'type' => $type,
        ];
    }

    /**
     * Get site status
     */
    public function getStatus(string $id): array
    {
        $site = $this->getById($id);
        if (!$site) {
            return ['status' => 'unknown'];
        }

        $ssh = new SSHService();
        
        // Check if directory exists
        $pathExists = is_dir($site['path']);
        
        // Check git status
        $gitResult = $ssh->execute("cd {$site['path']} && git rev-parse --short HEAD 2>/dev/null");
        $currentCommit = trim($gitResult['output'] ?? '');
        
        // Check nginx config
        $nginxResult = $ssh->execute("nginx -t 2>&1");
        $nginxValid = $nginxResult['success'];

        return [
            'status' => $pathExists ? 'active' : 'not_deployed',
            'path_exists' => $pathExists,
            'current_commit' => $currentCommit,
            'nginx_valid' => $nginxValid,
            'last_updated' => $site['updated_at'] ?? null,
        ];
    }

    /**
     * Setup nginx config for a site
     */
    public function setupNginx(string $id): array
    {
        $site = $this->getById($id);
        if (!$site) {
            return ['success' => false, 'error' => 'Site not found'];
        }

        // Generate nginx config
        $config = $this->generateNginxConfig($site);
        $configPath = "/etc/nginx/sites-available/{$site['domain']}";

        $ssh = new SSHService();
        
        // Write config
        $tempFile = "/tmp/nginx_{$site['id']}.conf";
        file_put_contents($tempFile, $config);
        $result = $ssh->execute("sudo cp {$tempFile} {$configPath}");
        unlink($tempFile);

        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to write nginx config'];
        }

        // Enable site
        $ssh->execute("sudo ln -sf {$configPath} /etc/nginx/sites-enabled/");

        // Test config
        $testResult = $ssh->execute("sudo nginx -t");
        if (!$testResult['success']) {
            return ['success' => false, 'error' => 'Invalid nginx config', 'output' => $testResult['output']];
        }

        // Reload nginx
        $ssh->execute("sudo systemctl reload nginx");

        return ['success' => true];
    }

    /**
     * Generate nginx config for a site
     */
    private function generateNginxConfig(array $site): string
    {
        $domain = $site['domain'];
        $path = $site['path'];
        $phpVersion = $site['php_version'];

        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};

    root {$path}/website/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml image/svg+xml;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php{$phpVersion}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    location ~ /\. {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /(config|storage|migrations|app)/ {
        deny all;
    }

    error_page 404 /index.php;
    error_page 500 502 503 504 /index.php;

    access_log /var/log/nginx/{$domain}.access.log;
    error_log /var/log/nginx/{$domain}.error.log;
}
NGINX;
    }
}
