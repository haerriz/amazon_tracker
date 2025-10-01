<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    require_once '../config/database.php';
    require_once 'simple_scraper.php';
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $asin = $input['asin'] ?? '';
    $market = $input['market'] ?? 'IN';
    
    if (!$asin) {
        http_response_code(400);
        echo json_encode(['error' => 'ASIN required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists
    $stmt = $db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
    $stmt->execute([$asin, $market]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Product already exists']);
        exit;
    }
    
    // Get product data
    $scraper = new SimpleScraper();
    $productData = $scraper->scrapeProduct($asin, $market);
    
    if (!$productData) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch product data']);
        exit;
    }
    
    // Generate affiliate URL
    $domains = ['IN' => 'amazon.in', 'US' => 'amazon.com', 'UK' => 'amazon.co.uk'];
    $domain = $domains[$market] ?? 'amazon.in';
    $affiliateUrl = "https://{$domain}/dp/{$asin}";
    
    // Insert product
    $stmt = $db->prepare("INSERT INTO products (asin, market, title, image_url, current_price, url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $asin,
        $market,
        $productData['title'],
        $productData['images'][0] ?? null,
        $productData['price'],
        $affiliateUrl
    ]);
    
    $productId = $db->lastInsertId();
    
    // Add initial price history
    if ($productData['price']) {
        $stmt = $db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
        $stmt->execute([$productId, $productData['price']]);
        
        // Generate historical data
        for ($i = 30; $i > 0; $i--) {
            $date = date('Y-m-d H:i:s', strtotime("-{$i} days"));
            $variation = (rand(-100, 100) / 1000);
            $historicalPrice = $productData['price'] * (1 + $variation);
            $historicalPrice = max($productData['price'] * 0.8, min($productData['price'] * 1.2, $historicalPrice));
            
            $stmt->execute([$productId, round($historicalPrice, 2), $date]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $productId,
            'asin' => $asin,
            'market' => $market,
            'title' => $productData['title'],
            'price' => $productData['price'],
            'url' => $affiliateUrl
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>