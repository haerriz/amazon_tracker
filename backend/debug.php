<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check products count
    $stmt = $db->query("SELECT COUNT(*) FROM products");
    $productCount = $stmt->fetchColumn();
    
    echo json_encode([
        'status' => 'OK',
        'database' => 'Connected',
        'tables' => $tables,
        'product_count' => $productCount,
        'enhanced_table_exists' => in_array('enhanced_products', $tables)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ]);
}
?>