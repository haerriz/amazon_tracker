<?php
require_once 'backend/api/scraper.php';

$asin = 'B09G9BL5CP';
$market = 'IN';

echo "<h2>Scraper Test</h2>";
echo "<p>Testing ASIN: $asin (Market: $market)</p>";

$scraper = new AmazonScraper();
$result = $scraper->fetchProductData($asin, $market);

echo "<h3>Result:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result) {
    echo "<p><strong>Title:</strong> " . ($result['title'] ?? 'NULL') . "</p>";
    echo "<p><strong>Price:</strong> " . ($result['price'] ?? 'NULL') . "</p>";
    echo "<p><strong>Image:</strong> " . ($result['image'] ?? 'NULL') . "</p>";
} else {
    echo "<p><strong>Scraping failed!</strong></p>";
    
    // Test basic URL fetch
    echo "<h3>Testing basic URL fetch:</h3>";
    $url = "https://amazon.in/dp/$asin";
    echo "<p>URL: $url</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    echo "<p>Content Length: " . strlen($html) . "</p>";
    
    if ($httpCode === 200 && $html) {
        echo "<p>✅ URL fetch successful</p>";
        // Look for title in HTML
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            echo "<p>Found title: " . htmlspecialchars($matches[1]) . "</p>";
        }
    } else {
        echo "<p>❌ URL fetch failed</p>";
    }
}
?>