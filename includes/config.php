<?php
// Security settings
// ini_set('session.cookie_httponly', 1);
// ini_set('session.use_only_cookies', 1);

// session_start();
// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'multi_vendor_marketplace');
// AI Configuration - Gemini

define('SITE_NAME', 'MultiVendor Market');
define('BASE_URL', '/multi-vendor/');
define('ADMIN_EMAIL', 'admin@marketplace.local');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', BASE_URL . 'uploads');

// Connect to database
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}
if (!$mysqli->select_db(DB_NAME)) {
    die('Database "' . DB_NAME . '" not found. Please import database.sql using phpMyAdmin or MySQL before using the app.');
}
$mysqli->set_charset('utf8mb4');

require_once __DIR__ . '/functions.php';

