<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_oauth();

$db = initDb();
$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}
$job = getJob($db, $id);
if (!$job) {
    http_response_code(404);
    echo json_encode(['error' => 'Job not found']);
    exit;
}
echo json_encode($job);
