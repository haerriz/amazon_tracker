<?php
require_once '../config/database.php';
require_once '../api/scraper.php';

class PriceUpdater {
    private $db;
    private $scraper;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->scraper = new AmazonScraper();
    }

    public function updateAllPrices() {
        echo "Starting price update...\n";
        
        $stmt = $this->db->prepare("SELECT id, asin, market, current_price FROM products");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $this->updateProductPrice($product);
            sleep(2); // Rate limiting
        }

        echo "Price update completed.\n";
    }

    private function updateProductPrice($product) {
        echo "Updating {$product['asin']}...\n";
        
        $productData = $this->scraper->fetchProductData($product['asin'], $product['market']);
        
        if (!$productData || !$productData['price']) {
            echo "Failed to fetch price for {$product['asin']}\n";
            return;
        }

        $newPrice = $productData['price'];
        $oldPrice = $product['current_price'];

        // Update current price
        $stmt = $this->db->prepare("UPDATE products SET current_price = ?, last_updated = NOW() WHERE id = ?");
        $stmt->execute([$newPrice, $product['id']]);

        // Add to price history
        $stmt = $this->db->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
        $stmt->execute([$product['id'], $newPrice]);

        // Check alerts
        $this->checkAlerts($product['id'], $newPrice);

        echo "Updated {$product['asin']}: {$oldPrice} -> {$newPrice}\n";
    }

    private function checkAlerts($productId, $currentPrice) {
        $stmt = $this->db->prepare("
            SELECT pa.target_price, p.title, p.asin 
            FROM price_alerts pa 
            JOIN products p ON pa.product_id = p.id 
            WHERE pa.product_id = ? AND pa.is_active = 1 AND pa.target_price >= ?
        ");
        $stmt->execute([$productId, $currentPrice]);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($alerts as $alert) {
            echo "ALERT: {$alert['title']} ({$alert['asin']}) hit target price {$alert['target_price']}\n";
            // Here you would send email/notification
        }
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    $updater = new PriceUpdater();
    $updater->updateAllPrices();
}
?>