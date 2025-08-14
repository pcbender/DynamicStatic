<?php
// Unified bootstrap: autoload, dotenv, helpers.
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use Dotenv\Dotenv;
use Weaver\WeaverConfig;

// Fallback PSR-4 autoloader for Weaver namespace if Composer's autoloader
// is unavailable or missing project classes.
spl_autoload_register(function (string $class): void {
    $prefix = 'Weaver\\';
    $len = strlen($prefix);
    if (strncmp($class, $prefix, $len) !== 0) {
        return; // Not a Weaver class
    }
    $relative = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $v !== false && $v !== null && $v !== '' ? $v : $default;
    }
}

try {
    $root = dirname(__DIR__); // Weaver/
    if (class_exists(Dotenv::class)) {
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
        $isLocalHost = stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false;
        $explicit = getenv('WEAVER_ENV_FILE') ?: null; // overrides all
        $candidateFiles = [];
        if ($explicit) {
            $candidateFiles[] = $explicit; // user override
        } elseif ($isLocalHost) {
            // Local dev preference order
            $candidateFiles[] = '.env.local';
            $candidateFiles[] = '.env'; // fallback
        } else {
            // Production preference order
            $candidateFiles[] = '.env';
            $candidateFiles[] = '.env.local'; // (ignored in prod if missing)
        }
        $loaded = false;
        foreach ($candidateFiles as $file) {
            if ($loaded) break;
            // Search Weaver/ first then repo root
            $paths = [$root, dirname($root)];
            foreach ($paths as $path) {
                $full = $path . DIRECTORY_SEPARATOR . $file;
                if (is_file($full)) {
                    Dotenv::createImmutable($path, $file)->safeLoad();
                    $loaded = true;
                    if (!defined('WEAVER_ENV_FILE_LOADED')) {
                        define('WEAVER_ENV_FILE_LOADED', $full);
                        $mode = $explicit ? 'explicit' : ($isLocalHost ? 'local' : 'production');
                        define('WEAVER_ENV_MODE', $mode);
                    }
                    break;
                }
            }
        }
        if (!$loaded && !defined('WEAVER_ENV_MODE')) {
            define('WEAVER_ENV_MODE', $explicit ? 'explicit-missing' : 'none');
        }
    }
    // Existing config (legacy OAuth) may be referenced elsewhere; keep init but swallow errors.
    try { WeaverConfig::getInstance(); } catch (Throwable $ignored) {}
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'config_error']);
    exit;
}

// Generic JSON response helpers (new model) if not already defined.
if (!function_exists('json_out')) {
    function json_out(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
