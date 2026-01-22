<?php
/**
 * Simple Router for VPS Panel
 */

declare(strict_types=1);

class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function __construct()
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Auth routes
        $this->routes['GET']['/login'] = ['AuthController', 'showLogin'];
        $this->routes['GET']['/auth/google'] = ['AuthController', 'redirectToGoogle'];
        $this->routes['GET']['/auth/google/callback'] = ['AuthController', 'handleGoogleCallback'];
        $this->routes['GET']['/logout'] = ['AuthController', 'logout'];

        // Protected routes
        $this->routes['GET']['/'] = ['DashboardController', 'index'];
        $this->routes['GET']['/dashboard'] = ['DashboardController', 'index'];
        
        // Sites management
        $this->routes['GET']['/sites'] = ['SitesController', 'index'];
        $this->routes['GET']['/sites/add'] = ['SitesController', 'showAdd'];
        $this->routes['POST']['/sites/add'] = ['SitesController', 'store'];
        $this->routes['GET']['/sites/edit'] = ['SitesController', 'showEdit'];
        $this->routes['POST']['/sites/edit'] = ['SitesController', 'update'];
        $this->routes['POST']['/sites/deploy'] = ['SitesController', 'deploy'];
        $this->routes['GET']['/sites/logs'] = ['SitesController', 'logs'];
        $this->routes['POST']['/sites/delete'] = ['SitesController', 'delete'];

        // Commands
        $this->routes['GET']['/commands'] = ['CommandsController', 'index'];
        $this->routes['POST']['/commands/execute'] = ['CommandsController', 'execute'];
        $this->routes['POST']['/commands/save'] = ['CommandsController', 'save'];
        $this->routes['POST']['/commands/delete'] = ['CommandsController', 'delete'];

        // Settings
        $this->routes['GET']['/settings'] = ['SettingsController', 'index'];
        $this->routes['POST']['/settings'] = ['SettingsController', 'update'];

        // API endpoints for AJAX
        $this->routes['GET']['/api/stats'] = ['DashboardController', 'getStats'];
        $this->routes['GET']['/api/services'] = ['DashboardController', 'getServices'];
        $this->routes['POST']['/api/service/restart'] = ['DashboardController', 'restartService'];
        $this->routes['GET']['/api/logs'] = ['SitesController', 'getLogContent'];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        // Check if route exists
        if (!isset($this->routes[$method][$uri])) {
            $this->notFound();
            return;
        }

        [$controllerName, $action] = $this->routes[$method][$uri];
        
        // Auth check (skip for auth routes)
        $publicRoutes = ['/login', '/auth/google', '/auth/google/callback'];
        if (!in_array($uri, $publicRoutes)) {
            if (!$this->isAuthenticated()) {
                header('Location: /login');
                exit;
            }
        }

        // Load and execute controller
        $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            $this->serverError("Controller not found: $controllerName");
            return;
        }

        require_once $controllerFile;
        $controller = new $controllerName();
        
        if (!method_exists($controller, $action)) {
            $this->serverError("Action not found: $action");
            return;
        }

        $controller->$action();
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['email']);
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
    }

    private function serverError(string $message): void
    {
        http_response_code(500);
        if ($_ENV['APP_DEBUG'] ?? false) {
            echo "<h1>500 - Server Error</h1><p>$message</p>";
        } else {
            echo '<h1>500 - Server Error</h1>';
        }
    }
}
