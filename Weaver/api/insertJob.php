<?php
require_once 'db.php';
require_once 'auth.php';
require_once 'github_app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

$claims = require_bearer();
require_scope($claims, 'jobs:write');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    bad_request('Invalid JSON input');
}
if (isset($input['github_token']) || isset($input['payload']['github_token'])) {
    bad_request('Use GitHub App flow');
}

$input['created_by_sub'] = $claims['sub'] ?? null;
$input['created_by_email'] = $claims['email'] ?? null;

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

json_out(['status' => 'success', 'job_id' => $result['id'], 'dispatched' => $dispatched]);
