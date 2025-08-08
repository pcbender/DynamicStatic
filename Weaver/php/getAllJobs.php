<?php
require_once 'db.php';
header('Content-Type: application/json');

$db = initDb();
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing status']);
    exit;
}

$jobs = getAllJobs($db, $data['status']);
echo json_encode($jobs);

