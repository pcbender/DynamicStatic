<?php
require_once __DIR__.'/../lib/config.php';
require_once __DIR__.'/../lib/http.php';
require_once __DIR__.'/../lib/google_oidc.php';
require_once __DIR__.'/../lib/jwt.php';

/**
 * Google redirects here:
 *   GET /oauth/google_callback.php?code=...&state=<gstate>
 * We exchange code at Google, read ID token (email/sub), then mint a short-lived
 * **authorization code** (JWT) for ChatGPT and redirect to the GPT callback with ?code=...
 */

if (!isset($_GET['code'], $_GET['state'])) bad_request('Missing code/state');

$gstate = json_decode(base64_decode(strtr($_GET['state'],'-_','+/')), true);
if (!$gstate || empty($gstate['redirect_uri']) || empty($gstate['client_id'])) {
  bad_request('Invalid state');
}
if ($gstate['client_id'] !== envr('WEAVER_OAUTH_CLIENT_ID')) unauthorized('client mismatch');

$tok = google_exchange_code($_GET['code']);
$id_token = $tok['id_token'] ?? null;
if (!$id_token) server_error('No id_token from Google');

[, $gclaims] = parse_jwt($id_token);
if (($gclaims['aud'] ?? null) !== envr('GOOGLE_CLIENT_ID')) unauthorized('aud mismatch');
if (!in_array($gclaims['iss'] ?? '', ['https://accounts.google.com','accounts.google.com'], true)) unauthorized('iss mismatch');

$sub = $gclaims['sub'] ?? null;
$email = $gclaims['email'] ?? null;
if (!$sub) unauthorized('no sub');

/** Mint short-lived authorization code for ChatGPT (valid ~60s) */
$auth_code = weaver_sign([
  'typ' => 'auth_code',
  'sub' => $sub,
  'email' => $email,
  'scope' => $gstate['scope'],
  'client_id' => $gstate['client_id']
], 60);

$redir = $gstate['redirect_uri'];
$qs = http_build_query(['code'=>$auth_code, 'state'=>$gstate['orig_state'] ?? '']);
header('Location: '.$redir.(strpos($redir,'?')===false?'?':'&').$qs, true, 302);
exit;
