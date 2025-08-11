<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(['error' => 'Method not allowed'], 405);
}

$claims = require_bearer();
require_scope($claims, 'jobs:read');

$db = initDb();
$id = $_GET['id'] ?? null;
if (!$id) {
    bad_request('Missing id');
}
$job = getJob($db, $id);
if (!$job) {
    json_out(['error' => 'Job not found'], 404);
}
if ($job['created_by_sub'] !== ($claims['sub'] ?? null) && !has_scope($claims, 'jobs:admin')) {
    forbidden();
}
json_out($job);
