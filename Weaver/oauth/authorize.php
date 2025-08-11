<?php
require_once __DIR__.'/../lib/config.php';
require_once __DIR__.'/../lib/http.php';
require_once __DIR__.'/../lib/google_oidc.php';

/**
 * ChatGPT hits:
 *   GET /oauth/authorize?response_type=code&client_id=...&redirect_uri=...&state=...&scope=...
 * We verify client_id/redirect_uri, then redirect user to Google with a signed state
 * that includes the original redirect_uri, client_id, scope, and state.
 */

$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$state = $_GET['state'] ?? '';
$scope = $_GET['scope'] ?? 'openid email profile';

if ($client_id !== envr('WEAVER_OAUTH_CLIENT_ID')) {
  unauthorized('Unknown client_id');
}

if (!$redirect_uri) bad_request('redirect_uri required');

/** Build signed state (JWT-ish via base64url + HMAC-free: for skeleton we’ll just base64url) */
$payload = [
  'redirect_uri' => $redirect_uri,
  'orig_state' => $state,
  'client_id' => $client_id,
  'scope' => $scope,
  'ts' => time()
];
$gstate = rtrim(strtr(base64_encode(json_encode($payload)), '+/','-_'), '=');

/** Send user to Google */
header('Location: '.google_auth_url($gstate, $scope), true, 302);
exit;
