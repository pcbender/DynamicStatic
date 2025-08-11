<?php
require_once __DIR__.'/../lib/jwt.php';
header('Content-Type: application/json');
echo json_encode(['keys'=>[jwk_from_private()]], JSON_UNESCAPED_SLASHES);
