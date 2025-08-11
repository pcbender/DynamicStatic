<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/hmac.php';

header('Content-Type: application/json');

$timestamp = $_SERVER['HTTP_X_TIMESTAMP'] ?? null;
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;
if (!$timestamp || !$signature || !verify_hmac($timestamp, '', $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$db = initDb();
$job = getJob($db, $id);
if (!$job) {
    http_response_code(404);
    echo json_encode(['error' => 'Job not found']);
    exit;
}

echo $job['payload'];
