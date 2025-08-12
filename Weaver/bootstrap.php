<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Weaver\WeaverConfig;

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
