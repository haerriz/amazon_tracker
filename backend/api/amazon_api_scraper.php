<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class AmazonAPIScraper {
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];
    
    public function scrapeProduct($asin, $market = 'IN') {
        $domain = $this->getDomain($market);
        $url = "https://{$domain}/dp/{$asin}";
        
        // Only return real scraped data - no fake data
        $data = $this->scrapeWithAdvancedCurl($url, $asin, $market);
        
        if ($data && $data['title'] && $data['price']) {
            return $data;
        }
        
        // Return null if no real data found
        return null;
    }
    
    private function scrapeWithAdvancedCurl($url, $asin, $market) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)],
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,hi;q=0.8',
                'Accept-Encoding: gzip, deflate, br',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip, deflate'
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $html && strlen($html) > 5000) {
            return $this->parseAmazonHTML($html, $asin, $url);
        }
        
        return null;
    }
    
    private function parseAmazonHTML($html, $asin, $url) {
        $data = [
            'asin' => $asin,
            'title' => $this->extractTitle($html),
            'price' => $this->extractPrice($html),
            'original_price' => $this->extractOriginalPrice($html),
            'discount' => null,
            'rating' => $this->extractRating($html),
            'review_count' => $this->extractReviewCount($html),
            'availability' => $this->extractAvailability($html),
            'brand' => $this->extractBrand($html),
            'images' => $this->extractImages($html, $asin),
            'url' => $url,
            'scraped_at' => date('Y-m-d H:i:s')
        ];
        
        // Calculate discount if both prices available
        if ($data['price'] && $data['original_price'] && $data['original_price'] > $data['price']) {
            $data['discount'] = round((($data['original_price'] - $data['price']) / $data['original_price']) * 100);
        }
        
        return $data;
    }
    
    private function extractTitle($html) {
        $patterns = [
            // Main product title
            '/<span[^>]*id="productTitle"[^>]*>\s*([^<]+?)\s*<\/span>/i',
            '/<h1[^>]*class="[^"]*product[^"]*title[^"]*"[^>]*>\s*([^<]+?)\s*<\/h1>/i',
            
            // Alternative title patterns
            '/<span[^>]*class="[^"]*product[^"]*title[^"]*"[^>]*>\s*([^<]+?)\s*<\/span>/i',
            '/<div[^>]*id="title_feature_div"[^>]*>.*?<span[^>]*>([^<]+?)<\/span>/s',
            
            // Page title fallback
            '/<title>\s*([^<]+?)\s*[-:]\s*Amazon/i',
            '/<title>\s*([^<]+?)\s*\|\s*Amazon/i',
            
            // JSON-LD structured data
            '/"name"\s*:\s*"([^"]+)"/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
                $title = preg_replace('/\s+/', ' ', $title);
                
                // Clean up common Amazon suffixes
                $title = preg_replace('/\s*[-:]\s*Amazon\.(in|com|co\.uk).*$/i', '', $title);
                $title = preg_replace('/\s*\|\s*Amazon.*$/i', '', $title);
                $title = preg_replace('/\s*-\s*Buy.*$/i', '', $title);
                
                if (strlen($title) > 10 && strlen($title) < 500) {
                    return $title;
                }
            }
        }
        return null;
    }
    
    private function extractPrice($html) {
        $patterns = [
            // 2024 Amazon price patterns - most specific first
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9,]+)<\/span><span[^>]*class="[^"]*a-price-fraction[^"]*"[^>]*>([0-9]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/',
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9,]+)<\/span>/',
            
            // Deal and current price
            '/<span[^>]*id="priceblock_dealprice"[^>]*>₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/',
            '/<span[^>]*id="priceblock_ourprice"[^>]*>₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/',
            '/<span[^>]*class="[^"]*a-price[^"]*"[^>]*>.*?₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/s',
            
            // Price display patterns
            '/<div[^>]*class="[^"]*a-section[^"]*"[^>]*>.*?₹\s*([0-9,]+(?:\.[0-9]{2})?)/s',
            '/<span[^>]*data-a-size="xl"[^>]*>.*?₹\s*([0-9,]+(?:\.[0-9]{2})?)/s',
            
            // JSON price data
            '/"price"\s*:\s*"?₹?\s*([0-9,]+(?:\.[0-9]{2})?)"?/i',
            '/"priceAmount"\s*:\s*([0-9,]+(?:\.[0-9]{2})?)/i',
            
            // Fallback currency patterns
            '/₹\s*([0-9,]+(?:\.[0-9]{2})?)/i',
            '/INR\s*([0-9,]+(?:\.[0-9]{2})?)/i',
            '/Rs\.?\s*([0-9,]+(?:\.[0-9]{2})?)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                if (isset($matches[2])) {
                    $price = $matches[1] . '.' . $matches[2];
                } else {
                    $price = $matches[1];
                }
                
                $price = str_replace(',', '', $price);
                $price = floatval($price);
                
                if ($price > 0 && $price < 10000000) {
                    return $price;
                }
            }
        }
        
        return null;
    }
    
    private function extractOriginalPrice($html) {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price[^"]*a-text-price[^"]*"[^>]*><span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/',
            '/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/',
            '/M\.R\.P\.:?\s*₹\s*([0-9,]+(?:\.[0-9]{2})?)/i',
            '/<span[^>]*id="listPrice"[^>]*>₹\s*([0-9,]+(?:\.[0-9]{2})?)<\/span>/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = str_replace(',', '', $matches[1]);
                $price = floatval($price);
                if ($price > 0) return $price;
            }
        }
        
        return null;
    }
    
    private function extractRating($html) {
        $patterns = [
            '/(\d+\.?\d*)\s*out of 5 stars/i',
            '/"ratingValue":\s*(\d+\.?\d*)/i',
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>(\d+\.?\d*) out of/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $rating = floatval($matches[1]);
                if ($rating >= 1 && $rating <= 5) {
                    return $rating;
                }
            }
        }
        
        return null;
    }
    
    private function extractReviewCount($html) {
        $patterns = [
            '/([\d,]+)\s*ratings?/i',
            '/([\d,]+)\s*customer reviews?/i',
            '/<span[^>]*id="acrCustomerReviewText"[^>]*>([\d,]+) ratings?<\/span>/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $count = str_replace(',', '', $matches[1]);
                $count = intval($count);
                if ($count > 0) return $count;
            }
        }
        
        return null;
    }
    
    private function extractAvailability($html) {
        if (preg_match('/<div[^>]*id="availability"[^>]*>.*?<span[^>]*>([^<]+)<\/span>/s', $html, $matches)) {
            $availability = trim(strip_tags($matches[1]));
            if (stripos($availability, 'in stock') !== false) return 'In Stock';
            if (stripos($availability, 'out of stock') !== false) return 'Out of Stock';
            if (stripos($availability, 'temporarily') !== false) return 'Temporarily Unavailable';
            return $availability;
        }
        return 'In Stock';
    }
    
    private function extractBrand($html) {
        $patterns = [
            '/by\s+<a[^>]*>([^<]+)<\/a>/i',
            '/<span[^>]*class="[^"]*po-break-word[^"]*"[^>]*>([^<]+)<\/span>/i',
            '/Brand:\s*([^<\n]+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $brand = trim(strip_tags($matches[1]));
                if (strlen($brand) > 1 && strlen($brand) < 100) {
                    return $brand;
                }
            }
        }
        
        return null;
    }
    
    private function extractImages($html, $asin) {
        $images = [];
        
        // Try to extract from image data
        if (preg_match_all('/"large":"([^"]+)"/i', $html, $matches)) {
            foreach ($matches[1] as $imageUrl) {
                $imageUrl = str_replace('\/', '/', $imageUrl);
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $images[] = $imageUrl;
                }
            }
        }
        
        // Fallback to standard Amazon image URLs
        if (empty($images)) {
            for ($i = 1; $i <= 5; $i++) {
                $images[] = "https://images-na.ssl-images-amazon.com/images/P/{$asin}.0{$i}.L.jpg";
            }
        }
        
        return array_slice($images, 0, 5);
    }
    
    private function generateRealisticData($asin, $market, $url) {
        // Return null - no fake data, only real scraping
        return null;
    }
    
    private function generateRealisticTitle($asin, $category) {
        $titles = [
            'Fashion' => [
                'Men\'s Cotton Checkered Trousers Expandable Waist',
                'Women\'s Stretchable Formal Pants',
                'Unisex Casual Wear Trousers',
                'Premium Cotton Blend Pants'
            ],
            'Electronics' => [
                'Wireless Bluetooth Headphones',
                'Smart Phone Accessories',
                'Portable Charger Power Bank',
                'LED Smart TV'
            ],
            'Home & Kitchen' => [
                'Non-Stick Cookware Set',
                'Kitchen Storage Container',
                'Home Decor Items',
                'Dining Table Set'
            ],
            'General' => [
                'Premium Quality Product',
                'Best Seller Item',
                'Top Rated Product',
                'Customer Favorite'
            ]
        ];
        
        $categoryTitles = $titles[$category] ?? $titles['General'];
        $baseTitle = $categoryTitles[array_rand($categoryTitles)];
        
        return $baseTitle . ' (' . $asin . ')';
    }
    
    private function getDomain($market) {
        $domains = ['IN' => 'amazon.in', 'US' => 'amazon.com', 'UK' => 'amazon.co.uk'];
        return $domains[$market] ?? 'amazon.in';
    }
}

// Handle API request
if (isset($_GET['asin'])) {
    $scraper = new AmazonAPIScraper();
    $data = $scraper->scrapeProduct($_GET['asin'], $_GET['market'] ?? 'IN');
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ASIN parameter required']);
}
?>