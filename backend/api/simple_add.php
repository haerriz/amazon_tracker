<?php
ini_set('display_errors', 0);
error_reporting(0);

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
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
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
    
    // Skip duplicate check for now - focus on scraping availability
    
    // Try to scrape real data
    require_once 'amazon_api_scraper.php';
    $scraper = new AmazonAPIScraper();
    $productData = $scraper->scrapeProduct($asin, $market);
    
    if ($productData && $productData['title'] && $productData['price']) {
        // Insert product into database
        $domains = ['IN' => 'amazon.in', 'US' => 'amazon.com', 'UK' => 'amazon.co.uk'];
        $domain = $domains[$market] ?? 'amazon.in';
        $affiliateUrl = "https://{$domain}/dp/{$asin}";
        
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
        
        // Add price history
        $stmt = $db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
        $stmt->execute([$productId, $productData['price']]);
        
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
    } else {
        http_response_code(400);
        echo json_encode([
            'error' => 'Unable to extract product data from Amazon. Please verify the ASIN/URL is correct.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred']);
}
?>