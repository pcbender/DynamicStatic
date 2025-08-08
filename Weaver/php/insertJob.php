<?php
require_once 'db.php';

// Entry point
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $response = insertJob($input);
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid JSON input'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}
