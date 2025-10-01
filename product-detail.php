<?php
require_once 'backend/config/database.php';
require_once 'backend/config/affiliate.php';

// Get product ID from URL
$asin = $_GET['asin'] ?? '';
if (!$asin) {
    header('Location: index.html');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE asin = ?");
    $stmt->execute([$asin]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: index.html');
        exit;
    }
    
    // Get price history
    $stmt = $conn->prepare("SELECT price, created_at FROM price_history WHERE product_id = ? ORDER BY created_at DESC LIMIT 30");
    $stmt->execute([$product['id']]);
    $priceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $prices = array_column($priceHistory, 'price');
    $currentPrice = $product['current_price'];
    $minPrice = !empty($prices) ? min($prices) : $currentPrice;
    $maxPrice = !empty($prices) ? max($prices) : $currentPrice;
    $avgPrice = !empty($prices) ? round(array_sum($prices) / count($prices), 2) : $currentPrice;
    
    // Calculate deal score
    $dealScore = calculateDealScore($currentPrice, $minPrice, $maxPrice, $avgPrice);
    
    // SEO data
    $pageTitle = $product['title'] . ' - Price History & Deals';
    $pageDescription = "Track {$product['title']} price history, set alerts, and get the best deals. Current price: ₹{$currentPrice}";
    $canonicalUrl = "https://" . $_SERVER['HTTP_HOST'] . "/product-detail.php?asin=" . $asin;
    
} catch (Exception $e) {
    error_log("Product page error: " . $e->getMessage());
    header('Location: index.html');
    exit;
}

function calculateDealScore($current, $min, $max, $avg) {
    $score = 0;
    
    // At all-time low (35 points)
    if ($current <= $min) $score += 35;
    
    // At 6-month low (29 points) 
    if ($current <= $min * 1.05) $score += 29;
    
    // Below average (21 points)
    if ($current < $avg) $score += 21;
    
    // No price hike before sale (12 points)
    $score += 12;
    
    return min($score, 100);
}

