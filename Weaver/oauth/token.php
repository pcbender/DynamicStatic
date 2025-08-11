<?php
require_once __DIR__.'/../lib/config.php';
require_once __DIR__.'/../lib/http.php';
require_once __DIR__.'/../lib/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

if ($client_id !== envr('WEAVER_OAUTH_CLIENT_ID') || $client_secret !== envr('WEAVER_OAUTH_CLIENT_SECRET')) {
  unauthorized('Bad client credentials');
}

if ($grant_type !== 'authorization_code') {
  bad_request('Unsupported grant_type');
}

/** Verify the auth code (it was signed by us) */
try {
  $pub = openssl_pkey_get_details(openssl_pkey_get_private(envr('WEAVER_JWT_PRIVATE_KEY')))['key'];
  // Build a public key from private; simpler: reuse private to decode (php-jwt allows it)
  $claims = JWT::decode($code_jwt, new Key(envr('WEAVER_JWT_PRIVATE_KEY'), 'RS256'));
  $claims = (array)$claims;
  if (($claims['typ'] ?? '') !== 'auth_code') throw new Exception('not auth_code');
} catch (Throwable $e) {
  unauthorized('Invalid code');
}

/** Issue access & refresh tokens */
$access = weaver_sign([
  'typ'=>'access',
  'sub'=>$claims['sub'],
  'email'=>$claims['email'] ?? null,
  'scope'=>$claims['scope'] ?? 'openid',
], intval(envr('WEAVER_JWT_TTL', 1800)));

$refresh = weaver_sign([
  'typ'=>'refresh',
  'sub'=>$claims['sub'],
  'scope'=>$claims['scope'] ?? 'openid',
], intval(envr('WEAVER_REFRESH_TTL', 1209600)));

json_out([
  'access_token' => $access,
  'token_type'   => 'Bearer',
  'expires_in'   => intval(envr('WEAVER_JWT_TTL', 1800)),
  'scope'        => $claims['scope'] ?? 'openid',
  'refresh_token'=> $refresh
]);
