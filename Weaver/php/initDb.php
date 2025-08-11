<?php
require_once 'db.php';
require_once 'auth.php';

initDb();
json_out(['status' => 'initialized']);
