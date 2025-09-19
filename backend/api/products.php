<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once '../config/database.php';
    require_once 'scraper.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load dependencies: ' . $e->getMessage()]);
    exit;
}

class ProductAPI {
    private $db;
    private $scraper;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->scraper = new AmazonScraper();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));

        switch ($method) {
            case 'POST':
                if (end($segments) === 'add') {
                    $this->addProduct();
                }
                break;
            case 'GET':
                if (isset($segments[3])) {
                    $asin = $segments[3];
                    if (isset($segments[4]) && $segments[4] === 'history') {
                        $this->getHistory($asin);
                    } else {
                        $this->getProduct($asin);
                    }
                } else {
                    $this->getAllProducts();
                }
                break;
            case 'PUT':
                if (isset($segments[3]) && isset($segments[4]) && $segments[4] === 'alert') {
                    $this->setAlert($segments[3]);
                }
                break;
            case 'DELETE':
                if (isset($segments[3])) {
                    $this->deleteProduct($segments[3]);
                }
                break;
        }
    }

    private function addProduct() {
        $input = json_decode(file_get_contents('php://input'), true);
        $asin = $input['asin'] ?? '';
        $market = $input['market'] ?? 'IN';

        if (!$asin) {
            http_response_code(400);
            echo json_encode(['error' => 'ASIN required']);
            return;
        }

        // Check if product exists
        $stmt = $this->db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
        $stmt->execute([$asin, $market]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Product already exists']);
            return;
        }

        // Fetch product data
        $productData = $this->scraper->fetchProductData($asin, $market);
        if (!$productData) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch product data']);
            return;
        }

        // Generate affiliate URL
        $affiliateUrl = $this->generateAffiliateUrl($asin, $market);
        
        // Insert product
        $stmt = $this->db->prepare("INSERT INTO products (asin, market, title, image_url, current_price, url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $asin,
            $market,
            $productData['title'],
            $productData['image'],
            $productData['price'],
            $affiliateUrl
        ]);

        $productId = $this->db->lastInsertId();

        // Add initial price history
        if ($productData['price']) {
            $stmt = $this->db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
            $stmt->execute([$productId, $productData['price']]);
        }

        echo json_encode([
            'success' => true,
            'product' => [
                'id' => $productId,
                'asin' => $asin,
                'market' => $market,
                'title' => $productData['title'],
                'image' => $productData['image'],
                'price' => $productData['price'],
                'url' => $affiliateUrl
            ]
        ]);
    }

    private function getProduct($asin) {
        $market = $_GET['market'] ?? 'IN';
        
        $stmt = $this->db->prepare("SELECT * FROM products WHERE asin = ? AND market = ?");
        $stmt->execute([$asin, $market]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        echo json_encode($product);
    }

    private function getAllProducts() {
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY created_at DESC");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($products);
    }

    private function getHistory($asin) {
        $market = $_GET['market'] ?? 'IN';
        $days = $_GET['days'] ?? 30;

        $stmt = $this->db->prepare("
            SELECT ph.price, ph.timestamp 
            FROM price_history ph 
            JOIN products p ON ph.product_id = p.id 
            WHERE p.asin = ? AND p.market = ? AND ph.timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY ph.timestamp ASC
        ");
        $stmt->execute([$asin, $market, $days]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($history);
    }

    private function setAlert($asin) {
        $input = json_decode(file_get_contents('php://input'), true);
        $market = $input['market'] ?? 'IN';
        $targetPrice = $input['target_price'] ?? null;

        if (!$targetPrice) {
            http_response_code(400);
            echo json_encode(['error' => 'Target price required']);
            return;
        }

        $stmt = $this->db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
        $stmt->execute([$asin, $market]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO price_alerts (product_id, target_price) VALUES (?, ?) ON DUPLICATE KEY UPDATE target_price = ?");
        $stmt->execute([$product['id'], $targetPrice, $targetPrice]);

        echo json_encode(['success' => true]);
    }
    
    private function deleteProduct($asin) {
        $market = $_GET['market'] ?? 'IN';
        
        $stmt = $this->db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
        $stmt->execute([$asin, $market]);
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }
        
        $stmt = $this->db->prepare("DELETE FROM products WHERE asin = ? AND market = ?");
        $stmt->execute([$asin, $market]);
        
        echo json_encode(['success' => true]);
    }
    
    private function generateAffiliateUrl($asin, $market) {
        $domains = [
            'IN' => 'amazon.in',
            'US' => 'amazon.com',
            'UK' => 'amazon.co.uk'
        ];
        
        $affiliateTags = [
            'IN' => AFFILIATE_TAG_IN,
            'US' => AFFILIATE_TAG_US,
            'UK' => AFFILIATE_TAG_UK
        ];
        
        $domain = $domains[$market] ?? 'amazon.in';
        $tag = $affiliateTags[$market] ?? AFFILIATE_TAG_IN;
        
        return "https://{$domain}/dp/{$asin}?tag={$tag}";
    }
}

try {
    $api = new ProductAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>