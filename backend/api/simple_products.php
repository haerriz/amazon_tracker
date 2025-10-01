<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../config/database.php';
    require_once '../config/affiliate.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Simple query to get products
    $stmt = $db->prepare("SELECT * FROM products ORDER BY created_at DESC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to proper format and add affiliate URLs
    foreach ($products as &$product) {
        $product['current_price'] = floatval($product['current_price']);
        $product['target_price'] = null; // No alerts for now
        
        // Generate affiliate URL if not already set
        if (!$product['url'] || strpos($product['url'], 'tag=') === false) {
            $product['url'] = generateAffiliateUrl($product['asin'], $product['market'] ?? 'IN');
        }
    }
    
    echo json_encode($products);
    
} catch (Exception $e) {
    // Return empty array if any error occurs
    echo json_encode([]);
}
?>