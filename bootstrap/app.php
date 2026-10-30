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
    /**
     * TEMPORARY FEATURE FLAG: Per-Account Demo Role Switcher
     * Change DEMO_ROLE_SWITCHER_ENABLED to false (or set DEMO_ROLE_SWITCHER=false in .env)
     * whenever you want to permanently disable role switching so every account strictly uses its own role.
     */
    const DEMO_ROLE_SWITCHER_ENABLED = true;

    private static $instance = null;
    public $db = null;
    public $config = [];
    public $user = null;
    public $originalUser = null;

    public function __construct()
    {
        self::$instance = $this;
        $this->loadEnv(__DIR__ . '/../.env');
        $this->loadPhpEnv(__DIR__ . '/../config/env.php');
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

    public function env($key, $default = null)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        $val = function_exists('getenv') ? @getenv($key) : false;
        return ($val !== false && $val !== '') ? $val : $default;
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
                $this->config[$key] = $val;
                $_ENV[$key] = $val;
                if (function_exists('putenv')) {
                    @putenv("$key=$val");
                }
            }
        }
    }

    private function loadPhpEnv($path)
    {
        if (!file_exists($path)) {
            return;
        }
        $vars = include $path;
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                $this->config[$key] = $val;
                $_ENV[$key] = $val;
                if (function_exists('putenv')) {
                    @putenv("$key=$val");
                }
            }
        }
    }

    private function initDatabase()
    {
        $host = $this->env('DB_HOST', '127.0.0.1');
        $port = $this->env('DB_PORT', '3306');
        $dbname = $this->env('DB_DATABASE', 'brighteen');
        $username = $this->env('DB_USERNAME', 'root');
        $password = $this->env('DB_PASSWORD', '');

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

    public function isRoleSwitcherEnabled()
    {
        if (!self::DEMO_ROLE_SWITCHER_ENABLED) {
            return false;
        }
        $flag = $this->env('DEMO_ROLE_SWITCHER', 'true');
        return !in_array(strtolower((string)$flag), ['false', '0', 'off', 'no'], true);
    }

    public static function getAllowedSwitchRoles($originalRole)
    {
        if ($originalRole === 'management') {
            return ['management', 'editor', 'journalist', 'contributor', 'reader_registered', 'guest'];
        }
        if ($originalRole === 'editor') {
            return ['editor', 'journalist', 'contributor', 'reader_registered', 'guest'];
        }
        return [];
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
            $fetchedUser = $stmt->fetch() ?: null;
            if ($fetchedUser && !empty($fetchedUser['avatar']) && function_exists('normalize_media_url')) {
                $fetchedUser['avatar'] = normalize_media_url($fetchedUser['avatar']);
            }
            $this->originalUser = $fetchedUser;
            $this->user = $fetchedUser;

            // Apply temporary per-account Demo Role Switcher override if enabled & permitted
            if ($this->originalUser && $this->isRoleSwitcherEnabled()) {
                $origRole = $this->originalUser['role_name'] ?? '';
                $allowed = self::getAllowedSwitchRoles($origRole);
                $overrideRole = $_SESSION['demo_override_role'] ?? null;

                if ($overrideRole && in_array($overrideRole, $allowed, true)) {
                    if ($overrideRole === 'guest') {
                        // Simulate Guest mode while keeping $this->originalUser in session to switch back
                        $this->user = null;
                    } elseif ($overrideRole !== $origRole) {
                        $roleStmt = $this->db->prepare("SELECT id, name, display_name FROM roles WHERE name = ? LIMIT 1");
                        $roleStmt->execute([$overrideRole]);
                        $roleRow = $roleStmt->fetch();
                        if ($roleRow) {
                            $this->user['role_id'] = $roleRow['id'];
                            $this->user['role_name'] = $roleRow['name'];
                            $this->user['role_display_name'] = $roleRow['display_name'];
                        } else {
                            $this->user['role_name'] = $overrideRole;
                        }
                    }
                }
            }
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
            public function originalUser() {
                return app()->originalUser;
            }
            public function originalRole() {
                return app()->originalUser ? app()->originalUser['role_name'] : null;
            }
            public function canUseRoleSwitcher() {
                if (!app()->isRoleSwitcherEnabled() || !app()->originalUser) {
                    return false;
                }
                return !empty(Application::getAllowedSwitchRoles($this->originalRole()));
            }
            public function availableSwitchRoles() {
                if (!$this->canUseRoleSwitcher()) {
                    return [];
                }
                return Application::getAllowedSwitchRoles($this->originalRole());
            }
            public function isRoleOverridden() {
                if (!$this->canUseRoleSwitcher()) {
                    return false;
                }
                $active = $this->check() ? $this->role() : 'guest';
                return $active !== $this->originalRole();
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

if (!function_exists('env')) {
    function env($key, $default = null) {
        return app()->env($key, $default);
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        $envKey = strtoupper(str_replace('.', '_', $key));
        return app()->env($envKey, $default);
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return url($path);
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $envUrl = rtrim((string)app()->env('APP_URL', ''), '/');

        if (!empty($_SERVER['HTTP_HOST'])) {
            $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if (substr($script, -7) === '/public') {
                $script = substr($script, 0, -7);
            }
            $subPath = ($script === '/' || $script === '.') ? '' : rtrim($script, '/');

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
                || (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false);
            $scheme = $isHttps ? 'https://' : 'http://';
            $baseUrl = $scheme . $_SERVER['HTTP_HOST'] . $subPath;
        } else {
            $subPath = parse_url($envUrl, PHP_URL_PATH) ?: '';
            $baseUrl = $envUrl ?: $subPath;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('normalize_media_url')) {
    function normalize_media_url($mediaUrl) {
        if (empty($mediaUrl) || !is_string($mediaUrl)) return $mediaUrl;
        if (preg_match('#^https?://localhost(?::\d+)?/(?:brighteen/)?(uploads/.+)$#i', $mediaUrl, $m)) {
            return url($m[1]);
        }
        return $mediaUrl;
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
        $list = is_array($data) ? array_values(array_filter($data)) : (json_decode($data, true) ?: []);
        if (!is_array($list)) return [];
        return array_values(array_map('normalize_media_url', array_filter($list)));
    }
}

require_once __DIR__ . '/../app/View/BladeRenderer.php';
require_once __DIR__ . '/../app/Http/Router.php';

// Initialize Application instance
return Application::getInstance();
}

