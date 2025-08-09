<?php
require_once 'db.php';
require_once 'auth.php';
require_once 'github_app.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_oauth();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}
if (isset($input['github_token']) || isset($input['payload']['github_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Use GitHub App flow']);
    exit;
}

$result = insertJob($input);
$dispatched = false;
try {
    $payload = $input['payload'] ?? [];
    $repoFull = $payload['repository'] ?? null;
    if ($repoFull) {
        [$owner, $repo] = explode('/', $repoFull, 2);
        $branch = $payload['branch'] ?? 'main';
        $basePath = $payload['base_path'] ?? '';
        $token = githubInstallationToken($owner, $repo);
        githubDispatch($repoFull, 'dsb.new_article', ['job_id' => $result['id'], 'branch' => $branch, 'base_path' => $basePath], $token);
        $dispatched = true;
    }
} catch (Exception $e) {
    $dispatched = false;
}

echo json_encode(['status' => 'success', 'job_id' => $result['id'], 'dispatched' => $dispatched]);
