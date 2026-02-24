<?php
// Common bootstrap: session, db, functions
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
date_default_timezone_set('Asia/Singapore');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
?>
