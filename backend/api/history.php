<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $asin = $_GET['asin'] ?? '';
    $market = $_GET['market'] ?? 'IN';
    $days = $_GET['days'] ?? 30;
    
    if (!$asin) {
        http_response_code(400);
        echo json_encode(['error' => 'ASIN required']);
        exit;
    }
    
    // Get price history
    $stmt = $db->prepare("
        SELECT ph.price, ph.timestamp as ts
        FROM price_history ph 
        JOIN products p ON ph.product_id = p.id 
        WHERE p.asin = ? AND p.market = ? AND ph.timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY ph.timestamp ASC
    ");
    $stmt->execute([$asin, $market, $days]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert price to float and ensure proper date format
    foreach ($history as &$item) {
        $item['price'] = floatval($item['price']);
        // Ensure timestamp is in proper format
        if (isset($item['ts'])) {
            $item['ts'] = date('Y-m-d H:i:s', strtotime($item['ts']));
        }
    }
    
    echo json_encode(array_values($history));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>