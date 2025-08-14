<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_api_key.php';
if (env('WEAVER_SESSION_JWT_SECRET')) { require_once __DIR__ . '/session.php'; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'method_not_allowed'], 405);
}
$raw = file_get_contents('php://input');
if (strlen($raw) > 512 * 1024) { json_out(['error' => 'payload_too_large'], 413); }
$data = json_decode($raw, true) ?: [];
$id = $data['id'] ?? '';
$status = $data['status'] ?? '';
if (!$id || !$status) { json_out(['error' => 'missing_fields'], 400); }
if (function_exists('requireSession') && env('WEAVER_SESSION_JWT_SECRET')) {
    requireSession(['job_id' => $id]);
}
$db = initDb();
$job = getJob($db, $id);
if (!$job) { json_out(['error' => 'not_found'], 404); }
updateJobStatus($db, $id, $status, $data['payload'] ?? null);
json_out(['status' => 'updated']);
