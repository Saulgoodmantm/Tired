<?php
/**
 * SSH/Command Execution Service
 * Executes commands locally on the VPS (panel runs on same server)
 */

declare(strict_types=1);

class SSHService
{
    private string $logFile;
    private array $allowedCommands = [];
    private array $blockedPatterns = [
        'rm -rf /',
        'mkfs',
        '> /dev/',
        'dd if=',
        ':(){:|:&};:',
        'chmod -R 777 /',
        'chown -R',
    ];

    public function __construct()
    {
        $this->logFile = STORAGE_PATH . '/logs/commands.log';
        $this->loadAllowedCommands();
    }

    /**
     * Execute a command and return the output
     */
    public function execute(string $command, ?string $workingDir = null): array
    {
        // Security check
        if (!$this->isCommandSafe($command)) {
            $this->log('BLOCKED', $command, 'Command contains blocked pattern');
            return [
                'success' => false,
                'output' => 'Command blocked for security reasons',
                'exit_code' => -1,
            ];
        }

        // Build the full command
        $fullCommand = $command;
        if ($workingDir) {
            $fullCommand = "cd " . escapeshellarg($workingDir) . " && $command";
        }

        // Execute
        $output = [];
        $exitCode = 0;
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($fullCommand, $descriptors, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            
            $exitCode = proc_close($process);
            
            $output = $stdout;
            if ($stderr) {
                $output .= ($output ? "\n" : '') . $stderr;
            }
        } else {
            $this->log('ERROR', $command, 'Failed to execute command');
            return [
                'success' => false,
                'output' => 'Failed to execute command',
                'exit_code' => -1,
            ];
        }

        $success = $exitCode === 0;
        $this->log($success ? 'SUCCESS' : 'FAILED', $command, "Exit code: $exitCode");

        return [
            'success' => $success,
            'output' => $output,
            'exit_code' => $exitCode,
        ];
    }

    /**
     * Execute a predefined command by ID
     */
    public function executeById(string $commandId, array $variables = []): array
    {
        $command = $this->getCommandById($commandId);
        if (!$command) {
            return [
                'success' => false,
                'output' => 'Command not found',
                'exit_code' => -1,
            ];
        }

        // Replace variables in command
        $cmdString = $command['command'];
        foreach ($variables as $key => $value) {
            $cmdString = str_replace('{' . $key . '}', escapeshellarg($value), $cmdString);
        }

        return $this->execute($cmdString);
    }

    /**
     * Check if a command is safe to execute
     */
    private function isCommandSafe(string $command): bool
    {
        $lowerCommand = strtolower($command);
        
        foreach ($this->blockedPatterns as $pattern) {
            if (str_contains($lowerCommand, strtolower($pattern))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Load allowed commands from config
     */
    private function loadAllowedCommands(): void
    {
        $file = CONFIG_PATH . '/commands.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $this->allowedCommands = $data ?? [];
        }
    }

    /**
     * Get command by ID
     */
    public function getCommandById(string $id): ?array
    {
        foreach ($this->allowedCommands['categories'] ?? [] as $category) {
            foreach ($category['commands'] ?? [] as $command) {
                if ($command['id'] === $id) {
                    return $command;
                }
            }
        }

        foreach ($this->allowedCommands['custom'] ?? [] as $command) {
            if ($command['id'] === $id) {
                return $command;
            }
        }

        return null;
    }

    /**
     * Get all commands organized by category
     */
    public function getAllCommands(): array
    {
        return $this->allowedCommands;
    }

    /**
     * Save a custom command
     */
    public function saveCustomCommand(array $command): bool
    {
        $file = CONFIG_PATH . '/commands.json';
        
        if (!isset($command['id'])) {
            $command['id'] = 'custom-' . bin2hex(random_bytes(4));
        }
        
        $command['created_at'] = date('c');
        
        $this->allowedCommands['custom'][] = $command;
        
        return file_put_contents($file, json_encode($this->allowedCommands, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Delete a custom command
     */
    public function deleteCustomCommand(string $id): bool
    {
        $file = CONFIG_PATH . '/commands.json';
        
        $this->allowedCommands['custom'] = array_filter(
            $this->allowedCommands['custom'] ?? [],
            fn($cmd) => $cmd['id'] !== $id
        );
        
        $this->allowedCommands['custom'] = array_values($this->allowedCommands['custom']);
        
        return file_put_contents($file, json_encode($this->allowedCommands, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Log command execution
     */
    private function log(string $status, string $command, string $details = ''): void
    {
        $user = $_SESSION['user']['email'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[$timestamp] [$status] [$user] $command" . ($details ? " - $details" : "") . "\n";
        
        @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get command execution history
     */
    public function getHistory(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $content = file_get_contents($this->logFile);
        $allLines = array_filter(explode("\n", $content));
        
        return array_slice($allLines, -$lines);
    }
}
