<?php
session_start();
define('ROOT', dirname(__DIR__));
$CONFIG = require ROOT . '/config.php';
define('UPLOADS_PATH', $CONFIG['uploads']);
define('UPLOADS_URL', '/uploads');
require_once __DIR__ . '/functions.php';