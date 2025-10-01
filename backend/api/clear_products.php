<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Clear all products and related data
    $db->exec("DELETE FROM price_history");
    $db->exec("DELETE FROM price_alerts");
    $db->exec("DELETE FROM products");
    
    // Reset auto increment
    $db->exec("ALTER TABLE products AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE price_history AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE price_alerts AUTO_INCREMENT = 1");
    
    echo json_encode([
        'success' => true,
        'message' => 'All products cleared successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Failed to clear products: ' . $e->getMessage()
    ]);
}
?>