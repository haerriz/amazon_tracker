<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once 'scraper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$asin = $input['asin'] ?? '';
$market = $input['market'] ?? 'IN';

if (!$asin) {
    http_response_code(400);
    echo json_encode(['error' => 'ASIN required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get product
    $stmt = $db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
    $stmt->execute([$asin, $market]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Scrape current price
    $scraper = new AmazonScraper();
    $productData = $scraper->fetchProductData($asin, $market);
    
    if ($productData && $productData['price']) {
        // Update product price
        $stmt = $db->prepare("UPDATE products SET current_price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$productData['price'], $product['id']]);
        
        // Add to price history
        $stmt = $db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
        $stmt->execute([$product['id'], $productData['price']]);
        
        echo json_encode([
            'success' => true,
            'price' => $productData['price'],
            'title' => $productData['title'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch current price'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>