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
    $root = dirname(__DIR__);
    $envFile = getenv('WEAVER_ENV_FILE') ?: '.env';
    if (class_exists(Dotenv::class)) {
        Dotenv::createImmutable($root, $envFile)->safeLoad();
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
