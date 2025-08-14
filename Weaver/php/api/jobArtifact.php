<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/hmac.php';
require_once __DIR__ . '/auth_api_key.php';
if (env('WEAVER_SESSION_JWT_SECRET')) { require_once __DIR__ . '/session.php'; }

header('Content-Type: application/json');

// HMAC headers remain for external consumer verification but API key is primary auth here.
$timestamp = $_SERVER['HTTP_X_TIMESTAMP'] ?? null;
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;
if ($timestamp && $signature && !verify_hmac($timestamp, '', $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_hmac']);
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

// Optional session validation to ensure caller is bound to this job
if (function_exists('requireSession') && env('WEAVER_SESSION_JWT_SECRET')) {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($hdr) {
        try { requireSession(['job_id' => $id]); } catch (Throwable $e) { /* ignore */ }
    }
}
echo $job['payload'];
