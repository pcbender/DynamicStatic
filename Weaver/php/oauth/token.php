<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/http.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Weaver\Service\JwtService;
use Weaver\WeaverConfig;

$config = WeaverConfig::getInstance();
$jwtService = new JwtService($config);

/**
 * ChatGPT calls:
 *   POST /oauth/token
 *   grant_type=authorization_code&code=...&redirect_uri=...
 * with client authentication (the Action UI uses POST body; Basic also works).
 */

require_post();

$grant_type    = $_POST['grant_type']    ?? '';
$code_jwt      = $_POST['code']          ?? '';
$client_id     = $_POST['client_id']     ?? '';
$client_secret = $_POST['client_secret'] ?? '';

if ($client_id !== $config->weaverOauthClientId || $client_secret !== $config->weaverOauthClientSecret) {
    unauthorized('Bad client credentials');
}

if ($grant_type !== 'authorization_code') {
    bad_request('Unsupported grant_type');
}

try {
    $claims = JWT::decode($code_jwt, new Key($config->weaverJwtPrivateKey, 'RS256'));
    $claims = (array)$claims;
    if (($claims['typ'] ?? '') !== 'auth_code') {
        throw new Exception('not auth_code');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    unauthorized('Invalid code');
}

try {
    $access = $jwtService->sign([
        'typ' => 'access',
        'sub' => $claims['sub'],
        'email' => $claims['email'] ?? null,
        'scope' => $claims['scope'] ?? 'openid',
    ], $config->weaverJwtTtl);

    $refresh = $jwtService->sign([
        'typ' => 'refresh',
        'sub' => $claims['sub'],
        'scope' => $claims['scope'] ?? 'openid',
    ], $config->weaverRefreshTtl);
} catch (Throwable $e) {
    error_log($e->getMessage());
    server_error('Failed to sign token');
}

json_out([
    'access_token' => $access,
    'token_type'   => 'Bearer',
    'expires_in'   => $config->weaverJwtTtl,
    'scope'        => $claims['scope'] ?? 'openid',
    'refresh_token'=> $refresh,
]);
