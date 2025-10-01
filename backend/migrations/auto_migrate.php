<?php
require_once '../config/database.php';

class AutoMigration {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function runMigrations() {
        $this->createMigrationsTable();
        $this->migration001_CreateBasicTables();
        $this->migration002_CreateEnhancedTables();
        $this->migration003_AddIndexes();
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }
    
    private function migrationExists($name) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM migrations WHERE migration_name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function markMigrationComplete($name) {
        $stmt = $this->db->prepare("INSERT IGNORE INTO migrations (migration_name) VALUES (?)");
        $stmt->execute([$name]);
    }
    
    private function migration001_CreateBasicTables() {
        if ($this->migrationExists('001_create_basic_tables')) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asin VARCHAR(20) NOT NULL,
            market VARCHAR(5) NOT NULL DEFAULT 'IN',
            title TEXT,
            image_url TEXT,
            current_price DECIMAL(10,2),
            url TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_product (asin, market)
        )";
        $this->db->exec($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS price_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";
        $this->db->exec($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS price_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            target_price DECIMAL(10,2) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_alert (product_id)
        )";
        $this->db->exec($sql);
        
        $this->markMigrationComplete('001_create_basic_tables');
    }
    
    private function migration002_CreateEnhancedTables() {
        if ($this->migrationExists('002_create_enhanced_tables')) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS enhanced_products (
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
            UNIQUE KEY unique_enhanced_product (product_id)
        )";
        $this->db->exec($sql);
        
        $this->markMigrationComplete('002_create_enhanced_tables');
    }
    
    private function migration003_AddIndexes() {
        if ($this->migrationExists('003_add_indexes')) return;
        
        $indexes = [
            "ALTER TABLE products ADD INDEX idx_asin (asin)",
            "ALTER TABLE products ADD INDEX idx_market (market)",
            "ALTER TABLE price_history ADD INDEX idx_product_time (product_id, timestamp)",
            "ALTER TABLE enhanced_products ADD INDEX idx_rating (rating)"
        ];
        
        foreach ($indexes as $sql) {
            try {
                $this->db->exec($sql);
            } catch (Exception $e) {
                // Index might already exist
            }
        }
        
        $this->markMigrationComplete('003_add_indexes');
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $migration = new AutoMigration();
    $migration->runMigrations();
    echo "Migrations completed!";
}
?>