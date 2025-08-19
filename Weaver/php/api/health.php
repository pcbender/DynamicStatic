<?php
require_once __DIR__ . '/../bootstrap.php';

// Lightweight health / diagnostics endpoint (no auth) - DO NOT expose secrets.
$envLoaded = defined('WEAVER_ENV_FILE_LOADED') ? basename(WEAVER_ENV_FILE_LOADED) : null;
$mode = defined('WEAVER_ENV_MODE') ? WEAVER_ENV_MODE : 'unknown';

// Surface minimal config signals (presence only, not values)
$config = function_exists('weaver_config_status') ? weaver_config_status() : ['presence'=>[], 'all_set'=>false];

json_out([
  'status' => 'ok',
  'mode' => $mode,
  'env_file' => $envLoaded,
  'config' => $config,
  'timestamp' => gmdate('c')
]);