function formatPriceHistory($history) {
    $formatted = [];
    foreach ($history as $item) {
        $formatted[] = [
            'date' => date('Y-m-d', strtotime($item['created_at'])),
            'price' => floatval($item['price'])
        ];
    }
    return array_reverse($formatted);
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($product['image_url']) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:type" content="product">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($product['image_url']) ?>">
    
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/seo-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <a href="index.html" class="logo">
                        <span class="material-icons">trending_down</span>
                        Amazon Tracker
                    </a>
                    <nav class="nav">
                        <a href="index.html">Home</a>
                        <a href="#deals">Deals</a>
                        <a href="#alerts">Alerts</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <div class="container">
                <a href="index.html">Home</a>
                <span>/</span>
                <span><?= htmlspecialchars(substr($product['title'], 0, 50)) ?>...</span>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <div class="product-layout">
                    <!-- Product Info -->
                    <div class="product-info">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($product['title']) ?>"
                                 loading="lazy">
                        </div>
                        
                        <div class="product-details">
                            <div class="site-badge">
                                <img src="https://logo.clearbit.com/amazon.in" alt="Amazon" class="site-icon">
                                <span>Amazon</span>
                            </div>
                            
                            <h1 class="product-title"><?= htmlspecialchars($product['title']) ?></h1>
                            
                            <div class="price-section">
                                <div class="current-price">₹<?= number_format($currentPrice) ?></div>
                                <?php if ($product['original_price'] && $product['original_price'] > $currentPrice): ?>
                                    <div class="price-details">
                                        <span class="discount-badge">
                                            <span class="material-icons">trending_down</span>
                                            <?= round((($product['original_price'] - $currentPrice) / $product['original_price']) * 100) ?>%
                                        </span>
                                        <span class="original-price">₹<?= number_format($product['original_price']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Deal Scanner -->
                            <div class="deal-scanner">
                                <div class="deal-header">
                                    <h4>Deal Scanner</h4>
                                    <div class="share-buttons">
                                        <span>Share on</span>
                                        <a href="https://api.whatsapp.com/send?text=<?= urlencode($canonicalUrl) ?>" target="_blank">
                                            <span class="material-icons">share</span>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="deal-content">
                                    <div class="deal-badge">
                                        <span class="material-icons">thumb_up</span>
                                        Epic Hatke Steal 🚀
                                    </div>
                                    
                                    <div class="deal-meter">
                                        <div class="speedometer">
                                            <div class="needle" style="transform: rotate(<?= ($dealScore * 1.8) - 90 ?>deg)"></div>
                                        </div>
                                        <div class="deal-score">Deal Score <strong><?= $dealScore ?></strong></div>
                                    </div>
                                </div>
                                
                                <?php if ($currentPrice <= $minPrice): ?>
                                <div class="lowest-badge">
                                    <span class="material-icons">trending_down</span>
                                    <div>
                                        <h6>Lowest Since Launch</h6>
                                        <p>Best price we've ever recorded for this product</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Deal Score Breakdown -->
                                <div class="score-breakdown">
                                    <h5>Deal Score Breakup</h5>
                                    <div class="score-items">
                                        <div class="score-item">
                                            <span>No Price hike before sale</span>
                                            <div class="score-circle" data-score="35"></div>
                                        </div>
                                        <div class="score-item">
                                            <span>At All time low price (₹<?= number_format($minPrice) ?>)</span>
                                            <div class="score-circle" data-score="29"></div>
                                        </div>
                                        <div class="score-item">
                                            <span>Below average price (₹<?= number_format($avgPrice) ?>)</span>
                                            <div class="score-circle" data-score="21"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Stats -->
                    <div class="price-stats">
                        <div class="stat-item">
                            <span class="material-icons red">keyboard_arrow_up</span>
                            <span class="stat-label">Highest Price</span>
                            <span class="stat-value">₹<?= number_format($maxPrice) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="material-icons orange">swap_vert</span>
                            <span class="stat-label">Average Price</span>
                            <span class="stat-value">₹<?= number_format($avgPrice) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="material-icons green">keyboard_arrow_down</span>
                            <span class="stat-label">Lowest Price</span>
                            <span class="stat-value">₹<?= number_format($minPrice) ?></span>
                        </div>
                    </div>

                    <!-- Price Alert -->
                    <div class="price-alert-section">
                        <h3>Set Price Alert</h3>
                        <div class="alert-form">
                            <div class="input-group">
                                <span class="material-icons">notifications</span>
                                <input type="number" id="alertPrice" placeholder="Enter target price" value="<?= $currentPrice ?>">
                            </div>
                            <button class="btn-primary" onclick="setPriceAlert('<?= $asin ?>')">
                                Set Alert
                            </button>
                        </div>
                        
                        <a href="<?= getAffiliateUrl($product['url']) ?>" target="_blank" class="buy-button">
                            <button class="btn-buy">
                                Buy Now on Amazon
                            </button>
                        </a>
                    </div>

                    <!-- Price History Chart -->
                    <div class="chart-section">
                        <h3>Price History</h3>
                        <div class="chart-container">
                            <canvas id="priceChart"></canvas>
                        </div>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-period="7">7D</button>
                            <button class="chart-btn" data-period="30">1M</button>
                            <button class="chart-btn" data-period="90">3M</button>
                            <button class="chart-btn" data-period="365">1Y</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h4>Amazon Price Tracker</h4>
                        <p>Track prices, set alerts, and never miss a deal</p>
                    </div>
                    <div class="footer-section">
                        <h4>Features</h4>
                        <ul>
                            <li><a href="#price-tracking">Price Tracking</a></li>
                            <li><a href="#price-alerts">Price Alerts</a></li>
                            <li><a href="#deal-scanner">Deal Scanner</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#privacy">Privacy Policy</a></li>
                            <li><a href="#terms">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; 2024 Amazon Price Tracker. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Pass data to JavaScript
        window.productData = {
            asin: '<?= $asin ?>',
            title: <?= json_encode($product['title']) ?>,
            currentPrice: <?= $currentPrice ?>,
            priceHistory: <?= json_encode(formatPriceHistory($priceHistory)) ?>,
            minPrice: <?= $minPrice ?>,
            maxPrice: <?= $maxPrice ?>,
            avgPrice: <?= $avgPrice ?>,
            dealScore: <?= $dealScore ?>
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/product-page.js"></script>
</body>
</html>