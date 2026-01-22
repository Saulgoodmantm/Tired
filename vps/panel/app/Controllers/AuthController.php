<?php
/**
 * Authentication Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once APP_PATH . '/Services/GoogleAuthService.php';

class AuthController extends BaseController
{
    private GoogleAuthService $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new GoogleAuthService();
    }

    /**
     * Show login page
     */
    public function showLogin(): void
    {
        // Already logged in?
        if ($this->user) {
            $this->redirect('/dashboard');
            return;
        }

        $data = [
            'configured' => $this->auth->isConfigured(),
            'flash' => $this->getFlash(),
        ];

        $this->render('login', $data);
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(): void
    {
        if (!$this->auth->isConfigured()) {
            $this->flash('error', 'Google OAuth is not configured');
            $this->redirect('/login');
            return;
        }

        $url = $this->auth->getAuthUrl();
        $this->redirect($url);
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(): void
    {
        $code = $this->getQuery('code');
        $state = $this->getQuery('state');
        $error = $this->getQuery('error');

        if ($error) {
            $this->flash('error', 'Google login was cancelled or failed');
            $this->redirect('/login');
            return;
        }

        if (!$code || !$state) {
            $this->flash('error', 'Invalid callback parameters');
            $this->redirect('/login');
            return;
        }

        $user = $this->auth->handleCallback($code, $state);

        if (!$user) {
            $this->flash('error', 'Authentication failed or email not allowed');
            $this->redirect('/login');
            return;
        }

        // Set session
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();

        // Log login
        $this->logLogin($user['email']);

        $this->redirect('/dashboard');
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/login');
    }

    /**
     * Log login event
     */
    private function logLogin(string $email): void
    {
        $logFile = STORAGE_PATH . '/logs/auth.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logLine = "[$timestamp] LOGIN: $email from $ip\n";
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
