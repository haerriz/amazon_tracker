<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class RealtimeScraper {
    private $tempDir;
    
    public function __construct() {
        $this->tempDir = sys_get_temp_dir();
    }
    
    public function scrapeProduct($asin, $market = 'IN') {
        $domain = $this->getDomain($market);
        $url = "https://{$domain}/dp/{$asin}";
        
        // Try multiple methods for real-time scraping
        $data = $this->scrapeWithInternalBrowser($url, $asin, $market) ?:
                $this->scrapeWithProxy($url, $asin, $market) ?:
                $this->scrapeWithAdvancedCurl($url, $asin, $market);
        
        if ($data && $this->validatePrice($data['price'])) {
            $data['method'] = 'realtime';
            $data['scraped_at'] = date('Y-m-d H:i:s');
            return $data;
        }
        
        return null;
    }
    
    private function scrapeWithInternalBrowser($url, $asin, $market) {
        // Create a temporary HTML file that loads Amazon internally
        $htmlFile = $this->tempDir . '/amazon_loader_' . uniqid() . '.html';
        $dataFile = $this->tempDir . '/amazon_data_' . uniqid() . '.json';
        
        $html = $this->generateBrowserHTML($url, $dataFile);
        file_put_contents($htmlFile, $html);
        
        // Try to execute with headless browser
        $commands = [
            "timeout 30 google-chrome --headless --disable-gpu --no-sandbox --disable-dev-shm-usage --virtual-time-budget=10000 --run-all-compositor-stages-before-draw --dump-dom file://{$htmlFile}",
            "timeout 30 chromium --headless --disable-gpu --no-sandbox --disable-dev-shm-usage --virtual-time-budget=10000 file://{$htmlFile}",
            "timeout 30 chromium-browser --headless --disable-gpu --no-sandbox --virtual-time-budget=10000 file://{$htmlFile}"
        ];
        
        foreach ($commands as $command) {
            exec($command . ' 2>/dev/null', $output, $returnCode);
            
            // Wait for data file to be created
            $attempts = 0;
            while (!file_exists($dataFile) && $attempts < 20) {
                usleep(500000); // 0.5 seconds
                $attempts++;
            }
            
            if (file_exists($dataFile)) {
                $data = json_decode(file_get_contents($dataFile), true);
                unlink($htmlFile);
                unlink($dataFile);
                
                if ($data && $data['price']) {
                    return [
                        'asin' => $asin,
                        'title' => $data['title'],
                        'price' => $data['price'],
                        'original_price' => $data['original_price'],
                        'discount' => $data['discount'],
                        'rating' => $data['rating'],
                        'review_count' => $data['review_count'],
                        'availability' => $data['availability'],
                        'images' => [$data['image']],
                        'url' => $url
                    ];
                }
            }
        }
        
        // Cleanup
        if (file_exists($htmlFile)) unlink($htmlFile);
        if (file_exists($dataFile)) unlink($dataFile);
        
        return null;
    }
    
    private function generateBrowserHTML($amazonUrl, $dataFile) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 20px; font-family: Arial; }
        #status { background: #f0f0f0; padding: 10px; margin-bottom: 20px; }
        #amazon-frame { width: 100%; height: 800px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div id="status">Loading Amazon product data...</div>
    <iframe id="amazon-frame" src="about:blank"></iframe>
    
    <script>
        let productData = {
            title: null,
            price: null,
            original_price: null,
            discount: null,
            rating: null,
            review_count: null,
            availability: null,
            image: null
        };
        
        function extractProductData() {
            const frame = document.getElementById("amazon-frame");
            const doc = frame.contentDocument || frame.contentWindow.document;
            
            // Extract title
            const titleSelectors = [
                "#productTitle",
                "h1.product-title",
                "[data-testid=product-title]"
            ];
            
            for (const selector of titleSelectors) {
                const el = doc.querySelector(selector);
                if (el && el.textContent.trim()) {
                    productData.title = el.textContent.trim();
                    break;
                }
            }
            
            // Extract current price
            const priceSelectors = [
                ".a-price-whole",
                ".a-offscreen",
                "#priceblock_dealprice",
                "#priceblock_ourprice",
                "[data-testid=price-current]"
            ];
            
            for (const selector of priceSelectors) {
                const el = doc.querySelector(selector);
                if (el) {
                    const text = el.textContent || el.innerText;
                    const priceMatch = text.match(/[\d,]+\.?\d*/);
                    if (priceMatch) {
                        productData.price = parseFloat(priceMatch[0].replace(/,/g, ""));
                        if (productData.price > 0) break;
                    }
                }
            }
            
            // Extract original price
            const originalPriceSelectors = [
                ".a-text-strike .a-offscreen",
                ".a-price.a-text-price .a-offscreen",
                "[data-testid=list-price]"
            ];
            
            for (const selector of originalPriceSelectors) {
                const el = doc.querySelector(selector);
                if (el) {
                    const text = el.textContent || el.innerText;
                    const priceMatch = text.match(/[\d,]+\.?\d*/);
                    if (priceMatch) {
                        productData.original_price = parseFloat(priceMatch[0].replace(/,/g, ""));
                        break;
                    }
                }
            }
            
            // Calculate discount
            if (productData.price && productData.original_price && productData.original_price > productData.price) {
                productData.discount = Math.round(((productData.original_price - productData.price) / productData.original_price) * 100);
            }
            
            // Extract rating
            const ratingSelectors = [
                ".a-icon-alt",
                "[data-testid=rating]"
            ];
            
            for (const selector of ratingSelectors) {
                const el = doc.querySelector(selector);
                if (el) {
                    const text = el.textContent || el.innerText;
                    const ratingMatch = text.match(/[\d.]+/);
                    if (ratingMatch) {
                        productData.rating = parseFloat(ratingMatch[0]);
                        break;
                    }
                }
            }
            
            // Extract review count
            const reviewSelectors = [
                "#acrCustomerReviewText",
                "[data-testid=review-count]"
            ];
            
            for (const selector of reviewSelectors) {
                const el = doc.querySelector(selector);
                if (el) {
                    const text = el.textContent || el.innerText;
                    const countMatch = text.match(/[\d,]+/);
                    if (countMatch) {
                        productData.review_count = parseInt(countMatch[0].replace(/,/g, ""));
                        break;
                    }
                }
            }
            
            // Extract availability
            const availabilitySelectors = [
                "#availability span",
                "[data-testid=availability]"
            ];
            
            for (const selector of availabilitySelectors) {
                const el = doc.querySelector(selector);
                if (el && el.textContent.trim()) {
                    productData.availability = el.textContent.trim();
                    break;
                }
            }
            
            // Extract main image
            const imageSelectors = [
                "#landingImage",
                ".a-dynamic-image",
                "[data-testid=product-image]"
            ];
            
            for (const selector of imageSelectors) {
                const el = doc.querySelector(selector);
                if (el && el.src) {
                    productData.image = el.src;
                    break;
                }
            }
            
            // Save data using fetch to a data endpoint
            if (productData.title && productData.price) {
                fetch("data:application/json;base64," + btoa(JSON.stringify(productData)))
                    .then(() => {
                        // Signal completion
                        document.getElementById("status").innerHTML = "✓ Data extracted successfully";
                        
                        // Try to save to file system (this might not work in all browsers)
                        try {
                            const blob = new Blob([JSON.stringify(productData)], {type: "application/json"});
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement("a");
                            a.href = url;
                            a.download = "' . basename($dataFile) . '";
                            a.click();
                        } catch(e) {
                            console.log("File save failed, data:", productData);
                        }
                    });
            }
        }
        
        // Load Amazon page
        setTimeout(() => {
            const frame = document.getElementById("amazon-frame");
            frame.onload = () => {
                setTimeout(extractProductData, 3000); // Wait 3 seconds for page to fully load
            };
            frame.src = "' . $amazonUrl . '";
        }, 1000);
        
        // Fallback: extract after 10 seconds regardless
        setTimeout(extractProductData, 10000);
    </script>
