<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Gate System ("67")
 * =============================================================================
 * Simple password gate for friends/family during soft launch
 * =============================================================================
 */

namespace App\Utils;

class Gate
{
    private static array $config = [];

    /**
     * Initialize gate with config
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Check if user has passed the gate
     */
    public static function hasPassed(): bool
    {
        $cookieName = self::$config['cookie_name'] ?? 'tiredofdointm_gate';

        if (!isset($_COOKIE[$cookieName])) {
            return false;
        }

        // Verify the signed cookie
        return self::verifyCookie($_COOKIE[$cookieName]);
    }

    /**
     * Verify password and set cookie if correct
     * Returns: true if password correct, false otherwise
     */
    public static function verify(string $password): bool
    {
        $correctPassword = self::$config['password'] ?? '67';

        // Constant-time comparison
        if (!hash_equals($correctPassword, $password)) {
            return false;
        }

        // Set signed cookie
        self::setCookie();
        return true;
    }

    /**
     * Create signed cookie value
     */
    private static function createSignedValue(): string
    {
        $secret = self::$config['secret'] ?? 'default-secret';
        $expires = time() + (86400 * (self::$config['cookie_days'] ?? 30));
        $data = "passed|{$expires}";
        $signature = hash_hmac('sha256', $data, $secret);

        return base64_encode("{$data}|{$signature}");
    }

    /**
     * Verify signed cookie
     */
    private static function verifyCookie(string $cookie): bool
    {
        $secret = self::$config['secret'] ?? 'default-secret';

        $decoded = base64_decode($cookie, true);
        if (!$decoded) return false;

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) return false;

        [$status, $expires, $signature] = $parts;

        // Check expiry
        if ((int) $expires < time()) {
            return false;
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', "{$status}|{$expires}", $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        return $status === 'passed';
    }

    /**
     * Set the gate cookie
     */
    private static function setCookie(): void
    {
        $cookieName = self::$config['cookie_name'] ?? 'tiredofdointm_gate';
        $days = self::$config['cookie_days'] ?? 30;

        setcookie($cookieName, self::createSignedValue(), [
            'expires' => time() + (86400 * $days),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    /**
     * Clear the gate cookie (for testing/logout)
     */
    public static function clear(): void
    {
        $cookieName = self::$config['cookie_name'] ?? 'tiredofdointm_gate';
        setcookie($cookieName, '', time() - 3600, '/');
    }
}
