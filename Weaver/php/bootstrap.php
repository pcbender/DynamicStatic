<?php
// Unified bootstrap: class loader, dotenv, helpers.
require_once __DIR__ . '/classloader.php';

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
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    $isLocalHost = stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false;
    $explicit = getenv('WEAVER_ENV_FILE') ?: null; // overrides all

    // Decide candidate list (two-mode model: .env.local for local, .env for production)
    $candidateFiles = [];
    if ($explicit) {
        $candidateFiles[] = $explicit;
    } elseif ($isLocalHost) {
        $candidateFiles[] = '.env.local';
        $candidateFiles[] = '.env';
    } else {
        $candidateFiles[] = '.env';
        $candidateFiles[] = '.env.local';
    }

    // Fallback parser if Dotenv not available
    if (!function_exists('weaver_simple_env_load')) {
        function weaver_simple_env_load(string $fullPath): void {
            $lines = @file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!$lines) return;
            foreach ($lines as $line) {
                if ($line === '' || $line[0] === '#') continue;
                if (!str_contains($line, '=')) continue;
                [$k,$v] = array_map('trim', explode('=', $line, 2));
                if ($k === '') continue;
                // Strip surrounding quotes
                if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
                    $v = substr($v, 1, -1);
                }
                if (!array_key_exists($k, $_ENV)) { $_ENV[$k] = $v; }
                if (!array_key_exists($k, $_SERVER)) { $_SERVER[$k] = $v; }
            }
        }
    }

    $loaded = false; $loadedFile = null;
    foreach ($candidateFiles as $file) {
        if ($loaded) break;
        $paths = [$root, dirname($root)]; // Weaver/, repo root
        foreach ($paths as $path) {
            $full = $path . DIRECTORY_SEPARATOR . $file;
            if (is_file($full)) {
                if (class_exists(Dotenv::class)) {
                    try { Dotenv::createImmutable($path, $file)->safeLoad(); } catch (Throwable $e) { /* fall back */ }
                } else {
                    weaver_simple_env_load($full);
                }
                $loaded = true; $loadedFile = $full; break;
            }
        }
    }
    if (!defined('WEAVER_ENV_FILE_LOADED') && $loadedFile) {
        define('WEAVER_ENV_FILE_LOADED', $loadedFile);
        if ($explicit) {
            define('WEAVER_ENV_MODE', 'explicit');
        } else {
            // Determine mode from filename (.env.local => local, .env => production)
            $base = basename($loadedFile);
            $mode = ($base === '.env.local') ? 'local' : 'production';
            define('WEAVER_ENV_MODE', $mode);
        }
    }
    if (!defined('WEAVER_ENV_MODE')) {
        define('WEAVER_ENV_MODE', $explicit ? 'explicit-missing' : 'none');
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

// Helper: report configuration completeness (used by health endpoint)
if (!function_exists('weaver_config_status')) {
    function weaver_config_status(): array {
        $requiredKeys = [
            'WEAVER_API_KEY',
            'WEAVER_SESSION_JWT_SECRET',
            'GITHUB_APP_ID',
            'GITHUB_APP_CLIENT_ID',
            'GITHUB_APP_PRIVATE_KEY',
            'GITHUB_WEBHOOK_SECRET'
        ];
        $presence = [];
        foreach ($requiredKeys as $k) { $presence[$k] = env($k) ? true : false; }
        return [
            'presence' => $presence,
            'all_set' => !in_array(false, $presence, true)
        ];
    }
}