</body>
</html>';
    }
    
    private function scrapeWithProxy($url, $asin, $market) {
        $proxyServices = [
            'https://api.allorigins.win/get?url=' . urlencode($url),
            'https://cors-anywhere.herokuapp.com/' . $url,
            'https://thingproxy.freeboard.io/fetch/' . $url
        ];
        
        foreach ($proxyServices as $proxyUrl) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $proxyUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json, text/html',
                    'Accept-Language: en-US,en;q=0.9'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $json = json_decode($response, true);
                $html = $json['contents'] ?? $response;
                
                if (strlen($html) > 10000) { // Ensure we got substantial content
                    $data = $this->parseAmazonHTML($html, $asin, $url);
                    if ($data && $data['price']) {
                        return $data;
                    }
                }
            }
            
            sleep(1); // Rate limiting between proxy attempts
        }
        
        return null;
    }
    
    private function scrapeWithAdvancedCurl($url, $asin, $market) {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];
        
        foreach ($userAgents as $userAgent) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => $userAgent,
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
                    'Cache-Control: no-cache'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_COOKIEJAR => $this->tempDir . '/cookies_' . uniqid() . '.txt',
                CURLOPT_COOKIEFILE => $this->tempDir . '/cookies_' . uniqid() . '.txt'
            ]);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $html && strlen($html) > 10000) {
                $data = $this->parseAmazonHTML($html, $asin, $url);
                if ($data && $data['price']) {
                    return $data;
                }
            }
            
            sleep(2); // Rate limiting between attempts
        }
        
        return null;
    }
    
    private function parseAmazonHTML($html, $asin, $url) {
        return [
            'asin' => $asin,
            'title' => $this->extractTitle($html),
            'price' => $this->extractCurrentPrice($html),
            'original_price' => $this->extractOriginalPrice($html),
            'discount' => $this->extractDiscount($html),
            'rating' => $this->extractRating($html),
            'review_count' => $this->extractReviewCount($html),
            'availability' => $this->extractAvailability($html),
            'images' => $this->extractImages($html, $asin),
            'url' => $url
        ];
    }
    
    private function extractCurrentPrice($html) {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([^<]+)<\/span><span[^>]*class="[^"]*a-price-fraction[^"]*"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>₹\s*([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([^<]+)<\/span>/',
            '/<span[^>]*id="priceblock_dealprice"[^>]*>₹\s*([^<]+)<\/span>/',
            '/<span[^>]*id="priceblock_ourprice"[^>]*>₹\s*([^<]+)<\/span>/',
            '/₹\s*([0-9,]+(?:\.[0-9]{2})?)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                if (isset($matches[2])) {
                    $price = $matches[1] . '.' . $matches[2];
                } else {
                    $price = $matches[1];
                }
                
                $price = preg_replace('/[^0-9.]/', '', $price);
                $price = floatval($price);
                
                if ($price > 0 && $price < 10000000) {
                    return $price;
                }
            }
        }
        
        return null;
    }
    
    private function extractTitle($html) {
        $patterns = [
            '/<span[^>]*id="productTitle"[^>]*>\s*([^<]+?)\s*<\/span>/i',
            '/<h1[^>]*class="[^"]*product[^"]*title[^"]*"[^>]*>\s*([^<]+?)\s*<\/h1>/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
                if (strlen($title) > 5) return $title;
            }
        }
        return null;
    }
    
    private function extractOriginalPrice($html) {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price[^"]*a-text-price[^"]*"[^>]*><span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>₹\s*([^<]+)<\/span>/',
            '/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>₹\s*([^<]+)<\/span>/',
            '/M\.R\.P\.:?\s*₹\s*([0-9,]+(?:\.[0-9]{2})?)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = preg_replace('/[^0-9.]/', '', $matches[1]);
                $price = floatval($price);
                if ($price > 0) return $price;
            }
        }
        return null;
    }
    
    private function extractDiscount($html) {
        $patterns = [
            '/(\d+)%\s*savings/i',
            '/-(\d+)%/i',
            '/(\d+)%\s*off/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return intval($matches[1]);
            }
        }
        return null;
    }
    
    private function extractRating($html) {
        if (preg_match('/(\d+\.?\d*)\s*out of 5 stars/i', $html, $matches)) {
            return floatval($matches[1]);
        }
        return null;
    }
    
    private function extractReviewCount($html) {
        if (preg_match('/([\d,]+)\s*ratings?/i', $html, $matches)) {
            return intval(str_replace(',', '', $matches[1]));
        }
        return null;
    }
    
    private function extractAvailability($html) {
        if (preg_match('/<div[^>]*id="availability"[^>]*>.*?<span[^>]*>([^<]+)<\/span>/s', $html, $matches)) {
            $availability = trim(strip_tags($matches[1]));
            if (stripos($availability, 'in stock') !== false) return 'In Stock';
            if (stripos($availability, 'out of stock') !== false) return 'Out of Stock';
            return $availability;
        }
        return 'In Stock';
    }
    
    private function extractImages($html, $asin) {
        $images = [];
        if (preg_match_all('/"large":"([^"]+)"/i', $html, $matches)) {
            $images = array_slice($matches[1], 0, 3);
        }
        
        if (empty($images)) {
            $images[] = "https://images-na.ssl-images-amazon.com/images/P/{$asin}.01.L.jpg";
        }
        
        return $images;
    }
    
    private function getDomain($market) {
        $domains = ['IN' => 'amazon.in', 'US' => 'amazon.com', 'UK' => 'amazon.co.uk'];
        return $domains[$market] ?? 'amazon.in';
    }
    
    private function validatePrice($price) {
        return is_numeric($price) && $price > 0 && $price < 10000000;
    }
}

// Handle API request
if (isset($_GET['asin'])) {
    $scraper = new RealtimeScraper();
    $data = $scraper->scrapeProduct($_GET['asin'], $_GET['market'] ?? 'IN');
    
    if ($data) {
        echo json_encode($data, JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Unable to fetch current price data']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ASIN parameter required']);
}
?>