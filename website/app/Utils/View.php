<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - View Renderer
 * =============================================================================
 * Simple PHP template rendering
 * =============================================================================
 */

namespace App\Utils;

class View
{
    private static string $viewsPath = '';
    private static array $shared = [];

    /**
     * Set views directory path
     */
    public static function setPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/');
    }

    /**
     * Share data with all views
     */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Render a view
     *
     * @param string $view View path (e.g., 'pages/home', 'dashboard/index')
     * @param array $data Data to pass to view
     * @param string|null $layout Layout to wrap view (null for no layout)
     * @return string Rendered HTML
     */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): string
    {
        $viewPath = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }

        // Merge shared data
        $data = array_merge(self::$shared, $data);

        // Render view content
        ob_start();
        extract($data);
        include $viewPath;
        $content = ob_get_clean();

        // Wrap in layout if specified
        if ($layout) {
            $layoutPath = self::$viewsPath . '/layouts/' . $layout . '.php';
            if (file_exists($layoutPath)) {
                ob_start();
                extract($data);
                include $layoutPath;
                return ob_get_clean();
            }
        }

        return $content;
    }

    /**
     * Render and output a view
     */
    public static function display(string $view, array $data = [], ?string $layout = 'main'): void
    {
        echo self::render($view, $data, $layout);
    }

    /**
     * Render a component/partial
     */
    public static function component(string $name, array $data = []): string
    {
        return self::render('components/' . $name, $data, null);
    }

    /**
     * Include a component inline
     */
    public static function include(string $name, array $data = []): void
    {
        echo self::component($name, $data);
    }

    /**
     * Escape HTML
     */
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format date
     */
    public static function date(string $date, string $format = 'M j, Y'): string
    {
        return date($format, strtotime($date));
    }

    /**
     * Format currency
     */
    public static function money(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Check if user has role (for views)
     */
    public static function can(int $minLevel): bool
    {
        return Auth::hasRole($minLevel);
    }

    /**
     * Generate CSRF token field
     */
    public static function csrf(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf_token'] . '">';
    }

    /**
     * Get asset URL with cache busting
     */
    public static function asset(string $path): string
    {
        $fullPath = self::$viewsPath . '/../public/' . ltrim($path, '/');
        $version = file_exists($fullPath) ? filemtime($fullPath) : time();
        return '/' . ltrim($path, '/') . '?v=' . $version;
    }
}
