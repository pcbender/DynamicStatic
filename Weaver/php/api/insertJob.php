<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/auth_api_key.php';
require_once __DIR__ . '/github_app_client.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

// Validate and normalize assets (ContentAsset schema subset)
function validateAssets($assets) {
    if (!is_array($assets)) return [];
    $out = [];
    $max = 50; // safety cap
    foreach ($assets as $a) {
        if (count($out) >= $max) break;
        if (!is_array($a)) continue;
        $type = $a['type'] ?? null;
        $name = $a['name'] ?? null;
        $url  = $a['url'] ?? null;
        if (!in_array($type, ['image','video','document','audio'], true)) continue;
        if (!$name || !$url || !is_string($name) || !is_string($url)) continue;
        if (!(preg_match('#^https?://#i', $url) || strpos($url, 'data:') === 0)) continue;
        $asset = [ 'type'=>$type,'name'=>$name,'url'=>$url ];
        foreach (['alt','caption'] as $k) {
            if (isset($a[$k]) && is_string($a[$k]) && $a[$k] !== '' && strlen($a[$k]) < 500) { $asset[$k] = $a[$k]; }
        }
        $placement = $a['placement'] ?? 'inline';
        if (in_array($placement, ['hero','inline','gallery','attachment'], true)) { $asset['placement']=$placement; }
        $out[] = $asset;
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'method_not_allowed'], 405);
}

$raw = $GLOBALS['__RAW_BODY_OVERRIDE__'] ?? file_get_contents('php://input');
if (strlen($raw) > 2 * 1024 * 1024) { json_out(['error' => 'payload_too_large'], 413); }
$input = json_decode($raw, true) ?: [];

// Support new schema: wrapper with payload (ContentJobPayload)
$payload = $input['payload'] ?? null;
$owner = '';$repo='';$branch='';
if (is_array($payload)) {
    $deployment = $payload['deployment'] ?? [];
    $repository = $deployment['repository'] ?? '';
    if (strpos($repository, '/') !== false) { [$owner,$repo] = array_map('trim', explode('/', $repository, 2)); }
    $branch = trim($deployment['branch'] ?? '');
    if (!$owner || !$repo || !isset($payload['metadata']) || !isset($payload['content'])) { json_out(['error'=>'missing_fields'],400); }
    if (!preg_match('/^[A-Za-z0-9_.-]+$/', $owner) || !preg_match('/^[A-Za-z0-9_.-]+$/', $repo)) { json_out(['error'=>'invalid_owner_repo'],400); }
    // Allowlist removed; rely on GitHub App installation / auth for repo authorization.
    if (isset($payload['assets'])) { $payload['assets'] = validateAssets($payload['assets']); }
    if (!$branch || preg_match('/[^\w\-\/]/', $branch)) { $branch = 'dynstatic/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)); $payload['deployment']['branch']=$branch; }
} else {
    // Legacy path
    $owner = trim($input['owner'] ?? '');
    $repo = trim($input['repo'] ?? '');
    $branch = trim($input['branch'] ?? '');
    $article = $input['article'] ?? null;
    if (!$owner || !$repo || !$article) { json_out(['error'=>'missing_fields'],400); }
    if (!preg_match('/^[A-Za-z0-9_.-]+$/', $owner) || !preg_match('/^[A-Za-z0-9_.-]+$/', $repo)) { json_out(['error'=>'invalid_owner_repo'],400); }
    // Allowlist removed; rely on GitHub App installation / auth for repo authorization.
    if (!$branch || preg_match('/[^\w\-\/]/', $branch)) { $branch = 'dynstatic/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)); }
    $payload = [
        'type' => 'article',
        'metadata' => ['title' => $article['title'] ?? 'Untitled'],
        'content' => [ 'format' => 'markdown', 'body' => $article['body'] ?? ($article['content'] ?? '') ],
        'deployment' => [ 'repository' => $owner.'/'.$repo, 'branch' => $branch, 'filename' => ($article['filename'] ?? 'article-'.$branch.'.html') ]
    ];
}

try {
    $ghConfigured = env('GITHUB_APP_ID') && env('GITHUB_APP_PRIVATE_KEY');
    if ($ghConfigured) {
        $token = getInstallationToken($owner, $repo);
        $gh = githubClientForInstallation($token);
        // TODO implement actual branch / commit / PR creation.
    }
    $job = insertJob(['status' => 'pending', 'payload' => $payload]);
    $session = issueSession($job['id'], $owner, $repo, 1800);
    json_out(['status' => 'success', 'job_id' => $job['id'], 'weaver_session' => $session]);
} catch (Throwable $e) {
    error_log('insertJob github error: ' . $e->getMessage());
    json_out(['error' => 'github_error'], 500);
}
