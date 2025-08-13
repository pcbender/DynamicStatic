<?php
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

try {
    $root = dirname(__DIR__);
    $envFile = getenv('WEAVER_ENV_FILE') ?: '.env';
    Dotenv::createImmutable($root, $envFile)->safeLoad();
    WeaverConfig::getInstance();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'config_error',
        'error_description' => $e->getMessage()
    ]);
    exit;
}
