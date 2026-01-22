<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Simple Router
 * =============================================================================
 * Clean URL routing with middleware support
 * =============================================================================
 */

namespace App\Utils;

class Router
{
    private static array $routes = [];
    private static array $middleware = [];
    private static string $basePath = '';

    /**
     * Set base path (for subdirectory installations)
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    /**
     * Register a GET route
     */
    public static function get(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public static function post(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register both GET and POST
     */
    public static function any(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
        self::addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route
     */
    public static function put(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public static function delete(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Register a PATCH route
     */
    public static function patch(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Add route to registry
     */
    private static function addRoute(string $method, string $path, callable $handler, array $middleware): void
    {
        // Convert path params like {id} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . self::$basePath . $pattern . '$#';

        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Register global middleware
     */
    public static function middleware(string $name, callable $handler): void
    {
        self::$middleware[$name] = $handler;
    }

    /**
     * Dispatch the current request
     */
    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middlewareName) {
                    if (isset(self::$middleware[$middlewareName])) {
                        $result = (self::$middleware[$middlewareName])();
                        if ($result === false) {
                            return; // Middleware blocked request
                        }
                    }
                }

                // Call handler
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // No route matched - 404
        self::notFound();
    }

    /**
     * Handle 404
     */
    private static function notFound(): void
    {
        http_response_code(404);
        if (file_exists(__DIR__ . '/../../views/errors/404.php')) {
            include __DIR__ . '/../../views/errors/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Return JSON response
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Get current URL path
     */
    public static function currentPath(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * Check if current path matches
     */
    public static function isActive(string $path): bool
    {
        $current = self::currentPath();
        return $current === $path || strpos($current, $path . '/') === 0;
    }
}
