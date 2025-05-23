<?php
namespace App\Lib;

class Router {
    protected array $routes = [];
    protected string $page_path = '';

    public function __construct(string $page_path, array $routes) {
        $this->page_path = rtrim($page_path, DS) . DS;
        $this->routes = $routes;
    }

    public function dispatch(string $page_key) {
        global $page_title, $content_file;

        if (array_key_exists($page_key, $this->routes)) {
            $route_config = $this->routes[$page_key];

            if (!empty($route_config['guest_only']) && isset($_SESSION['user_id'])) {
                header("Location: /index.php?page=account_dashboard");
                exit();
            }

            if (!empty($route_config['auth_required']) && !isset($_SESSION['user_id'])) {
                $_SESSION['login_errors'] = ['Please log in to access this page.'];
                header("Location: /index.php?page=home");
                exit();
            }

            $page_title = (isset($route_config['title']) ? ' | ' . $route_config['title'] : '');
            $content_file_path = $this->page_path . $route_config['file'];

            if (file_exists($content_file_path)) {
                $content_file = $content_file_path;
                return true;
            } else {
                error_log("Router error: Page file '{$route_config['file']}' not found for page key '{$page_key}'.");
                $page_title = 'Page Not Found';
                $content_file = $this->page_path . '404.php';
                if (!headers_sent()) {
                    header("HTTP/1.0 404 Not Found");
                }
                return false;
            }
        }

        $page_title = 'Page Not Found';
        $content_file = $this->page_path . '404.php';
        if (!headers_sent()) {
            header("HTTP/1.0 404 Not Found");
        }
        return false;
    }
}