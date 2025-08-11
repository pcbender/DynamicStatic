<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

$claims = require_bearer();
require_scope($claims, 'jobs:write');

$db = initDb();
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'], $data['status'])) {
    bad_request('Missing fields');
}
$job = getJob($db, $data['id']);
if (!$job) {
    json_out(['error' => 'Job not found'], 404);
}
if ($job['created_by_sub'] !== ($claims['sub'] ?? null) && !has_scope($claims, 'jobs:admin')) {
    forbidden();
}
updateJobStatus($db, $data['id'], $data['status'], $data['payload'] ?? null);
json_out(['status' => 'updated']);
