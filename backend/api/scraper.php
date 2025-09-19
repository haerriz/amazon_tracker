<?php
class AmazonScraper {
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ];

    public function fetchProductData($asin, $market) {
        $domain = $this->getDomain($market);
        $url = "https://{$domain}/dp/{$asin}";
        
        $html = $this->fetchPage($url);
        if (!$html) return null;

        return [
            'title' => $this->extractTitle($html),
            'price' => $this->extractPrice($html, $market),
            'image' => $this->extractImage($html, $asin)
        ];
    }

    private function getDomain($market) {
        $domains = [
            'IN' => 'amazon.in',
            'US' => 'amazon.com',
            'UK' => 'amazon.co.uk'
        ];
        return $domains[$market] ?? 'amazon.in';
    }

    private function fetchPage($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEJAR => '/tmp/cookies.txt',
            CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ]
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log for debugging
        error_log("Scraping $url - HTTP: $httpCode - Length: " . strlen($html));
        
        return ($httpCode === 200) ? $html : null;
    }

    private function extractTitle($html) {
        // Multiple patterns for title extraction
        $patterns = [
            '/<span[^>]*id="productTitle"[^>]*>\s*([^<]+?)\s*<\/span>/i',
            '/<h1[^>]*class="[^"]*product[^"]*title[^"]*"[^>]*>\s*([^<]+?)\s*<\/h1>/i',
            '/<title>\s*([^<]+?)\s*<\/title>/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
                // Clean up Amazon suffixes
                $title = preg_replace('/\s*[-:]\s*Amazon\.(com|in|co\.uk).*$/i', '', $title);
                $title = preg_replace('/\s*[-:]\s*Buy.*$/i', '', $title);
                if (strlen($title) > 10) { // Ensure we have a meaningful title
                    return $title;
                }
            }
        }
        return null;
    }

    private function extractPrice($html, $market) {
        $patterns = [
            // Current Amazon price patterns
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*id="priceblock_dealprice"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*id="priceblock_ourprice"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-price[^"]*"[^>]*>[^<]*<span[^>]*>([^<]+)<\/span>/',
            // Backup patterns
            '/₹\s*([\d,]+(?:\.\d{2})?)/i',
            '/INR\s*([\d,]+(?:\.\d{2})?)/i',
            '/Rs\.?\s*([\d,]+(?:\.\d{2})?)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = $matches[1];
                // Clean price string
                $price = preg_replace('/[^\d.,]/', '', $price);
                $price = str_replace(',', '', $price);
                $price = floatval($price);
                if ($price > 0) {
                    return $price;
                }
            }
        }
        return null;
    }

    private function extractImage($html, $asin) {
        // Try Amazon's standard image URL format
        return "https://images-na.ssl-images-amazon.com/images/P/{$asin}.01.L.jpg";
    }
}
?>