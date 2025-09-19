<?php
class AmazonScraper {
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/121.0'
    ];
    
    private $proxies = [];
    private $requestDelay = 1;

    public function fetchProductData($asin, $market) {
        $domain = $this->getDomain($market);
        $url = "https://{$domain}/dp/{$asin}";
        
        $html = $this->fetchPage($url);
        if (!$html) return null;

        return [
            'asin' => $asin,
            'title' => $this->extractTitle($html),
            'price' => $this->extractPrice($html, $market),
            'original_price' => $this->extractOriginalPrice($html, $market),
            'discount' => $this->extractDiscount($html),
            'rating' => $this->extractRating($html),
            'review_count' => $this->extractReviewCount($html),
            'availability' => $this->extractAvailability($html),
            'brand' => $this->extractBrand($html),
            'category' => $this->extractCategory($html),
            'features' => $this->extractFeatures($html),
            'variants' => $this->extractVariants($html),
            'images' => $this->extractImages($html, $asin),
            'description' => $this->extractDescription($html),
            'specifications' => $this->extractSpecifications($html),
            'seller' => $this->extractSeller($html),
            'prime_eligible' => $this->extractPrimeEligible($html),
            'delivery_info' => $this->extractDeliveryInfo($html),
            'coupon' => $this->extractCoupon($html),
            'url' => $url,
            'scraped_at' => date('Y-m-d H:i:s')
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

    private function fetchPage($url, $retries = 3) {
        sleep($this->requestDelay);
        
        for ($i = 0; $i < $retries; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)],
                CURLOPT_TIMEOUT => 45,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIEJAR => '/tmp/amazon_cookies_' . session_id() . '.txt',
                CURLOPT_COOKIEFILE => '/tmp/amazon_cookies_' . session_id() . '.txt',
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9,hi;q=0.8',
                    'Accept-Encoding: gzip, deflate, br',
                    'DNT: 1',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Cache-Control: max-age=0'
                ]
            ]);
            
            if (!empty($this->proxies)) {
                $proxy = $this->proxies[array_rand($this->proxies)];
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
            }
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            error_log("Scraping attempt " . ($i + 1) . " for $url - HTTP: $httpCode - Length: " . strlen($html));
            
            if ($httpCode === 200 && $html) {
                return $html;
            }
            
            if ($i < $retries - 1) {
                sleep(pow(2, $i + 1)); // Exponential backoff
            }
        }
        
        return null;
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
        // Log HTML snippet for debugging
        error_log("Price extraction for market: $market");
        
        $patterns = [
            // 2024 Amazon price patterns - most specific first
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([\d,]+)<\/span><span[^>]*class="[^"]*a-price-fraction[^"]*"[^>]*>([\d]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>\s*₹?\s*([\d,]+(?:\.\d{2})?)[^<]*<\/span>/',
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([\d,]+)<\/span>/',
            
            // Deal prices
            '/<span[^>]*id="priceblock_dealprice"[^>]*>\s*₹?\s*([\d,]+(?:\.\d{2})?)[^<]*<\/span>/',
            '/<span[^>]*id="priceblock_ourprice"[^>]*>\s*₹?\s*([\d,]+(?:\.\d{2})?)[^<]*<\/span>/',
            
            // JSON data
            '/"priceAmount":\s*([\d.]+)/',
            '/"price":\s*"?([\d,]+(?:\.\d{2})?)"?/',
            
            // Text-based patterns (more flexible)
            '/₹\s*([\d,]+(?:\.\d{2})?)/i',
            '/INR\s*([\d,]+(?:\.\d{2})?)/i',
            '/Rs\.?\s*([\d,]+(?:\.\d{2})?)/i',
            '/\$\s*([\d,]+(?:\.\d{2})?)/i',
            '/£\s*([\d,]+(?:\.\d{2})?)/i'
        ];

        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                if (isset($matches[2])) {
                    $price = $matches[1] . '.' . $matches[2];
                } else {
                    $price = $matches[1];
                }
                
                // Clean and validate price
                $price = preg_replace('/[^\d.,]/', '', $price);
                $price = str_replace(',', '', $price);
                $price = floatval($price);
                
                if ($price > 0 && $price < 10000000) { // Reasonable range
                    error_log("Price found using pattern $i: $price");
                    return $price;
                }
            }
        }
        
        error_log("No price found in HTML");
        return null;
    }
    
    private function extractOriginalPrice($html, $market) {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price[^"]*a-text-price[^"]*"[^>]*><span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*id="listPrice"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-price-was[^"]*"[^>]*>([^<]+)<\/span>/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = preg_replace('/[^\d.,]/', '', $matches[1]);
                $price = str_replace(',', '', $price);
                $price = floatval($price);
                if ($price > 0) return $price;
            }
        }
        return null;
    }
    
    private function extractDiscount($html) {
        $patterns = [
            '/<span[^>]*class="[^"]*savingsPercentage[^"]*"[^>]*>-([\d]+)%<\/span>/',
            '/<span[^>]*class="[^"]*a-size-large[^"]*a-color-price[^"]*"[^>]*>\(([\d]+)% off\)<\/span>/',
            '/Save ([\d]+)%/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return intval($matches[1]);
            }
        }
        return null;
    }
    
    private function extractRating($html) {
        $patterns = [
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.]+) out of 5 stars<\/span>/',
            '/<i[^>]*class="[^"]*a-icon[^"]*a-star[^"]*"[^>]*><span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.]+) out of/',
            '/"ratingValue":\s*([\d.]+)/',
            '/data-hook="average-star-rating"[^>]*><span[^>]*>([\d.]+)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return floatval($matches[1]);
            }
        }
        return null;
    }
    
    private function extractReviewCount($html) {
        $patterns = [
            '/<span[^>]*id="acrCustomerReviewText"[^>]*>([\d,]+) ratings<\/span>/',
            '/<a[^>]*href="[^"]*#customerReviews"[^>]*>([\d,]+) customer reviews<\/a>/',
            '/"reviewCount":\s*([\d,]+)/',
            '/<span[^>]*data-hook="total-review-count"[^>]*>([\d,]+)<\/span>/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return intval(str_replace(',', '', $matches[1]));
            }
        }
        return null;
    }
    
    private function extractAvailability($html) {
        if (preg_match('/<div[^>]*id="availability"[^>]*>.*?<span[^>]*>([^<]+)<\/span>/s', $html, $matches)) {
            $availability = trim(strip_tags($matches[1]));
            if (stripos($availability, 'in stock') !== false) return 'In Stock';
            if (stripos($availability, 'out of stock') !== false) return 'Out of Stock';
            if (stripos($availability, 'temporarily unavailable') !== false) return 'Temporarily Unavailable';
            return $availability;
        }
        return 'Unknown';
    }
    
    private function extractBrand($html) {
        $patterns = [
            '/<tr[^>]*class="[^"]*po-brand[^"]*"[^>]*>.*?<span[^>]*class="[^"]*po-break-word[^"]*"[^>]*>([^<]+)<\/span>/s',
            '/<span[^>]*class="[^"]*po-break-word[^"]*"[^>]*id="[^"]*brand[^"]*"[^>]*>([^<]+)<\/span>/',
            '/by <a[^>]*href="[^"]*\/brand\/[^"]*"[^>]*>([^<]+)<\/a>/',
            '/<a[^>]*id="bylineInfo"[^>]*>([^<]+)<\/a>/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(strip_tags($matches[1]));
            }
        }
        return null;
    }
    
    private function extractCategory($html) {
        if (preg_match('/<div[^>]*id="wayfinding-breadcrumbs_feature_div"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            $breadcrumbs = strip_tags($matches[1]);
            $categories = array_map('trim', explode('›', $breadcrumbs));
            return array_filter($categories);
        }
        return [];
    }
    
    private function extractFeatures($html) {
        $features = [];
        if (preg_match_all('/<span[^>]*class="[^"]*a-list-item[^"]*"[^>]*>\s*([^<]+)\s*<\/span>/i', $html, $matches)) {
            foreach ($matches[1] as $feature) {
                $feature = trim(strip_tags($feature));
                if (strlen($feature) > 10 && strlen($feature) < 200) {
                    $features[] = $feature;
                }
            }
        }
        return array_slice(array_unique($features), 0, 10);
    }
    
    private function extractVariants($html) {
        $variants = [];
        if (preg_match_all('/<li[^>]*class="[^"]*swatchElement[^"]*"[^>]*>.*?title="([^"]+)"/s', $html, $matches)) {
            $variants = array_unique($matches[1]);
        }
        return array_values($variants);
    }
    
    private function extractImages($html, $asin) {
        $images = [];
        
        // Try to extract from image data
        if (preg_match('/"colorImages":\s*{[^}]*"initial":\s*\[(.*?)\]/s', $html, $matches)) {
            if (preg_match_all('/"large":"([^"]+)"/i', $matches[1], $imageMatches)) {
                $images = $imageMatches[1];
            }
        }
        
        // Fallback to standard Amazon image URLs
        if (empty($images)) {
            $images[] = "https://images-na.ssl-images-amazon.com/images/P/{$asin}.01.L.jpg";
            for ($i = 2; $i <= 6; $i++) {
                $images[] = "https://images-na.ssl-images-amazon.com/images/P/{$asin}.0{$i}.L.jpg";
            }
        }
        
        return array_slice($images, 0, 8);
    }
    
    private function extractDescription($html) {
        $patterns = [
            '/<div[^>]*id="feature-bullets"[^>]*>.*?<ul[^>]*class="[^"]*a-unordered-list[^"]*"[^>]*>(.*?)<\/ul>/s',
            '/<div[^>]*class="[^"]*a-section[^"]*"[^>]*data-module-name="productDescription"[^>]*>(.*?)<\/div>/s'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $description = strip_tags($matches[1]);
                $description = preg_replace('/\s+/', ' ', $description);
                return trim(substr($description, 0, 500));
            }
        }
        return null;
    }
    
    private function extractSpecifications($html) {
        $specs = [];
        if (preg_match_all('/<tr[^>]*class="[^"]*a-spacing-small[^"]*"[^>]*>.*?<td[^>]*class="[^"]*a-span3[^"]*"[^>]*>\s*<span[^>]*class="[^"]*a-size-base[^"]*"[^>]*>([^<]+)<\/span>.*?<td[^>]*class="[^"]*a-span9[^"]*"[^>]*>\s*<span[^>]*class="[^"]*a-size-base[^"]*"[^>]*>([^<]+)<\/span>/s', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = trim(strip_tags($match[1]));
                $value = trim(strip_tags($match[2]));
                if ($key && $value) {
                    $specs[$key] = $value;
                }
            }
        }
        return $specs;
    }
    
    private function extractSeller($html) {
        $patterns = [
            '/<span[^>]*>Sold by <a[^>]*>([^<]+)<\/a><\/span>/',
            '/<div[^>]*id="merchant-info"[^>]*>.*?<a[^>]*>([^<]+)<\/a>/s'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(strip_tags($matches[1]));
            }
        }
        return null;
    }
    
    private function extractPrimeEligible($html) {
        return (stripos($html, 'prime') !== false && 
                (stripos($html, 'prime eligible') !== false || 
                 stripos($html, 'prime delivery') !== false));
    }
    
    private function extractDeliveryInfo($html) {
        if (preg_match('/<div[^>]*id="mir-layout-DELIVERY_BLOCK"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            $delivery = strip_tags($matches[1]);
            return trim(preg_replace('/\s+/', ' ', $delivery));
        }
        return null;
    }
    
    private function extractCoupon($html) {
        if (preg_match('/<label[^>]*class="[^"]*a-checkbox-label[^"]*"[^>]*>.*?Save ([^<]+)<\/label>/s', $html, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    public function setProxies($proxies) {
        $this->proxies = $proxies;
    }
    
    public function setRequestDelay($delay) {
        $this->requestDelay = max(1, intval($delay));
    }
    
    public function validateProduct($data) {
        return !empty($data['title']) && !empty($data['price']) && $data['price'] > 0;
    }
}
?>