<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($productPage->product['image_url'] ?? '/favicon.ico') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    
    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; background: #f8fafc; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
        .product-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0; }
        .product-image { width: 100%; max-width: 400px; border-radius: 12px; }
        .price-current { font-size: 2rem; font-weight: 700; color: #059669; }
        .price-original { text-decoration: line-through; color: #6b7280; }
        .discount-badge { background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 2rem 0; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .chart-container { background: white; padding: 2rem; border-radius: 12px; margin: 2rem 0; }
        .buy-button { background: #f97316; color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; width: 100%; }
        .alert-section { background: white; padding: 2rem; border-radius: 12px; margin: 1rem 0; }
        @media (max-width: 768px) { .product-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="product-header">
        <div class="container">
            <nav style="margin-bottom: 1rem;">
                <a href="/" style="color: rgba(255,255,255,0.8);">Home</a> / 
                <span><?= htmlspecialchars(substr($productPage->product['title'], 0, 50)) ?>...</span>
            </nav>
            <h1 style="margin: 0; font-size: 1.5rem;"><?= htmlspecialchars($productPage->product['title']) ?></h1>
        </div>
    </header>

    <main class="container">
        <div class="product-grid">
            <div>
                <img src="<?= htmlspecialchars($productPage->product['image_url'] ?? '/favicon.ico') ?>" 
                     alt="<?= htmlspecialchars($productPage->product['title']) ?>" 
                     class="product-image">
            </div>
            
            <div>
                <div style="margin-bottom: 1rem;">
                    <span style="background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                        Amazon
                    </span>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <div class="price-current">₹<?= number_format($productPage->product['current_price']) ?></div>
                    <?php if ($productPage->product['original_price'] && $productPage->product['original_price'] > $productPage->product['current_price']): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                            <span class="price-original">₹<?= number_format($productPage->product['original_price']) ?></span>
                            <span class="discount-badge">
                                <?= round((($productPage->product['original_price'] - $productPage->product['current_price']) / $productPage->product['original_price']) * 100) ?>% OFF
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="alert-section">
                    <h3 style="margin-top: 0;">Price Alert</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="number" id="alertPrice" placeholder="Enter target price" 
                               value="<?= $productPage->product['current_price'] ?>"
                               style="flex: 1; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        <button onclick="setAlert()" style="background: #4f46e5; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer;">
                            Set Alert
                        </button>
                    </div>
                </div>
                
                <a href="<?= htmlspecialchars($productPage->product['affiliate_url'] ?? $productPage->product['url']) ?>" target="_blank">
                    <button class="buy-button">Buy on Amazon</button>
                </a>
            </div>
        </div>

        <?php 
        $stats = $productPage->calculateStats();
        if ($stats): 
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div style="color: #ef4444; font-size: 1.25rem; font-weight: 600;">₹<?= number_format($stats['highest']) ?></div>
                <div style="color: #6b7280; font-size: 0.875rem;">Highest Price</div>
            </div>
            <div class="stat-card">
                <div style="color: #f97316; font-size: 1.25rem; font-weight: 600;">₹<?= number_format($stats['average']) ?></div>
                <div style="color: #6b7280; font-size: 0.875rem;">Average Price</div>
            </div>
            <div class="stat-card">
                <div style="color: #10b981; font-size: 1.25rem; font-weight: 600;">₹<?= number_format($stats['lowest']) ?></div>
                <div style="color: #6b7280; font-size: 0.875rem;">Lowest Price</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="chart-container">
            <h2 style="margin-top: 0;">Price History</h2>
            <div id="priceChart" style="height: 300px;"></div>
        </div>
    </main>

    <script>
        const productData = <?= json_encode($productPage->getProductData()) ?>;
        
        function setAlert() {
            const price = document.getElementById('alertPrice').value;
            if (!price) return;
            
            fetch('/backend/api/products.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'set_alert',
                    asin: '<?= $productPage->product['asin'] ?>',
                    target_price: parseFloat(price)
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Price alert set successfully!' : 'Error setting alert');
            });
        }
        
        // Simple price chart
        function renderChart() {
            const container = document.getElementById('priceChart');
            if (!productData.priceHistory.length) {
                container.innerHTML = '<p style="text-align: center; color: #6b7280;">No price history available</p>';
                return;
            }
            
            const prices = productData.priceHistory.map(h => h.price);
            const dates = productData.priceHistory.map(h => new Date(h.created_at).toLocaleDateString());
            
            const maxPrice = Math.max(...prices);
            const minPrice = Math.min(...prices);
            const range = maxPrice - minPrice || 1;
            
            let svg = `<svg width="100%" height="300" viewBox="0 0 800 300">`;
            
            // Draw price line
            let path = 'M';
            prices.forEach((price, i) => {
                const x = (i / (prices.length - 1)) * 750 + 25;
                const y = 275 - ((price - minPrice) / range) * 250;
                path += `${i === 0 ? '' : 'L'}${x},${y}`;
            });
            
            svg += `<path d="${path}" stroke="#4f46e5" stroke-width="3" fill="none"/>`;
            
            // Add price points
            prices.forEach((price, i) => {
                const x = (i / (prices.length - 1)) * 750 + 25;
                const y = 275 - ((price - minPrice) / range) * 250;
                svg += `<circle cx="${x}" cy="${y}" r="4" fill="#4f46e5"/>`;
                svg += `<text x="${x}" y="${y-10}" text-anchor="middle" font-size="12" fill="#374151">₹${price}</text>`;
            });
            
            svg += '</svg>';
            container.innerHTML = svg;
        }
        
        renderChart();
    </script>
</body>
</html>