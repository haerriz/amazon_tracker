<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$tests = [];

// Test 1: Basic API Response
$tests['basic_response'] = [
    'status' => 'ok',
    'message' => 'API is working',
    'timestamp' => date('Y-m-d H:i:s')
];

// Test 2: Database Connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $tests['database'] = $db ? 'connected' : 'failed';
} catch (Exception $e) {
    $tests['database'] = 'error: ' . $e->getMessage();
}

// Test 3: Products API
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $tests['products_count'] = $result['count'];
} catch (Exception $e) {
    $tests['products_count'] = 'error: ' . $e->getMessage();
}

// Test 4: File Permissions
$files = [
    'products.php' => file_exists('products.php'),
    'realtime_scraper.php' => file_exists('realtime_scraper.php'),
    'scraper.php' => file_exists('scraper.php')
];
$tests['files'] = $files;

// Test 5: Environment Detection
$tests['environment'] = [
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'php_version' => PHP_VERSION,
    'current_dir' => __DIR__
];

echo json_encode($tests, JSON_PRETTY_PRINT);
?>