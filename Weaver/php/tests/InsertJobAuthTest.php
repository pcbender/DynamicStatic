<?php
// Lightweight test script (avoids explicit PHPUnit dependency resolution issues)
// Simulates three scenarios and throws exceptions on failure.

$apiFile = __DIR__ . '/../api/insertJob.php';
$_ENV['WEAVER_API_KEY'] = 'test-key';
$_ENV['WEAVER_ALLOWLIST'] = '[{"owner":"o","repo":"r"}]';
$_ENV['WEAVER_SESSION_JWT_SECRET'] = 'secret';

function mustContain($haystack, $needle, $msg) {
        if (strpos($haystack, $needle) === false) { throw new \Exception($msg . " (needle '$needle' not found)"); }
}

// 1. Missing API key
$_SERVER = ['REQUEST_METHOD' => 'POST'];
unset($_SERVER['HTTP_X_API_KEY']);
$GLOBALS['__RAW_BODY_OVERRIDE__'] = json_encode(['owner'=>'o','repo'=>'r','article'=>['title'=>'T']]);
ob_start(); include $apiFile; $out = ob_get_clean();
mustContain($out, 'unauthorized', 'Should contain unauthorized');

// 2. Legacy payload success
$_SERVER = ['REQUEST_METHOD' => 'POST', 'HTTP_X_API_KEY' => 'test-key'];
$GLOBALS['__RAW_BODY_OVERRIDE__'] = json_encode(['owner'=>'o','repo'=>'r','article'=>['title'=>'T','body'=>'Body']]);
ob_start(); include $apiFile; $out = ob_get_clean();
mustContain($out, 'job_id', 'Legacy payload should succeed');

// 3. New payload with assets
$_SERVER = ['REQUEST_METHOD' => 'POST', 'HTTP_X_API_KEY' => 'test-key'];
$payload = [
    'payload' => [
        'type' => 'article',
        'metadata' => ['title' => 'T'],
        'content' => ['format' => 'markdown', 'body' => 'Body'],
        'assets' => [
            ['type'=>'image','name'=>'hero.png','url'=>'https://example.com/hero.png','placement'=>'hero','alt'=>'A'],
            ['type'=>'image','name'=>'inline.png','url'=>'https://example.com/inline.png']
        ],
        'deployment' => [ 'repository' => 'o/r', 'filename' => 'test.html' ]
    ]
];
$GLOBALS['__RAW_BODY_OVERRIDE__'] = json_encode($payload);
ob_start(); include $apiFile; $out = ob_get_clean();
mustContain($out, 'job_id', 'New payload with assets should succeed');

echo json_encode(['status'=>'ok','cases'=>3]);
