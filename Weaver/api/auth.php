<?php
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
}
require_once $autoload;

// Load environment variables from the repository root (.env)
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function unauthorized(string $msg = 'Unauthorized'): never {
    json_out(['error' => $msg], 401);
}

function forbidden(string $msg = 'Forbidden'): never {
    json_out(['error' => $msg], 403);
}

function bad_request(string $msg = 'Bad request'): never {
    json_out(['error' => $msg], 400);
}

function has_scope(array $claims, string $need): bool {
    $sc = preg_split('/\s+/', $claims['scope'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
    return in_array($need, $sc, true) || in_array('admin', $sc, true) || in_array('jobs:admin', $sc, true);
}

function require_scope(array $claims, string $needed): void {
    if (!has_scope($claims, $needed)) {
        forbidden('Insufficient scope');
    }
}

function require_bearer(): array {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
        unauthorized('Missing bearer');
    }
    $jwt = $m[1];
    try {
        $claims = JWT::decode($jwt, new Key(getenv('WEAVER_JWT_PRIVATE_KEY'), 'RS256'));
    } catch (Throwable $e) {
        unauthorized('Bad token');
    }
    $claims = (array)$claims;
    if (($claims['iss'] ?? '') !== getenv('WEAVER_ISSUER')) {
        unauthorized('iss mismatch');
    }
    if (($claims['aud'] ?? '') !== 'weaver-api') {
        unauthorized('aud mismatch');
    }
    $typ = $claims['typ'] ?? '';
    if (!in_array($typ, ['access', 'refresh', ''], true)) {
        unauthorized('typ mismatch');
    }
    return $claims;
}
?>
