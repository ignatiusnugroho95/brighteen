<?php

namespace App\Http;

class Router
{
    protected static $routes = [];

    public static function get($uri, $action)
    {
        self::addRoute('GET', $uri, $action);
    }

    public static function post($uri, $action)
    {
        self::addRoute('POST', $uri, $action);
    }

    protected static function addRoute($method, $uri, $action)
    {
        $uri = trim($uri, '/');
        self::$routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action
        ];
    }

    public static function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Compute basePath to strip (e.g. /brighteen or /brighteen/public)
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '/' && $scriptName !== '.') {
            if (strpos($requestUri, $scriptName) === 0) {
                $requestUri = substr($requestUri, strlen($scriptName));
            }
        }

        // Also check if URL contains /brighteen
        if (strpos($requestUri, '/brighteen') === 0) {
            $requestUri = substr($requestUri, strlen('/brighteen'));
        }

        // Normalize multiple consecutive slashes to single slash (e.g. // -> /)
        $requestUri = preg_replace('#/+#', '/', $requestUri);

        $requestUri = trim($requestUri, '/');

        foreach (self::$routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convert route uri to regex pattern
            // e.g. "articles/{slug}" -> "#^articles/([^/]+)$#"
            // "articles/{id}/edit" -> "#^articles/([^/]+)/edit$#"
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['uri']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove full match

                $action = $route['action'];

                if (is_array($action) && is_string($action[0])) {
                    $controllerClass = $action[0];
                    $method = $action[1];
                    $controller = new $controllerClass();
                    $result = call_user_func_array([$controller, $method], $matches);
                    if (is_string($result)) {
                        echo $result;
                    }
                    return $result;
                }

                if (is_callable($action)) {
                    $result = call_user_func_array($action, $matches);
                    if (is_string($result)) {
                        echo $result;
                    }
                    return $result;
                }
            }
        }

        // No matching route
        http_response_code(404);
        echo view('errors.404', ['message' => 'Page not found.']);
        exit;
    }
}
