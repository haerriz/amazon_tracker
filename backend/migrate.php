<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create enhanced_products table
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
    
    $db->exec($sql);
    echo "Enhanced products table created successfully\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>