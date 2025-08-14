<?php
require_once __DIR__ . '/../bootstrap.php';

// Lightweight health / diagnostics endpoint (no auth) - DO NOT expose secrets.
$envLoaded = defined('WEAVER_ENV_FILE_LOADED') ? basename(WEAVER_ENV_FILE_LOADED) : null;
$mode = defined('WEAVER_ENV_MODE') ? WEAVER_ENV_MODE : 'unknown';

// Surface minimal config signals (presence only, not values)
$signals = [
  'api_key_set' => env('WEAVER_API_KEY') ? true : false,
  'session_secret_set' => env('WEAVER_SESSION_JWT_SECRET') ? true : false,
  'github_app_id_set' => env('GITHUB_APP_ID') ? true : false,
];

json_out([
  'status' => 'ok',
  'mode' => $mode,
  'env_file' => $envLoaded,
  'signals' => $signals,
  'timestamp' => gmdate('c')
]);
