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
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    $asin = trim($input['asin'] ?? '');
    $market = $input['market'] ?? 'IN';
    
    if (!$asin || !preg_match('/^[A-Z0-9]{10}$/i', $asin)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid ASIN required']);
        exit;
    }
    
    // Try to scrape real Amazon data
    $url = "https://amazon.in/dp/{$asin}";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $title = null;
    $price = null;
    
    if ($httpCode === 200 && $html) {
        // Extract title
        if (preg_match('/<span[^>]*id="productTitle"[^>]*>\s*([^<]+?)\s*<\/span>/i', $html, $matches)) {
            $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        
        // Extract price
        if (preg_match('/₹\s*([0-9,]+(?:\.[0-9]{2})?)/i', $html, $matches)) {
            $price = floatval(str_replace(',', '', $matches[1]));
        }
    }
    
    if ($title && $price) {
        // Successfully scraped real data
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Insert product
        $stmt = $db->prepare("INSERT INTO products (asin, market, title, current_price, url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$asin, $market, $title, $price, $url]);
        
        $productId = $db->lastInsertId();
        
        // Add price history
        $stmt = $db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
        $stmt->execute([$productId, $price]);
        
        echo json_encode([
            'success' => true,
            'product' => [
                'id' => $productId,
                'asin' => $asin,
                'title' => $title,
                'price' => $price,
                'url' => $url
            ]
        ]);
    } else {
        // Scraping failed - return error without fake data
        http_response_code(400);
        echo json_encode([
            'error' => 'Unable to extract product data from Amazon. The product may not be available or accessible.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred']);
}
?>