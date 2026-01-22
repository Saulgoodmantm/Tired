<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Application Configuration
 * =============================================================================
 * Central configuration loader - loads .env and provides config access
 * =============================================================================
 */

// Load environment variables from .env file
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            throw new Exception("Environment file not found: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) continue;

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
                    $value = $matches[1];
                }

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Helper to get env value with default
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Load .env file
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// =============================================================================
// CONFIGURATION ARRAY
// =============================================================================
return [
    // -------------------------------------------------------------------------
    // Application
    // -------------------------------------------------------------------------
    'app' => [
        'name' => env('APP_NAME', 'TiredOfDoinTM'),
        'url' => env('APP_URL', 'http://localhost'),
        'env' => env('APP_ENV', 'development'),
        'debug' => env('APP_DEBUG', 'true') === 'true',
    ],

    // -------------------------------------------------------------------------
    // Gate System
    // -------------------------------------------------------------------------
    'gate' => [
        'password' => env('GATE_PASSWORD', '67'),
        'secret' => env('GATE_SECRET', 'change-this-secret'),
        'cookie_name' => 'tiredofdointm_gate',
        'cookie_days' => 30,
    ],

    // -------------------------------------------------------------------------
    // Showcase/Slideshow
    // -------------------------------------------------------------------------
    'showcase' => [
        'interval' => (int) env('SHOWCASE_INTERVAL', 6000),
        'fast_swap' => (int) env('SHOWCASE_FAST_SWAP', 500),
    ],

    // -------------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------------
    'database' => [
        'connection' => env('DB_CONNECTION', 'pgsql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '5432'),
        'name' => env('DB_NAME', 'tiredofdointm'),
        'user' => env('DB_USER', 'postgres'),
        'pass' => env('DB_PASS', ''),
        'ssl' => env('DB_SSL', 'require'),
    ],

    // -------------------------------------------------------------------------
    // Cloudflare R2
    // -------------------------------------------------------------------------
    'r2' => [
        'account_id' => env('R2_ACCOUNT_ID'),
        'access_key' => env('R2_ACCESS_KEY'),
        'secret_key' => env('R2_SECRET_KEY'),
        'bucket' => env('R2_BUCKET', 'tiredproduction'),
        'endpoint' => env('R2_ENDPOINT'),
        'token' => env('R2_TOKEN'),
        'public_url' => env('R2_PUBLIC_URL'),
    ],

    // -------------------------------------------------------------------------
    // Google APIs
    // -------------------------------------------------------------------------
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),
        'api_key' => env('GOOGLE_API_KEY'),
        'redirect_uri' => env('APP_URL') . '/auth/google/callback',
    ],

    // -------------------------------------------------------------------------
    // Stripe
    // -------------------------------------------------------------------------
    'stripe' => [
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // -------------------------------------------------------------------------
    // Email
    // -------------------------------------------------------------------------
    'mail' => [
        'host' => env('SMTP_HOST', 'smtp.gmail.com'),
        'user' => env('SMTP_USER'),
        'pass' => env('SMTP_PASS'),
        'port' => (int) env('SMTP_PORT', 587),
        'from_name' => env('SMTP_FROM_NAME', 'TiredOfDoinTM'),
        'from_email' => env('SMTP_USER'),
    ],

    // -------------------------------------------------------------------------
    // Admin
    // -------------------------------------------------------------------------
    'admin' => [
        'emails' => array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', '')))),
    ],

    // -------------------------------------------------------------------------
    // Security
    // -------------------------------------------------------------------------
    'security' => [
        'encryption_key' => env('ENCRYPTION_KEY'),
    ],

    // -------------------------------------------------------------------------
    // Paths
    // -------------------------------------------------------------------------
    'paths' => [
        'root' => dirname(__DIR__),
        'public' => dirname(__DIR__) . '/public',
        'views' => dirname(__DIR__) . '/views',
        'storage' => dirname(__DIR__) . '/storage',
        'uploads' => dirname(__DIR__) . '/public/uploads',
    ],

    // -------------------------------------------------------------------------
    // Social Links
    // -------------------------------------------------------------------------
    'social' => [
        'instagram' => 'https://www.instagram.com/tiredofdointm/',
        'instagram2' => 'https://www.instagram.com/tiredflics/',
        'tiktok' => 'https://www.tiktok.com/@tiredofdointm',
    ],

    // -------------------------------------------------------------------------
    // Profile
    // -------------------------------------------------------------------------
    'profile' => [
        'image' => 'https://instagram.fcps3-1.fna.fbcdn.net/v/t51.2885-19/612483021_17897242272370559_4688585427133632962_n.jpg',
    ],
];
