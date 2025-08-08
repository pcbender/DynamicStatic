<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input)
        throw new Exception("Invalid JSON input.");

    // Required fields
    $repo = $input['repository'];
    $token = $input['github_token'];
    $filename = $input['filename'];
    $content = $input['content'];
    $branch = $input['branch'] ?? 'main';
    $baseUrl = rtrim($input['base_url'] ?? 'https://example.com', '/');
    $articlePath = "{$baseUrl}/articles/{$filename}";


    // Generate branch name from filename
    $slug = pathinfo($filename, PATHINFO_FILENAME);
    $featureBranch = "echo/{$slug}";

    // Build override config
    $overrides = $input['overrides'] ?? [];
    $autoMerge = $overrides['auto_merge'] ?? false;
    $deleteBranch = $overrides['delete_branch'] ?? false;
    $prEnabled = $overrides['pull_request']['enabled'] ?? true;
    $prBase = $overrides['pull_request']['base'] ?? $branch;
    $prTitle = $overrides['pull_request']['title'] ?? "Echo: Publish “{$slug}”";
    $prBody = $overrides['pull_request']['body'] ?? "Published by Echo.";
    $buildHook = $overrides['build_hook'] ?? null;

    $baseSha = getBaseSha($repo, $prBase, $token);
    createBranch($repo, $featureBranch, $baseSha, $token);
    $commit = putFile($repo, $filename, $featureBranch, $content, $token, "✍️ Echo: Publish '{$slug}'");

    $prUrl = null;
    if ($prEnabled) {
        $pr = createPullRequest($repo, $prTitle, $featureBranch, $prBase, $prBody, $token);
        $prUrl = $pr['url'];

        if ($autoMerge) {
            mergePullRequest($repo, $pr['number'], $token, $prTitle);
            if ($deleteBranch) {
                deleteBranch($repo, $featureBranch, $token);
            }
        }
    }

    // Trigger build if requested
    $buildTriggered = false;
    if ($buildHook) {
        $buildTriggered = triggerBuildHook($buildHook);
    }

    echo json_encode([
        "status" => "success",
        "url" => $articlePath,
        "commitHash" => $commit['commit']['sha'],
        "pullRequestUrl" => $prUrl,
        "build_triggered" => $buildTriggered
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// --- Support Functions Below ---

function getBaseSha($repo, $branch, $token)
{
    $url = "https://api.github.com/repos/{$repo}/git/ref/heads/{$branch}";
    $res = curlGet($url, $token);
    return $res['object']['sha'];
}

function createBranch($repo, $newBranch, $baseSha, $token)
{
    $url = "https://api.github.com/repos/{$repo}/git/refs";
    $payload = ["ref" => "refs/heads/{$newBranch}", "sha" => $baseSha];
    curlPost($url, $payload, $token, 201);
}

function putFile($repo, $path, $branch, $content, $token, $commitMessage)
{
    $url = "https://api.github.com/repos/{$repo}/contents/{$path}";
    $payload = [
        "message" => $commitMessage,
        "content" => base64_encode($content),
        "branch" => $branch,
        "committer" => [
            "name" => "Echo",
            "email" => "echo@relationaldesign.ai"
        ]
    ];
    return curlPut($url, $payload, $token);
}

function createPullRequest($repo, $title, $head, $base, $body, $token)
{
    $url = "https://api.github.com/repos/{$repo}/pulls";
    $payload = [
        "title" => $title,
        "head" => $head,
        "base" => $base,
        "body" => $body
    ];
    $res = curlPost($url, $payload, $token, 201);
    return [
        "number" => $res['number'],
        "url" => $res['html_url']
    ];
}

function mergePullRequest($repo, $prNumber, $token, $commitTitle)
{
    $url = "https://api.github.com/repos/{$repo}/pulls/{$prNumber}/merge";
    $payload = [
        "merge_method" => "squash",
        "commit_title" => $commitTitle
    ];
    return curlPut($url, $payload, $token);
}

function deleteBranch($repo, $branch, $token)
{
    $url = "https://api.github.com/repos/{$repo}/git/refs/heads/{$branch}";
    curlDelete($url, $token);
}

function triggerBuildHook($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $info['http_code'] === 200 || $info['http_code'] === 204;
}

// --- Curl Helpers ---

function curlGet($url, $token)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => curlHeaders($token)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function curlPost($url, $data, $token, $expectedCode = 200)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => curlHeaders($token)
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] !== $expectedCode) {
        throw new Exception("POST failed: {$url}");
    }
    return json_decode($response, true);
}

function curlPut($url, $data, $token)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => curlHeaders($token)
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] !== 200 && $info['http_code'] !== 201) {
        throw new Exception("PUT failed: {$url}");
    }
    return json_decode($response, true);
}

function curlDelete($url, $token)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => curlHeaders($token)
    ]);
    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] !== 204) {
        throw new Exception("DELETE failed: {$url}");
    }
}

function curlHeaders($token)
{
    return [
        "Authorization: Bearer {$token}",
        "Accept: application/vnd.github+json",
        "User-Agent: EchoPostman",
        "Content-Type: application/json"
    ];
}
