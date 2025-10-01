<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

class ProductPage {
    private $db;
    private $product;
    private $priceHistory;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadProduct();
    }
    
    private function loadProduct() {
        $asin = $this->getASINFromURL();
        if (!$asin) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM products WHERE asin = ?");
        $stmt->execute([$asin]);
        $this->product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$this->product) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
        
        $this->loadPriceHistory();
    }
    
    private function getASINFromURL() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Extract ASIN from URL patterns like /product/B09G9BL5CP or /amazon-product-name-B09G9BL5CP
        if (preg_match('/([A-Z0-9]{10})/', $path, $matches)) {
            return $matches[1];
        }
        return $_GET['asin'] ?? null;
    }
    
    private function loadPriceHistory() {
        $stmt = $this->db->prepare("SELECT price, created_at FROM price_history WHERE asin = ? ORDER BY created_at DESC LIMIT 30");
        $stmt->execute([$this->product['asin']]);
        $this->priceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function render() {
        $title = $this->product['title'] . ' - Price History & Tracker';
        $description = "Track price history for " . $this->product['title'] . ". Current price: ₹" . number_format($this->product['current_price']) . ". Get price alerts and best deals.";
        $canonicalUrl = "https://" . $_SERVER['HTTP_HOST'] . "/product/" . $this->product['asin'];
        
        // Make $this available in template
        $productPage = $this;
        include 'templates/product-page.php';
    }
    
    public function getProductData() {
        return [
            'product' => $this->product,
            'priceHistory' => $this->priceHistory,
            'stats' => $this->calculateStats()
        ];
    }
    
    private function calculateStats() {
        if (empty($this->priceHistory)) return null;
        
        $prices = array_column($this->priceHistory, 'price');
        $currentPrice = $this->product['current_price'];
        
        return [
            'highest' => max($prices),
            'lowest' => min($prices),
            'average' => round(array_sum($prices) / count($prices)),
            'discount' => $this->product['original_price'] ? 
                round((($this->product['original_price'] - $currentPrice) / $this->product['original_price']) * 100) : 0
        ];
    }
}

$productPage = new ProductPage();
$productPage->render();
?>