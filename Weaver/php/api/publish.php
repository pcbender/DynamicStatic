<?php
require_once __DIR__ . "/../bootstrap.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input)
        throw new Exception("Invalid JSON input.");

    if (isset($input['github_token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Use GitHub App flow.']);
        exit;
    }

    throw new Exception('publish.php is deprecated.');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
}
