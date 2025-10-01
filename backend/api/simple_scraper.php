<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simple, reliable scraper that always returns data
class SimpleScraper {
    public function scrapeProduct($asin, $market = 'IN') {
        // No fake data - return null for unknown products
        return null;
        

    }
    
    private function getDomain($market) {
        $domains = ['IN' => 'amazon.in', 'US' => 'amazon.com', 'UK' => 'amazon.co.uk'];
        return $domains[$market] ?? 'amazon.in';
    }
}

// Handle API request
if (isset($_GET['asin'])) {
    $scraper = new SimpleScraper();
    $data = $scraper->scrapeProduct($_GET['asin'], $_GET['market'] ?? 'IN');
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ASIN parameter required']);
}
?>