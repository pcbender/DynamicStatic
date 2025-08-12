<?php
require_once __DIR__ . '/../bootstrap.php';

use Weaver\Service\JwtService;

$jwt = new JwtService($GLOBALS['weaverConfig']);
header('Content-Type: application/json');
echo json_encode(['keys' => [$jwt->jwkFromPrivate()]], JSON_UNESCAPED_SLASHES);
