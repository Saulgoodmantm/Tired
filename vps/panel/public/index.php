<?php
/**
 * VPS Management Panel - Entry Point
 * admin.tiredofdointm.com
 */

declare(strict_types=1);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define base paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Autoloader
spl_autoload_register(function (string $class): void {
    $paths = [
        'Controllers' => APP_PATH . '/Controllers/',
        'Services' => APP_PATH . '/Services/',
    ];
    
    foreach ($paths as $namespace => $path) {
        if (str_contains($class, $namespace)) {
            $file = $path . basename(str_replace('\\', '/', $class)) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Load environment
$envFile = CONFIG_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Session setup
ini_set('session.save_path', STORAGE_PATH . '/sessions');
session_start();

// Load router
require_once APP_PATH . '/Router.php';

// Initialize and run
$router = new Router();
$router->dispatch();
