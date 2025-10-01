<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    require_once '../config/database.php';
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    $asin = $input['asin'] ?? '';
    $market = $input['market'] ?? 'IN';
    
    if (!$asin) {
        http_response_code(400);
        echo json_encode(['error' => 'ASIN required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists
    $stmt = $db->prepare("SELECT id FROM products WHERE asin = ? AND market = ?");
    $stmt->execute([$asin, $market]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Product already exists']);
        exit;
    }
    
    // For now, return error since we only want real data
    http_response_code(400);
    echo json_encode([
        'error' => 'Real-time Amazon scraping is currently unavailable. Only authentic product data is supported.',
        'message' => 'Please try again later when scraping service is restored.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred']);
}
?>