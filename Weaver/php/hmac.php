<?php
function verify_hmac(string $timestamp, string $body, string $signature): bool {
    $secret = getenv('WEAVER_HMAC_SECRET');
    if (!$secret) {
        return false;
    }
    $sig = $signature;
    if (str_starts_with($sig, 'sha256=')) {
        $sig = substr($sig, 7);
    }
    $expected = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    $now = time();
    $ts = strtotime($timestamp);
    if ($ts === false || abs($now - $ts) > 600) {
        return false;
    }
    return hash_equals($expected, $sig);
}
