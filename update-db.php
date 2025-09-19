<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

echo "<h2>Database Update</h2>\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Add url column if it doesn't exist
    $sql = "ALTER TABLE products ADD COLUMN url VARCHAR(500) AFTER image_url";
    try {
        $conn->exec($sql);
        echo "✓ Added url column to products table<br>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ URL column already exists<br>\n";
        } else {
            throw $e;
        }
    }
    
    // Update existing products with affiliate URLs
    $stmt = $conn->prepare("SELECT id, asin, market FROM products WHERE url IS NULL OR url = ''");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $affiliateUrl = generateAffiliateUrl($product['asin'], $product['market']);
        $updateStmt = $conn->prepare("UPDATE products SET url = ? WHERE id = ?");
        $updateStmt->execute([$affiliateUrl, $product['id']]);
    }
    
    echo "✓ Updated " . count($products) . " products with affiliate URLs<br>\n";
    echo "<h3>✅ Database Update Complete!</h3>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Update Failed</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}

function generateAffiliateUrl($asin, $market) {
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
?>