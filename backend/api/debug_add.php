<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug endpoint to test add_product functionality
try {
    require_once '../config/database.php';
    require_once 'amazon_api_scraper.php';
    
    $asin = $_GET['asin'] ?? 'B091Q6FNCZ';
    $market = $_GET['market'] ?? 'IN';
    
    echo json_encode([
        'debug' => true,
        'asin' => $asin,
        'market' => $market,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>