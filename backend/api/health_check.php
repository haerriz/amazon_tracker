<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Database check
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $health['checks']['database'] = $db ? 'ok' : 'failed';
} catch (Exception $e) {
    $health['checks']['database'] = 'failed: ' . $e->getMessage();
    $health['status'] = 'error';
}

// File system check
$requiredFiles = [
    'products.php',
    'realtime_scraper.php',
    'scraper.php'
];

$health['checks']['files'] = 'ok';
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $health['checks']['files'] = "missing: $file";
        $health['status'] = 'error';
        break;
    }
}

// API endpoints check
$health['checks']['endpoints'] = [
    'products' => file_exists('products.php') ? 'ok' : 'missing',
    'scraper' => file_exists('realtime_scraper.php') ? 'ok' : 'missing'
];

echo json_encode($health, JSON_PRETTY_PRINT);
?>