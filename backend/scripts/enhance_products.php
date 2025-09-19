<?php
/**
 * Product Enhancement Script
 * Updates existing products with comprehensive data from enhanced scraper
 */

ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

require_once '../config/database.php';
require_once '../api/enhanced_scraper.php';

class ProductEnhancer {
    private $db;
    private $enhancedScraper;
    private $batchSize = 10;
    private $delayBetweenRequests = 2; // seconds
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->enhancedScraper = new EnhancedProductScraper();
        
        // Create enhanced_products table if it doesn't exist
        $this->createEnhancedProductsTable();
    }
    
    public function enhanceAllProducts() {
        echo "Starting product enhancement process...\n";
        
        // Get all products that need enhancement
        $stmt = $this->db->prepare("
            SELECT p.id, p.asin, p.market, p.title, p.current_price 
            FROM products p 
            LEFT JOIN enhanced_products ep ON p.id = ep.product_id 
            WHERE ep.product_id IS NULL OR ep.updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = count($products);
        echo "Found {$total} products to enhance.\n";
        
        $processed = 0;
        $enhanced = 0;
        $failed = 0;
        
        foreach (array_chunk($products, $this->batchSize) as $batch) {
            foreach ($batch as $product) {
                try {
                    echo "Enhancing product {$product['asin']} ({$product['market']})... ";
                    
                    $enhancedData = $this->enhancedScraper->getEnhancedProductData(
                        $product['asin'], 
                        $product['market']
                    );
                    
                    if ($enhancedData) {
                        $this->saveEnhancedData($product['id'], $enhancedData);
                        $this->updateProductWithEnhancedData($product['id'], $enhancedData);
                        echo "✓ Enhanced\n";
                        $enhanced++;
                    } else {
                        echo "✗ Failed to get data\n";
                        $failed++;
                    }
                    
                    $processed++;
                    
                    // Progress update
                    if ($processed % 10 === 0) {
                        echo "Progress: {$processed}/{$total} ({$enhanced} enhanced, {$failed} failed)\n";
                    }
                    
                    // Rate limiting
                    sleep($this->delayBetweenRequests);
                    
                } catch (Exception $e) {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                    $failed++;
                    $processed++;
                }
            }
            
            // Longer pause between batches
            if ($processed < $total) {
                echo "Batch completed. Pausing for 10 seconds...\n";
                sleep(10);
            }
        }
        
        echo "\nEnhancement completed!\n";
        echo "Total processed: {$processed}\n";
        echo "Successfully enhanced: {$enhanced}\n";
        echo "Failed: {$failed}\n";
        
        return [
            'total' => $total,
            'processed' => $processed,
            'enhanced' => $enhanced,
            'failed' => $failed
        ];
    }
    
    public function enhanceSpecificProduct($asin, $market = 'IN') {
        echo "Enhancing specific product: {$asin} ({$market})\n";
        
        // Get product ID
        $stmt = $this->db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
        $stmt->execute([$asin, $market]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo "Product not found in database.\n";
            return false;
        }
        
        try {
            $enhancedData = $this->enhancedScraper->getEnhancedProductData($asin, $market);
            
            if ($enhancedData) {
                $this->saveEnhancedData($product['id'], $enhancedData);
                $this->updateProductWithEnhancedData($product['id'], $enhancedData);
                echo "✓ Product enhanced successfully\n";
                return true;
            } else {
                echo "✗ Failed to get enhanced data\n";
                return false;
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function createEnhancedProductsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS enhanced_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                rating DECIMAL(3,2),
                review_count INT,
                discount_percentage INT,
                original_price DECIMAL(10,2),
                availability VARCHAR(100),
                brand VARCHAR(100),
                category JSON,
                features JSON,
                variants JSON,
                images JSON,
                description TEXT,
                specifications JSON,
                seller VARCHAR(100),
                prime_eligible BOOLEAN DEFAULT FALSE,
                delivery_info TEXT,
                coupon VARCHAR(100),
                price_analysis JSON,
                market_insights JSON,
                recommendation JSON,
                tracking_metrics JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->exec($sql);
    }
    
    private function saveEnhancedData($productId, $enhancedData) {
        $sql = "
            INSERT INTO enhanced_products (
                product_id, rating, review_count, discount_percentage, original_price,
                availability, brand, category, features, variants, images, description,
                specifications, seller, prime_eligible, delivery_info, coupon,
                price_analysis, market_insights, recommendation, tracking_metrics
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                review_count = VALUES(review_count),
                discount_percentage = VALUES(discount_percentage),
                original_price = VALUES(original_price),
                availability = VALUES(availability),
                brand = VALUES(brand),
                category = VALUES(category),
                features = VALUES(features),
                variants = VALUES(variants),
                images = VALUES(images),
                description = VALUES(description),
                specifications = VALUES(specifications),
                seller = VALUES(seller),
                prime_eligible = VALUES(prime_eligible),
                delivery_info = VALUES(delivery_info),
                coupon = VALUES(coupon),
                price_analysis = VALUES(price_analysis),
                market_insights = VALUES(market_insights),
                recommendation = VALUES(recommendation),
                tracking_metrics = VALUES(tracking_metrics),
                updated_at = CURRENT_TIMESTAMP
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $productId,
            $enhancedData['rating'] ?? null,
            $enhancedData['review_count'] ?? null,
            $enhancedData['discount'] ?? null,
            $enhancedData['original_price'] ?? null,
            $enhancedData['availability'] ?? null,
            $enhancedData['brand'] ?? null,
            json_encode($enhancedData['category'] ?? []),
            json_encode($enhancedData['features'] ?? []),
            json_encode($enhancedData['variants'] ?? []),
            json_encode($enhancedData['images'] ?? []),
            $enhancedData['description'] ?? null,
            json_encode($enhancedData['specifications'] ?? []),
            $enhancedData['seller'] ?? null,
            $enhancedData['prime_eligible'] ?? false,
            $enhancedData['delivery_info'] ?? null,
            $enhancedData['coupon'] ?? null,
            json_encode($enhancedData['price_analysis'] ?? []),
            json_encode($enhancedData['market_insights'] ?? []),
            json_encode($enhancedData['recommendation'] ?? []),
            json_encode($enhancedData['tracking_metrics'] ?? [])
        ]);
    }
    
    private function updateProductWithEnhancedData($productId, $enhancedData) {
        // Update main products table with key enhanced data
        $sql = "
            UPDATE products SET 
                current_price = COALESCE(?, current_price),
                image_url = COALESCE(?, image_url),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $enhancedData['price'] ?? null,
            isset($enhancedData['images'][0]) ? $enhancedData['images'][0] : null,
            $productId
        ]);
    }
    
    public function getEnhancementStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_products,
                COUNT(ep.id) as enhanced_products,
                AVG(ep.rating) as avg_rating,
                AVG(ep.review_count) as avg_reviews,
                COUNT(CASE WHEN ep.prime_eligible = 1 THEN 1 END) as prime_eligible_count
            FROM products p
            LEFT JOIN enhanced_products ep ON p.id = ep.product_id
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $enhancer = new ProductEnhancer();
    
    if (isset($argv[1])) {
        if ($argv[1] === 'stats') {
            $stats = $enhancer->getEnhancementStats();
            echo "Enhancement Statistics:\n";
            echo "Total Products: " . $stats['total_products'] . "\n";
            echo "Enhanced Products: " . $stats['enhanced_products'] . "\n";
            echo "Average Rating: " . round($stats['avg_rating'], 2) . "\n";
            echo "Average Reviews: " . round($stats['avg_reviews']) . "\n";
            echo "Prime Eligible: " . $stats['prime_eligible_count'] . "\n";
        } elseif ($argv[1] === 'single' && isset($argv[2])) {
            $market = $argv[3] ?? 'IN';
            $enhancer->enhanceSpecificProduct($argv[2], $market);
        } else {
            echo "Usage:\n";
            echo "  php enhance_products.php           - Enhance all products\n";
            echo "  php enhance_products.php stats     - Show enhancement statistics\n";
            echo "  php enhance_products.php single ASIN [MARKET] - Enhance specific product\n";
        }
    } else {
        $enhancer->enhanceAllProducts();
    }
} else {
    // Web execution
    header('Content-Type: application/json');
    
    $enhancer = new ProductEnhancer();
    $action = $_GET['action'] ?? 'enhance';
    
    switch ($action) {
        case 'stats':
            echo json_encode($enhancer->getEnhancementStats());
            break;
        case 'single':
            $asin = $_GET['asin'] ?? '';
            $market = $_GET['market'] ?? 'IN';
            if ($asin) {
                $result = $enhancer->enhanceSpecificProduct($asin, $market);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['error' => 'ASIN required']);
            }
            break;
        default:
            $result = $enhancer->enhanceAllProducts();
            echo json_encode($result);
    }
}
?>