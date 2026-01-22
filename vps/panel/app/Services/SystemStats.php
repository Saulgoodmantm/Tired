<?php
/**
 * System Statistics Service
 * Provides VPS system information
 */

declare(strict_types=1);

class SystemStats
{
    /**
     * Get all system stats
     */
    public function getAll(): array
    {
        return [
            'uptime' => $this->getUptime(),
            'load' => $this->getLoadAverage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'cpu' => $this->getCpuInfo(),
        ];
    }

    /**
     * Get system uptime
     */
    public function getUptime(): array
    {
        $uptime = @file_get_contents('/proc/uptime');
        if (!$uptime) {
            return ['seconds' => 0, 'formatted' => 'Unknown'];
        }

        $seconds = (int) explode(' ', $uptime)[0];
        
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $formatted = '';
        if ($days > 0) $formatted .= "{$days}d ";
        if ($hours > 0) $formatted .= "{$hours}h ";
        $formatted .= "{$minutes}m";

        return [
            'seconds' => $seconds,
            'formatted' => trim($formatted),
        ];
    }

    /**
     * Get load average
     */
    public function getLoadAverage(): array
    {
        $load = sys_getloadavg();
        if (!$load) {
            return ['1min' => 0, '5min' => 0, '15min' => 0];
        }

        return [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2),
        ];
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'percent' => 0,
            ];
        }

        $data = [];
        foreach (explode("\n", $meminfo) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $data[$matches[1]] = (int) $matches[2] * 1024; // Convert KB to bytes
            }
        }

        $total = $data['MemTotal'] ?? 0;
        $free = ($data['MemFree'] ?? 0) + ($data['Buffers'] ?? 0) + ($data['Cached'] ?? 0);
        $used = $total - $free;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            'total_formatted' => $this->formatBytes($total),
            'used_formatted' => $this->formatBytes($used),
            'free_formatted' => $this->formatBytes($free),
        ];
    }

    /**
     * Get disk usage
     */
    public function getDiskUsage(): array
    {
        $total = @disk_total_space('/');
        $free = @disk_free_space('/');
        
        if ($total === false || $free === false) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'percent' => 0,
            ];
        }

        $used = $total - $free;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            'total_formatted' => $this->formatBytes($total),
            'used_formatted' => $this->formatBytes($used),
            'free_formatted' => $this->formatBytes($free),
        ];
    }

    /**
     * Get CPU info
     */
    public function getCpuInfo(): array
    {
        $cpuinfo = @file_get_contents('/proc/cpuinfo');
        if (!$cpuinfo) {
            return ['cores' => 1, 'model' => 'Unknown'];
        }

        $cores = substr_count($cpuinfo, 'processor');
        
        $model = 'Unknown';
        if (preg_match('/model name\s*:\s*(.+)/i', $cpuinfo, $matches)) {
            $model = trim($matches[1]);
        }

        return [
            'cores' => $cores,
            'model' => $model,
        ];
    }

    /**
     * Get service status
     */
    public function getServiceStatus(string $service): array
    {
        $output = [];
        $exitCode = 0;
        exec("systemctl is-active " . escapeshellarg($service) . " 2>&1", $output, $exitCode);
        
        $status = trim($output[0] ?? 'unknown');
        
        return [
            'name' => $service,
            'status' => $status,
            'running' => $status === 'active',
        ];
    }

    /**
     * Get all important services status
     */
    public function getAllServicesStatus(): array
    {
        $services = ['nginx', 'php8.2-fpm', 'fail2ban', 'ufw'];
        
        return array_map(fn($s) => $this->getServiceStatus($s), $services);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
