<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

initDb();
json_out(['status' => 'initialized']);
