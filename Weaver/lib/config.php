<?php
require_once __DIR__.'/../vendor/autoload.php';

// Load environment variables from the repository root (.env)
$root = dirname(__DIR__, 2);
if (!file_exists($root.'/.env')) {
  $root = dirname(__DIR__);
}
Dotenv\Dotenv::createImmutable($root)->safeLoad();

function envr($k, $d=null){ $v=getenv($k); return $v!==false?$v:$d; }

const REQUIRED_ENVS = [
  'WEAVER_ISSUER','WEAVER_OAUTH_CLIENT_ID','WEAVER_OAUTH_CLIENT_SECRET',
  'WEAVER_JWT_KID','WEAVER_JWT_PRIVATE_KEY','GOOGLE_CLIENT_ID','GOOGLE_CLIENT_SECRET',
  'GOOGLE_REDIRECT_URI'
];

foreach (REQUIRED_ENVS as $k) {
  if (envr($k)===null) { http_response_code(500); die("Missing env: $k"); }
}
