<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

$claims = require_bearer();
require_scope($claims, 'jobs:read');

$db = initDb();
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['status'])) {
    bad_request('Missing status');
}

$jobs = getAllJobs($db, $data['status']);
if (!has_scope($claims, 'jobs:admin')) {
    $jobs = array_values(array_filter($jobs, function($j) use ($claims) {
        return ($j['created_by_sub'] ?? null) === ($claims['sub'] ?? null);
    }));
}
json_out($jobs);
