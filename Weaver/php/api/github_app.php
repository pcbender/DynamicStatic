<?php
// Legacy GitHub App helper retained for backward compatibility.
require_once __DIR__ . '/../bootstrap.php';

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function githubAppJwt(): string {
    $appId = getenv('GITHUB_APP_ID');
    $privateKey = getenv('GITHUB_APP_PRIVATE_KEY');
    if (!$appId || !$privateKey) {
        throw new Exception('GitHub App credentials not configured');
    }
    $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $payload = base64url_encode(json_encode([
        'iss' => (int)$appId,
        'iat' => $now - 60,
        'exp' => $now + 540
    ]));
    $unsigned = $header . '.' . $payload;
    openssl_sign($unsigned, $signature, $privateKey, 'SHA256');
    return $unsigned . '.' . base64url_encode($signature);
}

function githubInstallationToken(string $owner, string $repo): string {
    $jwt = githubAppJwt();
    $headers = [
        'Authorization: Bearer ' . $jwt,
        'Accept: application/vnd.github+json',
        'User-Agent: Weaver'
    ];
    $installUrl = "https://api.github.com/repos/{$owner}/{$repo}/installation";
    $ch = curl_init($installUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] !== 200) {
        throw new Exception('Unable to resolve installation');
    }
    $data = json_decode($response, true);
    $id = $data['id'];
    $tokenUrl = "https://api.github.com/app/installations/{$id}/access_tokens";
    $ch = curl_init($tokenUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => $headers
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] !== 201) {
        throw new Exception('Unable to create installation token');
    }
    $data = json_decode($response, true);
    return $data['token'];
}

function githubDispatch(string $repo, string $eventType, array $payload, string $token): void {
    $url = "https://api.github.com/repos/{$repo}/dispatches";
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github+json',
        'User-Agent: Weaver',
        'Content-Type: application/json'
    ];
    $body = json_encode(['event_type' => $eventType, 'client_payload' => $payload]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body
    ]);
    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] !== 204) {
        throw new Exception('repository_dispatch failed');
    }
}
