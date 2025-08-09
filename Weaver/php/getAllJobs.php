<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_oauth();

$db = initDb();
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing status']);
    exit;
}

$jobs = getAllJobs($db, $data['status']);
echo json_encode($jobs);
