<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Price Tracker Setup</h2>\n";
echo "<p>Environment: " . (isLocalEnvironment() ? 'Local Development' : 'Production Server') . "</p>\n";
echo "<p>Database: " . DB_NAME . "</p>\n";
echo "<p>Host: " . DB_HOST . "</p>\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h3>Creating Tables...</h3>\n";
    
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asin VARCHAR(10) NOT NULL,
        market VARCHAR(2) NOT NULL,
        title VARCHAR(500),
        image_url VARCHAR(500),
        current_price DECIMAL(10,2),
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_product (asin, market)
    )";
    $conn->exec($sql);
    echo "✓ Products table created<br>\n";
    
    // Create price_history table
    $sql = "CREATE TABLE IF NOT EXISTS price_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_product_timestamp (product_id, timestamp)
    )";
    $conn->exec($sql);
    echo "✓ Price history table created<br>\n";
    
    // Create price_alerts table
    $sql = "CREATE TABLE IF NOT EXISTS price_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        target_price DECIMAL(10,2) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "✓ Price alerts table created<br>\n";
    
    echo "<h3>✅ Setup Complete!</h3>\n";
    echo "<p>Your price tracker is ready to use.</p>\n";
    
    if (!isLocalEnvironment()) {
        echo "<h3>Cron Job Setup</h3>\n";
        echo "<p>Add this to your cPanel cron jobs:</p>\n";
        echo "<code>*/30 * * * * php " . $_SERVER['DOCUMENT_ROOT'] . "/backend/cron/update_prices.php</code>\n";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Setup Failed</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>