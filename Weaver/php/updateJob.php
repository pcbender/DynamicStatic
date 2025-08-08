<?php
require_once 'db.php';
$db = initDb();
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'], $data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}
updateJobStatus($db, $data['id'], $data['status'], $data['payload'] ?? null);
echo json_encode(['status' => 'updated']);
