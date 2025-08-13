<?php
require_once __DIR__ . '/../bootstrap.php';

use Weaver\Service\JwtService;
use Weaver\WeaverConfig;

$jwt = new JwtService(WeaverConfig::getInstance());
header('Content-Type: application/json');
echo json_encode(['keys' => [$jwt->jwkFromPrivate()]], JSON_UNESCAPED_SLASHES);
