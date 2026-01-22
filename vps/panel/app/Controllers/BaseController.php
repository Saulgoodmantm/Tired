<?php
/**
 * Base Controller
 */

declare(strict_types=1);

abstract class BaseController
{
    protected ?array $user = null;

    public function __construct()
    {
        $this->user = $_SESSION['user'] ?? null;
    }

    /**
     * Render a view with layout
     */
    protected function render(string $view, array $data = []): void
    {
        $data['user'] = $this->user;
        $data['currentPage'] = $view;
        
        extract($data);
        
        $content = VIEWS_PATH . '/' . $view . '.php';
        
        require VIEWS_PATH . '/layout.php';
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Get POST data
     */
    protected function getPost(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     */
    protected function getQuery(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Set flash message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Get and clear flash message
     */
    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
