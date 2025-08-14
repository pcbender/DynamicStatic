<?php
// Simple API key guard for service-to-service Echo -> Weaver calls.
require_once __DIR__ . '/../bootstrap.php';
$cfgKey = env('WEAVER_API_KEY');
$hdrKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!$cfgKey || !$hdrKey || !hash_equals($cfgKey, $hdrKey)) {
    json_out(['error' => 'unauthorized'], 401);
}
