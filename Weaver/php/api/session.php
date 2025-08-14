<?php
require_once __DIR__ . '/../bootstrap.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function issueSession(string $jobId, string $owner, string $repo, int $ttl = 1800): string {
    $secret = env('WEAVER_SESSION_JWT_SECRET');
    if (!$secret) { return ''; }
    $now = time();
    $payload = [
        'iat' => $now,
        'exp' => $now + $ttl,
        'job_id' => $jobId,
        'owner' => $owner,
        'repo' => $repo
    ];
    return JWT::encode($payload, $secret, 'HS256');
}

function requireSession(array $expected = []): array {
    $secret = env('WEAVER_SESSION_JWT_SECRET');
    if (!$secret) { json_out(['error' => 'session_disabled'], 400); }
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
        json_out(['error' => 'missing_session'], 401);
    }
    $jwt = $m[1];
    try {
        $payload = (array) JWT::decode($jwt, new Key($secret, 'HS256'));
    } catch (Throwable $e) {
        json_out(['error' => 'invalid_session'], 401);
    }
    foreach (['job_id','owner','repo'] as $k) {
        if (isset($expected[$k]) && $expected[$k] !== ($payload[$k] ?? null)) {
            json_out(['error' => 'session_mismatch'], 403);
        }
    }
    return $payload;
}
