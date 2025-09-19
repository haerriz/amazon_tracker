<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>API Test</h2>";

// Test basic API endpoint
echo "<h3>Testing GET /backend/api/products.php</h3>";
$url = 'https://amazon-tracker.haerriz.com/backend/api/products.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "<br>";
echo "Response: " . htmlspecialchars($response) . "<br>";

// Test database setup
echo "<h3>Testing Database Setup</h3>";
$setupUrl = 'https://amazon-tracker.haerriz.com/backend/setup.php';
echo "<a href='$setupUrl' target='_blank'>Run Setup Script</a><br>";
?>