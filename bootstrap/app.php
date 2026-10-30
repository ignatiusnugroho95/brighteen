<?php

namespace App {

use PDO;
use PDOException;

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

class Application
{
    private static $instance = null;
    public $db = null;
    public $config = [];
    public $user = null;

    public function __construct()
    {
        self::$instance = $this;
        $this->loadEnv(__DIR__ . '/../.env');
        $this->initDatabase();
        $this->initSession();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnv($path)
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }

    private function initDatabase()
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_DATABASE') ?: 'brighteen';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
        }
    }

    private function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Authenticate user from session if logged in
        if (!empty($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name, r.display_name as role_display_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $this->user = $stmt->fetch() ?: null;
        }
    }
}
}

namespace {

use App\Application;

// Global helper functions matching Laravel API
if (!function_exists('app')) {
    function app() {
        return Application::getInstance();
    }
}

if (!function_exists('db')) {
    function db() {
        return app()->db;
    }
}

if (!function_exists('auth')) {
    function auth() {
        return new class {
            public function user() {
                return app()->user;
            }
            public function check() {
                return app()->user !== null;
            }
            public function id() {
                return app()->user ? app()->user['id'] : null;
            }
            public function role() {
                return app()->user ? app()->user['role_name'] : 'reader_guest';
            }
            public function isManagement() {
                return $this->role() === 'management';
            }
            public function isEditor() {
                return in_array($this->role(), ['editor', 'management']);
            }
            public function isJournalist() {
                return in_array($this->role(), ['journalist', 'editor', 'management']);
            }
            public function isContributor() {
                return in_array($this->role(), ['contributor', 'journalist', 'editor', 'management']);
            }
            public function isReaderRegistered() {
                return $this->role() === 'reader_registered';
            }
            public function canCreateArticle() {
                return in_array($this->role(), ['contributor', 'journalist', 'editor', 'management']);
            }
            public function canPublishDirectly() {
                return in_array($this->role(), ['editor', 'management']);
            }
        };
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        $envKey = strtoupper(str_replace('.', '_', $key));
        $val = getenv($envKey);
        return $val !== false ? $val : $default;
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return url($path);
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $envUrl = rtrim(getenv('APP_URL') ?: '', '/');
        $subPath = parse_url($envUrl, PHP_URL_PATH) ?: '';
        if (empty($subPath)) {
            $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $subPath = rtrim($script, '/\\');
        }

        if (!empty($_SERVER['HTTP_HOST'])) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            $scheme = $isHttps ? 'https://' : 'http://';
            $baseUrl = $scheme . $_SERVER['HTTP_HOST'] . $subPath;
        } else {
            $baseUrl = $envUrl ?: $subPath;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route($name, $params = []) {
        // Simple named route resolver
        $routes = [
            'home' => '',
            'articles.index' => '',
            'articles.show' => 'articles/' . ($params['slug'] ?? ''),
            'articles.create' => 'articles/create',
            'articles.edit' => 'articles/' . ($params['id'] ?? '') . '/edit',
            'dashboard.editorial' => 'dashboard/editorial',
            'dashboard.management' => 'dashboard/management',
            'bookmarks' => 'bookmarks',
            'login' => 'login',
            'register' => 'register',
            'logout' => 'logout',
        ];

        $target = $routes[$name] ?? $name;
        return url($target);
    }
}

if (!function_exists('view')) {
    function view($viewName, $data = []) {
        $renderer = new \App\View\BladeRenderer();
        return $renderer->render($viewName, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect($to) {
        if (!preg_match('#^https?://#', $to)) {
            $to = url($to);
        }
        header("Location: $to");
        exit;
    }
}

if (!function_exists('response')) {
    function response() {
        return new class {
            public function json($data, $status = 200) {
                http_response_code($status);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data);
                exit;
            }
        };
    }
}

if (!function_exists('reading_time')) {
    function reading_time($content) {
        $clean = strip_tags((string)$content);
        $wordCount = str_word_count($clean);
        // Average reading speed: 200 words per minute
        $minutes = (int)ceil($wordCount / 200);
        return max(1, $minutes) . ' min read';
    }
}

if (!function_exists('youtube_embed_url')) {
    function youtube_embed_url($url) {
        if (empty($url)) return '';
        $pattern = '#(?:https?://)?(?:www\.)?(?:youtube\.com/(?:watch\?.*?v=|embed/|v/|shorts/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i';
        if (preg_match($pattern, $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/' . $matches[1];
        }
        return $url;
    }
}

if (!function_exists('parse_carousel_images')) {
    function parse_carousel_images($data) {
        if (empty($data)) return [];
        if (is_array($data)) return array_values(array_filter($data));
        $decoded = json_decode($data, true);
        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }
}

require_once __DIR__ . '/../app/View/BladeRenderer.php';
require_once __DIR__ . '/../app/Http/Router.php';

// Initialize Application instance
return Application::getInstance();
}

