<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once 'realtime_scraper.php';

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
    $stmt = $db->prepare("SELECT id, current_price FROM products WHERE asin = ? AND market = ?");
    $stmt->execute([$asin, $market]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Scrape current price with real-time scraper
    $scraper = new RealtimeScraper();
    $productData = $scraper->scrapeProduct($asin, $market);
    
    if ($productData && $productData['price']) {
        $oldPrice = floatval($product['current_price']);
        $newPrice = floatval($productData['price']);
        
        // Update product price
        $stmt = $db->prepare("UPDATE products SET current_price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$newPrice, $product['id']]);
        
        // Add to price history only if price changed significantly (more than 1%)
        if (abs($newPrice - $oldPrice) / $oldPrice > 0.01) {
            $stmt = $db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
            $stmt->execute([$product['id'], $newPrice]);
        }
        
        // Calculate price change
        $priceChange = $newPrice - $oldPrice;
        $priceChangePercent = $oldPrice > 0 ? (($priceChange / $oldPrice) * 100) : 0;
        
        echo json_encode([
            'success' => true,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'price_change' => $priceChange,
            'price_change_percent' => round($priceChangePercent, 2),
            'title' => $productData['title'],
            'rating' => $productData['rating'],
            'review_count' => $productData['review_count'],
            'availability' => $productData['availability'],
            'updated_at' => date('Y-m-d H:i:s'),
            'method' => $productData['method'] ?? 'realtime'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch current price - Amazon may be blocking requests'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>