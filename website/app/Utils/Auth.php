<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Authentication Utilities
 * =============================================================================
 * Session management, user authentication, and role checking
 * =============================================================================
 */

namespace App\Utils;

class Auth
{
    // Role levels (higher = more access)
    public const ROLE_GUEST = 0;
    public const ROLE_REGISTERED = 10;
    public const ROLE_CLIENT = 20;
    public const ROLE_MODEL = 25;
    public const ROLE_PHOTOGRAPHER = 30;
    public const ROLE_STAFF = 50;
    public const ROLE_MANAGER = 80;
    public const ROLE_ADMIN = 100;

    private static ?array $user = null;
    private static bool $initialized = false;

    /**
     * Initialize auth system (call once at app start)
     */
    public static function init(): void
    {
        if (self::$initialized) return;

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 86400 * 30, // 30 days
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Load user from session if exists
        if (isset($_SESSION['user_id'])) {
            self::loadUser($_SESSION['user_id']);
        }

        self::$initialized = true;
    }

    /**
     * Load user from database
     */
    private static function loadUser(int $userId): void
    {
        $user = Database::queryOne(
            "SELECT u.*, r.name as role_name, r.level as role_level
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.id = ? AND u.is_active = true",
            [$userId]
        );

        if ($user) {
            self::$user = $user;
        } else {
            // Invalid session - clear it
            self::logout();
        }
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return self::$user !== null;
    }

    /**
     * Get current user
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return self::$user['id'] ?? null;
    }

    /**
     * Get current user's role level
     */
    public static function roleLevel(): int
    {
        return (int) (self::$user['role_level'] ?? self::ROLE_GUEST);
    }

    /**
     * Get current user's role name
     */
    public static function roleName(): string
    {
        return self::$user['role_name'] ?? 'guest';
    }

    /**
     * Check if user has minimum role level
     */
    public static function hasRole(int $minLevel): bool
    {
        return self::roleLevel() >= $minLevel;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is staff or higher
     */
    public static function isStaff(): bool
    {
        return self::hasRole(self::ROLE_STAFF);
    }

    /**
     * Log in user by ID
     */
    public static function login(int $userId, bool $remember = false): bool
    {
        self::loadUser($userId);

        if (self::$user) {
            $_SESSION['user_id'] = $userId;

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Update last login
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);

            // Create session record
            self::createSessionRecord($userId, $remember);

            return true;
        }

        return false;
    }

    /**
     * Create session record in database
     */
    private static function createSessionRecord(int $userId, bool $remember): void
    {
        $tokenHash = hash('sha256', session_id());
        $fingerprint = self::getDeviceFingerprint();
        $expiresAt = date('Y-m-d H:i:s', strtotime($remember ? '+90 days' : '+30 days'));

        Database::insert('sessions', [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'device_fingerprint' => $fingerprint,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remember_me' => $remember ? 'true' : 'false',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Generate device fingerprint
     */
    private static function getDeviceFingerprint(): string
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        ];
        return hash('sha256', implode('|', $data));
    }

    /**
     * Log out current user
     */
    public static function logout(): void
    {
        // Delete session record
        if (isset($_SESSION['user_id'])) {
            Database::delete('sessions', 'token_hash = ?', [hash('sha256', session_id())]);
        }

        // Clear user
        self::$user = null;

        // Destroy session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Generate OTP code (6 alphanumeric, no ambiguous chars)
     */
    public static function generateOTP(): string
    {
        // Exclude ambiguous: 0, O, I, 1, L
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $otp = '';
        for ($i = 0; $i < 6; $i++) {
            $otp .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $otp;
    }

    /**
     * Store OTP for email
     */
    public static function storeOTP(string $email, string $otp): void
    {
        // Delete any existing OTPs for this email
        Database::delete('otp_codes', 'email = ?', [$email]);

        // Store new OTP (hashed)
        Database::insert('otp_codes', [
            'email' => strtolower($email),
            'code_hash' => password_hash($otp, PASSWORD_DEFAULT),
            'attempts' => 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Verify OTP
     * Returns: 'valid', 'expired', 'invalid', 'max_attempts'
     */
    public static function verifyOTP(string $email, string $otp): string
    {
        $record = Database::queryOne(
            "SELECT * FROM otp_codes WHERE email = ? ORDER BY created_at DESC LIMIT 1",
            [strtolower($email)]
        );

        if (!$record) {
            return 'invalid';
        }

        // Check max attempts
        if ($record['attempts'] >= 5) {
            return 'max_attempts';
        }

        // Check expiry
        if (strtotime($record['expires_at']) < time()) {
            return 'expired';
        }

        // Increment attempts
        Database::update('otp_codes', ['attempts' => $record['attempts'] + 1], 'id = ?', [$record['id']]);

        // Verify code (constant time comparison via password_verify)
        if (password_verify($otp, $record['code_hash'])) {
            // Delete used OTP
            Database::delete('otp_codes', 'id = ?', [$record['id']]);
            return 'valid';
        }

        return 'invalid';
    }

    /**
     * Find or create user by email
     * Returns: ['user' => array|null, 'is_new' => bool]
     */
    public static function findOrCreateUser(string $email, ?string $username = null, ?string $googleId = null): array
    {
        $email = strtolower(trim($email));

        // Try to find existing user
        $user = Database::queryOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );

        if ($user) {
            // Update Google ID if provided and not set
            if ($googleId && empty($user['google_id'])) {
                Database::update('users', ['google_id' => $googleId], 'id = ?', [$user['id']]);
            }
            return ['user' => $user, 'is_new' => false];
        }

        // Create new user
        $config = require __DIR__ . '/../../config/app.php';
        $adminEmails = $config['admin']['emails'] ?? [];

        // Determine role - admin if email is in admin list
        $roleId = in_array($email, array_map('strtolower', $adminEmails))
            ? self::getAdminRoleId()
            : self::getRegisteredRoleId();

        $userId = Database::insert('users', [
            'username' => $username ?? self::generateUsername($email),
            'email' => $email,
            'role_id' => $roleId,
            'google_id' => $googleId,
            'email_verified' => $googleId ? 'true' : 'false',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'is_active' => 'true',
        ]);

        $user = Database::queryOne("SELECT * FROM users WHERE id = ?", [$userId]);

        return ['user' => $user, 'is_new' => true];
    }

    /**
     * Generate username from email
     */
    private static function generateUsername(string $email): string
    {
        $base = explode('@', $email)[0];
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);
        $base = substr($base, 0, 20);

        // Check if taken
        $existing = Database::queryValue(
            "SELECT COUNT(*) FROM users WHERE username LIKE ?",
            [$base . '%']
        );

        if ($existing > 0) {
            $base .= random_int(100, 999);
        }

        return $base;
    }

    /**
     * Get admin role ID
     */
    private static function getAdminRoleId(): int
    {
        $role = Database::queryOne("SELECT id FROM roles WHERE name = 'admin'");
        return $role ? (int) $role['id'] : 1;
    }

    /**
     * Get registered role ID
     */
    private static function getRegisteredRoleId(): int
    {
        $role = Database::queryOne("SELECT id FROM roles WHERE name = 'registered'");
        return $role ? (int) $role['id'] : 2;
    }
}
