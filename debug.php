<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['HTTP_HOST'] . "<br>";

// Test database connection
try {
    require_once 'backend/config/config.php';
    echo "Config loaded successfully<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "DB_USER: " . DB_USER . "<br>";
    
    require_once 'backend/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>