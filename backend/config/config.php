<?php
// Environment detection
function isLocalEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return in_array($host, ['localhost', '127.0.0.1', 'localhost:8080']);
}

// Database configuration
if (isLocalEnvironment()) {
    // Local development settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'price_tracker');
    define('DB_USER', 'root');
    define('DB_PASS', 'Admin@123');
    define('BASE_URL', '/tracker');
} else {
    // Production server settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u434561653_amazon_tracker');
    define('DB_USER', 'u434561653_amazon_tracker');
    define('DB_PASS', 'Whatsapp@2026');
    define('BASE_URL', '');
}

// Common settings
define('AFFILIATE_TAG_IN', 'haerriz06-21');
define('AFFILIATE_TAG_US', 'haerriz06-20');
define('AFFILIATE_TAG_UK', 'haerriz06-21');
?>