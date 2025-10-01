<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/affiliate.php';
require_once 'enhanced_scraper.php';

class EnhancedProductsAPI {
    private $db;
    private $scraper;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->scraper = new EnhancedScraper();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        switch ($method) {
            case 'GET':
                if (strpos($path, '/history') !== false) {
                    $this->getPriceHistory();
                } else {
                    $this->getProducts();
                }
                break;
            case 'POST':
                $this->addProduct();
                break;
            case 'PUT':
                $this->setPriceAlert();
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }
    
    private function getProducts() {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       ph.price as latest_price,
                       ph.created_at as last_updated,
                       (SELECT MIN(price) FROM price_history WHERE product_id = p.id) as min_price,
                       (SELECT MAX(price) FROM price_history WHERE product_id = p.id) as max_price,
                       (SELECT AVG(price) FROM price_history WHERE product_id = p.id) as avg_price
                FROM products p 
                LEFT JOIN price_history ph ON p.id = ph.product_id 
                WHERE ph.id = (
                    SELECT MAX(id) FROM price_history WHERE product_id = p.id
                )
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format products for frontend
            $formattedProducts = array_map([$this, 'formatProduct'], $products);
            
            echo json_encode([
                'success' => true,
                'products' => $formattedProducts,
                'count' => count($formattedProducts)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
        }
    }
    
    private function addProduct() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $url = $input['url'] ?? '';
            
            if (!$url) {
                throw new Exception('URL is required');
            }
            
            // Extract ASIN from URL
            $asin = $this->extractASIN($url);
            if (!$asin) {
                throw new Exception('Invalid Amazon URL or ASIN');
            }
            
            // Check if product already exists
            $stmt = $this->db->prepare("SELECT id FROM products WHERE asin = ?");
            $stmt->execute([$asin]);
            if ($stmt->fetch()) {
                throw new Exception('Product already exists');
            }
            
            // Scrape product data
            $productData = $this->scraper->scrapeProduct($asin);
            if (!$productData) {
                throw new Exception('Failed to scrape product data');
            }
            
            // Insert product
            $stmt = $this->db->prepare("
                INSERT INTO products (asin, title, current_price, original_price, image_url, url, brand, rating, review_count, availability) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $asin,
                $productData['title'],
                $productData['price'],
                $productData['original_price'],
                $productData['images'][0] ?? '',
                $productData['url'],
                $productData['brand'],
                $productData['rating'],
                $productData['review_count'],
                $productData['availability']
            ]);
            
            $productId = $this->db->lastInsertId();
            
            // Insert initial price history
            $stmt = $this->db->prepare("
                INSERT INTO price_history (product_id, price) VALUES (?, ?)
            ");
            $stmt->execute([$productId, $productData['price']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added successfully',
                'product_id' => $productId,
                'asin' => $asin,
                'redirect_url' => "product-detail.php?asin={$asin}"
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function getPriceHistory() {
        try {
            $asin = $_GET['asin'] ?? '';
            if (!$asin) {
                throw new Exception('ASIN is required');
            }
            
            $stmt = $this->db->prepare("
                SELECT ph.price, ph.created_at, p.title
                FROM price_history ph
                JOIN products p ON ph.product_id = p.id
                WHERE p.asin = ?
                ORDER BY ph.created_at ASC
            ");
            $stmt->execute([$asin]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'history' => $history,
                'count' => count($history)
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function setPriceAlert() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $asin = $input['asin'] ?? '';
            $targetPrice = $input['target_price'] ?? 0;
            
            if (!$asin || !$targetPrice) {
                throw new Exception('ASIN and target price are required');
            }
            
            // Get product ID
            $stmt = $this->db->prepare("SELECT id FROM products WHERE asin = ?");
            $stmt->execute([$asin]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Insert or update price alert
            $stmt = $this->db->prepare("
                INSERT INTO price_alerts (product_id, target_price, is_active) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE target_price = ?, is_active = 1
            ");
            $stmt->execute([$product['id'], $targetPrice, $targetPrice]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Price alert set successfully'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function extractASIN($url) {
        // Handle direct ASIN
        if (preg_match('/^[A-Z0-9]{10}$/', $url)) {
            return $url;
        }
        
        // Extract from various Amazon URL formats
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',
            '/\/gp\/product\/([A-Z0-9]{10})/',
            '/\/product\/([A-Z0-9]{10})/',
            '/asin=([A-Z0-9]{10})/',
            '/\/([A-Z0-9]{10})(?:\/|\?|$)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    private function formatProduct($product) {
        $dealScore = $this->calculateDealScore(
            $product['current_price'],
            $product['min_price'],
            $product['max_price'],
            $product['avg_price']
        );
        
        return [
            'id' => $product['id'],
            'asin' => $product['asin'],
            'title' => $product['title'],
            'current_price' => floatval($product['current_price']),
            'original_price' => floatval($product['original_price']),
            'min_price' => floatval($product['min_price']),
            'max_price' => floatval($product['max_price']),
            'avg_price' => round(floatval($product['avg_price']), 2),
            'image_url' => $product['image_url'],
            'url' => $product['url'],
            'affiliate_url' => getAffiliateUrl($product['url']),
            'brand' => $product['brand'],
            'rating' => floatval($product['rating']),
            'review_count' => intval($product['review_count']),
            'availability' => $product['availability'],
            'deal_score' => $dealScore,
            'discount_percent' => $this->calculateDiscount($product['current_price'], $product['original_price']),
            'last_updated' => $product['last_updated'],
            'is_lowest' => $product['current_price'] <= $product['min_price'],
            'price_trend' => $this->getPriceTrend($product['id'])
        ];
    }
    
    private function calculateDealScore($current, $min, $max, $avg) {
        $score = 0;
        
        // At all-time low (35 points)
        if ($current <= $min) $score += 35;
        
        // At 6-month low (29 points)
        if ($current <= $min * 1.05) $score += 29;
        
        // Below average (21 points)
        if ($current < $avg) $score += 21;
        
        // No price hike before sale (12 points)
        $score += 12;
        
        return min($score, 100);
    }
    
    private function calculateDiscount($current, $original) {
        if (!$original || $original <= $current) return 0;
        return round((($original - $current) / $original) * 100);
    }
    
    private function getPriceTrend($productId) {
        try {
            $stmt = $this->db->prepare("
                SELECT price FROM price_history 
                WHERE product_id = ? 
                ORDER BY created_at DESC 
                LIMIT 2
            ");
            $stmt->execute([$productId]);
            $prices = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($prices) < 2) return 'stable';
            
            $current = $prices[0];
            $previous = $prices[1];
            
            if ($current < $previous) return 'down';
            if ($current > $previous) return 'up';
            return 'stable';
            
        } catch (Exception $e) {
            return 'stable';
        }
    }
}

// Handle the request
$api = new EnhancedProductsAPI();
$api->handleRequest();
?>