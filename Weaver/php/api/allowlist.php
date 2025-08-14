<?php
require_once __DIR__ . '/../bootstrap.php';
function ownerRepoAllowed(string $owner, string $repo): bool {
    $json = env('WEAVER_ALLOWLIST', '[]');
    $list = json_decode($json, true) ?: [];
    foreach ($list as $pair) {
        if (($pair['owner'] ?? '') === $owner && ($pair['repo'] ?? '') === $repo) return true;
    }
    return false;
}
