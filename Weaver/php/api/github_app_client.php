<?php
require_once __DIR__ . '/../bootstrap.php';
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

function githubAppJwt(): string {
    // Prefer the App Client ID for the issuer (GitHub docs recommendation), fallback to App ID.
    $clientIdOrAppId = env('GITHUB_APP_CLIENT_ID') ?: env('GITHUB_APP_ID');
    $rawKey = env('GITHUB_APP_PRIVATE_KEY');
    if (!$clientIdOrAppId || !$rawKey) { throw new RuntimeException('GitHub App not configured'); }
    // If the value contains a PEM header treat it as inline; replace escaped newlines. Otherwise treat as path.
    if (str_contains($rawKey, '-----BEGIN')) {
        $pem = str_replace('\n', "\n", $rawKey);
    } else {
        $pem = @file_get_contents($rawKey);
    }
    if (!$pem) { throw new RuntimeException('Private key load failed'); }

    // Caching: reuse recently issued App JWT until ~30s before it would expire or if key changed.
    $fingerprint = substr(sha1($pem . '|' . $clientIdOrAppId), 0, 16);
    $cacheKey = 'gh_app_jwt';
    if (function_exists('fileCacheGet')) {
        $cached = fileCacheGet($cacheKey);
        if ($cached && ($cached['exp'] ?? 0) > (time() + 30) && ($cached['fp'] ?? '') === $fingerprint && isset($cached['token'])) {
            return $cached['token'];
        }
    }
    $now = time();
    $payload = [
        'iat' => $now - 60,       // small clock skew allowance
        'exp' => $now + 9 * 60,   // under GitHub 10 min max
        'iss' => (string)$clientIdOrAppId // client ID preferred
    ];
    $jwt = JWT::encode($payload, $pem, 'RS256');
    if (function_exists('fileCacheSet')) {
        // Store with slight early expiry to force refresh before GitHub rejects it.
        fileCacheSet($cacheKey, ['token' => $jwt, 'exp' => $payload['exp'] - 30, 'fp' => $fingerprint], ($payload['exp'] - time()));
    }
    return $jwt;
}

function githubBaseHeaders(): array {
    return [
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'Weaver-GitHubApp'
    ];
}

function githubClientForApp(): Client {
    return new Client([
        'base_uri' => 'https://api.github.com/',
        'headers' => array_merge(githubBaseHeaders(), [ 'Authorization' => 'Bearer ' . githubAppJwt() ]),
        'timeout' => 20
    ]);
}

function githubClientForInstallation(string $token): Client {
    return new Client([
        'base_uri' => 'https://api.github.com/',
        'headers' => array_merge(githubBaseHeaders(), [ 'Authorization' => 'Bearer ' . $token ]),
        'timeout' => 20
    ]);
}

function getInstallationId(string $owner, string $repo): int {
    $key = strtolower($owner.'/'.$repo);
    $cached = fileCacheGet('gh_inst_'.$key);
    if ($cached && ($cached['exp'] ?? 0) > time()) {
        return (int)$cached['id'];
    }
    $app = githubClientForApp();
    $resp = $app->get("repos/$owner/$repo/installation");
    $data = json_decode($resp->getBody()->getContents(), true);
    $record = ['id' => (int)$data['id'], 'exp' => time() + 1800];
    fileCacheSet('gh_inst_'.$key, $record, 1800);
    return $record['id'];
}

function getInstallationToken(string $owner, string $repo): string {
    $id = getInstallationId($owner, $repo);
    $app = githubClientForApp();
    $resp = $app->post("app/installations/$id/access_tokens", ['json' => []]);
    $data = json_decode($resp->getBody()->getContents(), true);
    return $data['token'];
}

function ghGet(Client $c, string $url) { return json_decode($c->get($url)->getBody()->getContents(), true); }
function ghPost(Client $c, string $url, array $body) { return json_decode($c->post($url, ['json'=>$body])->getBody()->getContents(), true); }
function ghPut(Client $c, string $url, array $body) { return json_decode($c->put($url, ['json'=>$body])->getBody()->getContents(), true); }

// ---------------- File Cache (simple persistent) ----------------
// Stores JSON blobs per key in ../cache
function fileCacheDir(): string {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    return $dir;
}
function fileCachePath(string $key): string { return fileCacheDir() . '/' . md5($key) . '.json'; }
function fileCacheGet(string $key) {
    $path = fileCachePath($key);
    if (!is_file($path)) return null;
    $raw = @file_get_contents($path);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    if (!$data) return null;
    if (($data['exp'] ?? 0) < time()) { @unlink($path); return null; }
    return $data;
}
function fileCacheSet(string $key, $val, int $ttl = 1800): void {
    $path = fileCachePath($key);
    $payload = is_array($val) ? $val : ['val' => $val];
    if (!isset($payload['exp'])) { $payload['exp'] = time() + $ttl; }
    $tmp = $path . '.tmp';
    @file_put_contents($tmp, json_encode($payload, JSON_UNESCAPED_SLASHES));
    @rename($tmp, $path);
}
