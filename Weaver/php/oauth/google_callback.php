<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/http.php';

use Weaver\Service\GoogleOAuthService;
use Weaver\Service\JwtService;
use Weaver\WeaverConfig;

$config = WeaverConfig::getInstance();
$oauth = new GoogleOAuthService($config);
$jwt = new JwtService($config);

/**
 * Google redirects here:
 *   GET /oauth/google_callback.php?code=...&state=<gstate>
 * We exchange code at Google, read ID token (email/sub), then mint a short-lived
 * authorization code (JWT) for ChatGPT and redirect to the GPT callback with ?code=...
 */

if (!isset($_GET['code'], $_GET['state'])) {
    bad_request('Missing code/state');
}

$gstate = json_decode(base64_decode(strtr($_GET['state'], '-_', '+/')), true);
if (!$gstate || empty($gstate['redirect_uri']) || empty($gstate['client_id'])) {
    bad_request('Invalid state');
}
if ($gstate['client_id'] !== $config->weaverOauthClientId) {
    unauthorized('client mismatch');
}

try {
    $tok = $oauth->exchangeCode($_GET['code']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    server_error('Failed to exchange code');
}
$id_token = $tok['id_token'] ?? null;
if (!$id_token) {
    server_error('No id_token from Google');
}

try {
    $user = $oauth->validateIdToken($id_token);
} catch (Throwable $e) {
    error_log($e->getMessage());
    unauthorized($e->getMessage());
}

try {
    $auth_code = $jwt->sign([
        'typ' => 'auth_code',
        'sub' => $user->sub,
        'email' => $user->email,
        'scope' => $gstate['scope'],
        'client_id' => $gstate['client_id'],
    ], 60);
} catch (Throwable $e) {
    error_log($e->getMessage());
    server_error('Failed to sign token');
}

$redir = $gstate['redirect_uri'];
$qs = http_build_query(['code' => $auth_code, 'state' => $gstate['orig_state'] ?? '']);
header('Location: ' . $redir . (strpos($redir, '?') === false ? '?' : '&') . $qs, true, 302);
exit;
