<?php
/**
 * Settings Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';

class SettingsController extends BaseController
{
    /**
     * Show settings page
     */
    public function index(): void
    {
        $data = [
            'settings' => $this->getSettings(),
            'flash' => $this->getFlash(),
        ];

        $this->render('settings', $data);
    }

    /**
     * Update settings
     */
    public function update(): void
    {
        // Currently we update .env file
        // In production, you might want a more sophisticated config management
        
        $allowedEmails = $this->getPost('allowed_emails');
        
        if ($allowedEmails !== null) {
            $this->updateEnvValue('ALLOWED_EMAILS', $allowedEmails);
        }

        $this->flash('success', 'Settings updated');
        $this->redirect('/settings');
    }

    /**
     * Get current settings
     */
    private function getSettings(): array
    {
        return [
            'app_name' => $_ENV['APP_NAME'] ?? 'VPS Panel',
            'app_url' => $_ENV['APP_URL'] ?? '',
            'allowed_emails' => $_ENV['ALLOWED_EMAILS'] ?? '',
            'sites_root' => $_ENV['SITES_ROOT'] ?? '/home/deploy/TiredProductions',
            'deploy_user' => $_ENV['DEPLOY_USER'] ?? 'deploy',
            'google_configured' => !empty($_ENV['GOOGLE_CLIENT_ID']),
        ];
    }

    /**
     * Update a value in .env file
     */
    private function updateEnvValue(string $key, string $value): bool
    {
        $envFile = CONFIG_PATH . '/.env';
        
        if (!file_exists($envFile)) {
            return false;
        }

        $content = file_get_contents($envFile);
        $pattern = "/^" . preg_quote($key, '/') . "=.*/m";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "$key=$value", $content);
        } else {
            $content .= "\n$key=$value";
        }

        return file_put_contents($envFile, $content) !== false;
    }
}
