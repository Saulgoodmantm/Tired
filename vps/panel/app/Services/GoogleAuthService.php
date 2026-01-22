<?php
/**
 * Google OAuth Authentication Service
 */

declare(strict_types=1);

class GoogleAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private array $allowedEmails;

    public function __construct()
    {
        $this->clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
        
        $emails = $_ENV['ALLOWED_EMAILS'] ?? '';
        $this->allowedEmails = array_map('trim', explode(',', $emails));
    }

    /**
     * Get the Google OAuth authorization URL
     */
    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
            'state' => $this->generateState(),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens and get user info
     */
    public function handleCallback(string $code, string $state): ?array
    {
        // Verify state
        if (!$this->verifyState($state)) {
            return null;
        }

        // Exchange code for token
        $tokenData = $this->getAccessToken($code);
        if (!$tokenData || !isset($tokenData['access_token'])) {
            return null;
        }

        // Get user info
        $userInfo = $this->getUserInfo($tokenData['access_token']);
        if (!$userInfo || !isset($userInfo['email'])) {
            return null;
        }

        // Check if email is allowed
        if (!$this->isEmailAllowed($userInfo['email'])) {
            return null;
        }

        return [
            'email' => $userInfo['email'],
            'name' => $userInfo['name'] ?? $userInfo['email'],
            'picture' => $userInfo['picture'] ?? null,
        ];
    }

    /**
     * Exchange authorization code for access token
     */
    private function getAccessToken(string $code): ?array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get user info from Google
     */
    private function getUserInfo(string $accessToken): ?array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Check if email is in allowed list
     */
    public function isEmailAllowed(string $email): bool
    {
        return in_array(strtolower($email), array_map('strtolower', $this->allowedEmails));
    }

    /**
     * Generate CSRF state token
     */
    private function generateState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    /**
     * Verify CSRF state token
     */
    private function verifyState(string $state): bool
    {
        $sessionState = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state']);
        return hash_equals($sessionState, $state);
    }

    /**
     * Check if OAuth is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }
}
