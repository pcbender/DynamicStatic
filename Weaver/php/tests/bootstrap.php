<?php
putenv('WEAVER_ENV_FILE=.env.test');
$vendor = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendor)) { require $vendor; }
require __DIR__ . '/../bootstrap.php';
