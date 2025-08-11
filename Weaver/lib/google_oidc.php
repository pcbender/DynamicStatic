<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/http.php';
use GuzzleHttp\Client;

/** Build Google auth URL */
function google_auth_url($state, $scope) {
  $params = http_build_query([
    'client_id' => envr('GOOGLE_CLIENT_ID'),
    'redirect_uri' => envr('GOOGLE_REDIRECT_URI'),
    'response_type' => 'code',
    'scope' => $scope ?: 'openid email profile',
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => $state,
  ]);
  return 'https://accounts.google.com/o/oauth2/v2/auth?'.$params;
}

/** Exchange code -> tokens at Google */
function google_exchange_code($code) {
  $client = new Client(['timeout'=>10]);
  $resp = $client->post('https://oauth2.googleapis.com/token', [
    'form_params' => [
      'code' => $code,
      'client_id' => envr('GOOGLE_CLIENT_ID'),
      'client_secret' => envr('GOOGLE_CLIENT_SECRET'),
      'redirect_uri' => envr('GOOGLE_REDIRECT_URI'),
      'grant_type' => 'authorization_code'
    ]
  ]);
  return json_decode((string)$resp->getBody(), true);
}

/** Decode Google ID token (header claims only, minimal verify for skeleton) */
function parse_jwt($jwt){
  [$h,$p,$s]=explode('.', $jwt);
  return [json_decode(base64_decode(strtr($h,'-_','+/')),true),
          json_decode(base64_decode(strtr($p,'-_','+/')),true)];
}
