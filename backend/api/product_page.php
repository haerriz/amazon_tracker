<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/config.php';
require_once '../config/database.php';

class ProductPageAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                return $this->getProductData();
            case 'PUT':
                return $this->updateAlert();
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function getProductData() {
        $asin = $_GET['asin'] ?? null;
        if (!$asin) {
            return ['error' => 'ASIN required'];
        }
        
        // Get product
        $stmt = $this->db->prepare("SELECT * FROM products WHERE asin = ?");
        $stmt->execute([$asin]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['error' => 'Product not found'];
        }
        
        // Get price history
        $stmt = $this->db->prepare("SELECT price, created_at FROM price_history WHERE asin = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$asin]);
        $priceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate stats
        $stats = $this->calculateStats($priceHistory, $product['current_price']);
        
        return [
            'success' => true,
            'product' => $product,
            'priceHistory' => $priceHistory,
            'stats' => $stats,
            'dealScore' => $this->calculateDealScore($product, $stats)
        ];
    }
    
    private function updateAlert() {
        $input = json_decode(file_get_contents('php://input'), true);
        $asin = $input['asin'] ?? null;
        $targetPrice = $input['target_price'] ?? null;
        
        if (!$asin || !$targetPrice) {
            return ['error' => 'ASIN and target price required'];
        }
        
        $stmt = $this->db->prepare("UPDATE products SET target_price = ? WHERE asin = ?");
        $success = $stmt->execute([$targetPrice, $asin]);
        
        return ['success' => $success];
    }
    
    private function calculateStats($priceHistory, $currentPrice) {
        if (empty($priceHistory)) return null;
        
        $prices = array_column($priceHistory, 'price');
        
        return [
            'highest' => max($prices),
            'lowest' => min($prices),
            'average' => round(array_sum($prices) / count($prices)),
            'current' => $currentPrice,
            'isLowest' => $currentPrice <= min($prices),
            'isHighest' => $currentPrice >= max($prices)
        ];
    }
    
    private function calculateDealScore($product, $stats) {
        if (!$stats) return 50;
        
        $score = 0;
        
        // Price position (40 points max)
        if ($stats['current'] <= $stats['lowest']) {
            $score += 40; // At lowest price
        } else {
            $priceRange = $stats['highest'] - $stats['lowest'];
            if ($priceRange > 0) {
                $position = ($stats['highest'] - $stats['current']) / $priceRange;
                $score += $position * 40;
            }
        }
        
        // Discount percentage (30 points max)
        if ($product['original_price'] && $product['original_price'] > $product['current_price']) {
            $discount = (($product['original_price'] - $product['current_price']) / $product['original_price']) * 100;
            $score += min($discount * 0.6, 30);
        }
        
        // Availability and rating (30 points max)
        if ($product['in_stock']) $score += 15;
        if ($product['rating'] && $product['rating'] >= 4) $score += 15;
        
        return min(round($score), 100);
    }
}

$api = new ProductPageAPI();
echo json_encode($api->handleRequest());
?>