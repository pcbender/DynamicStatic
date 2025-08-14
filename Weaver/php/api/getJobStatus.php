<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/db.php';
// Optional: session binding
if (env('WEAVER_SESSION_JWT_SECRET')) { require_once __DIR__ . '/session.php'; }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(['error' => 'method_not_allowed'], 405);
}
$id = $_GET['id'] ?? '';
if (!$id) { json_out(['error' => 'missing_id'], 400); }
if (strlen($id) > 128) { json_out(['error' => 'invalid_id'], 400); }
$expected = ['job_id' => $id];
if (function_exists('requireSession') && env('WEAVER_SESSION_JWT_SECRET')) {
    // Ignore return; validation only.
    requireSession($expected);
}
$db = initDb();
$job = getJob($db, $id);
if (!$job) { json_out(['error' => 'not_found'], 404); }
json_out($job);
