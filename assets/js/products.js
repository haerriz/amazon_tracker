// Product Management



function cardHtml(product) {
  const imageUrl = getProductImage(product.asin, product.market);
  const productId = `${product.asin}-${product.market}`;
  const selectedRange = productTimeRanges[productId] || 30;
  const currency = currencyFor(product.market);
  const currentPrice = product.current_price || 0;
  
  // Calculate price assessment (mock data for demo)
  const assessment = calculatePriceAssessment(currentPrice);
  
  return `
    <div class="col s12 product-card">
      <div class="card-modern">
        <div class="card-content">
          <!-- Product Hero Section -->
          <div class="product-hero">
            <div class="product-image-container">
              <img class="product-img" src="${imageUrl}" alt="${product.title || 'Product'}" 
                   onerror="this.src='https://via.placeholder.com/300x300/f5f5f5/757575?text=No+Image'">
              <div class="product-meta">
                <span class="status-badge status-in-stock">ASIN: ${product.asin}</span><br>
                <span class="status-badge status-limited">📍 ${product.market}</span><br>
                <small>🕒 Updated: ${formatLastUpdate()}</small>
              </div>
            </div>
            <div class="product-info">
              <h6 class="product-title">${product.title && product.title !== 'null' ? product.title : `Amazon Product ${product.asin}`}</h6>
              <div class="price-display">
                <span class="price-current">${currentPrice > 0 ? money(currentPrice, currency) : 'Price not available'}</span>
                ${assessment.discount ? `<span class="price-discount">${assessment.discount}% OFF</span>` : ''}
              </div>
              <div class="rating-stars">
                ${'★'.repeat(Math.floor(Math.random() * 5) + 1)}
                <span class="rating-text">(${Math.floor(Math.random() * 1000) + 100} reviews)</span>
              </div>
              
              <!-- Price Statistics -->
              <div class="price-stats" id="stats-${productId}">
                <div class="price-stat">
                  <span class="price-stat-label">Lowest</span>
                  <span class="price-stat-value lowest st-min">—</span>
                </div>
                <div class="price-stat">
                  <span class="price-stat-label">Average</span>
                  <span class="price-stat-value average st-avg">—</span>
                </div>
                <div class="price-stat">
                  <span class="price-stat-label">Highest</span>
                  <span class="price-stat-value highest st-max">—</span>
                </div>
              </div>
              
              <!-- Buy Button -->
              <a class="btn-modern btn-gradient waves-effect" href="${product.url || getAffiliateUrl(product.asin, product.market)}" target="_blank" rel="nofollow" style="width:100%;margin-bottom:12px;display:inline-block;text-align:center;text-decoration:none;">
                <i class="material-icons left">shopping_bag</i>🛒 Buy on Amazon
              </a>
              <small class="grey-text" style="font-size:10px;">💰 Affiliate Link - We earn from qualifying purchases</small>
            </div>
          </div>
          
          <!-- Price Assessment -->
          <div class="assessment-container">
            <div class="assessment-title">🤔 Should you buy at this price?</div>
            <div class="rating-scale">
              <span class="${assessment.recommendation === 'skip' ? 'red-text' : ''}">❌ Skip</span>
              <span class="${assessment.recommendation === 'wait' ? 'orange-text' : ''}">⏳ Wait</span>
              <span class="${assessment.recommendation === 'okay' ? 'blue-text' : ''}">👍 Okay</span>
              <span class="${assessment.recommendation === 'buy' ? 'green-text' : ''}">🎯 Buy</span>
            </div>
            <div class="rating-bar">
              <div class="rating-indicator" style="left: ${assessment.position}%;"></div>
            </div>
            <p class="assessment-text">💡 ${assessment.text}</p>
          </div>
          
          <!-- Chart Section -->
          <div class="chart-container">
            <h6>Price History Chart</h6>
            <div class="range-chips">
              <a class="chip range ${selectedRange === 7 ? 'active' : ''}" data-id="${productId}" data-days="7">📅 7D</a>
              <a class="chip range ${selectedRange === 30 ? 'active' : ''}" data-id="${productId}" data-days="30">📊 1M</a>
              <a class="chip range ${selectedRange === 90 ? 'active' : ''}" data-id="${productId}" data-days="90">📈 3M</a>
              <a class="chip range ${selectedRange === 180 ? 'active' : ''}" data-id="${productId}" data-days="180">📉 6M</a>
              <a class="chip range ${selectedRange === 365 ? 'active' : ''}" data-id="${productId}" data-days="365">🗓️ 1Y</a>
              <a class="chip range ${selectedRange === 0 ? 'active' : ''}" data-id="${productId}" data-days="0">🔄 All</a>
            </div>
            <div class="svg-wrap" style="position: relative;">
              <svg class="chart-svg" data-id="${productId}" width="100%" height="100%" viewBox="0 0 800 350" preserveAspectRatio="none"></svg>
              <div class="chart-tooltip" id="tooltip-${productId}"></div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="row" style="margin-top:16px">
            <div class="col s12 m6">
              <div class="input-field">
                <input class="target-input" data-id="${productId}" type="number" step="0.01" 
                       value="${product.target_price ?? ''}" placeholder="Enter target price">
                <label class="active">Price Alert Target</label>
              </div>
            </div>
            <div class="col s6 m3">
              <button class="btn-modern set-target waves-effect" data-id="${productId}" style="width:100%;background:#ff6f00;color:white;">
                <i class="material-icons left">notification_add</i>🔔 Set Alert
              </button>
            </div>
            <div class="col s6 m3">
              <button class="btn-modern remove-btn waves-effect" data-id="${productId}" style="width:100%;background:#f44336;color:white;">
                <i class="material-icons left">delete_outline</i>🗑️ Remove
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

function calculatePriceAssessment(currentPrice) {
  // Mock assessment logic similar to competitor
  const randomFactor = Math.random();
  let recommendation, position, text;
  
  if (randomFactor < 0.2) {
    recommendation = 'skip';
    position = 15;
    text = 'Price is currently high. Consider waiting for a better deal.';
  } else if (randomFactor < 0.4) {
    recommendation = 'wait';
    position = 35;
    text = 'Price is above average. You might want to wait for a price drop.';
  } else if (randomFactor < 0.7) {
    recommendation = 'okay';
    position = 65;
    text = 'Price is reasonable. Good time to buy if you need it now.';
  } else {
    recommendation = 'buy';
    position = 85;
    text = 'Great price! This is a good deal based on price history.';
  }
  
  return { recommendation, position, text };
}

function formatLastUpdate() {
  const now = new Date();
  return now.toLocaleDateString('en-IN', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Store selected time ranges for each product
const productTimeRanges = {};

async function loadProducts() {
  try {
    const response = await fetch(API.products());
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const products = await response.json();
    
    const $cards = $('#cards');
    $cards.empty();
    
    if (!products || !Array.isArray(products) || products.length === 0) {
      $('#empty').show();
      return;
    }
    
    $('#empty').hide();
    products.forEach(product => {
      $cards.append(cardHtml(product));
      const productId = `${product.asin}-${product.market}`;
      const timeRange = productTimeRanges[productId] || 30;
      setTimeout(() => loadChart(product.asin, product.market, timeRange), 100);
    });
  } catch (error) {
    console.error('Load products error:', error);
    $('#empty').show();
    // Don't show error toast on initial load if no products exist
  }
}

function refreshList() {
  loadProducts();
}

function getProductImage(asin, market) {
  // Generate Amazon product image URL with fallback
  return `https://images-na.ssl-images-amazon.com/images/P/${asin}.01.L.jpg`;
}

function getAffiliateUrl(asin, market) {
  const domains = {
    'IN': 'amazon.in',
    'US': 'amazon.com', 
    'UK': 'amazon.co.uk'
  };
  
  const affiliateTags = {
    'IN': 'haerriz06-21',
    'US': 'haerriz06-20',
    'UK': 'haerriz06-21'
  };
  
  const domain = domains[market] || 'amazon.in';
  const tag = affiliateTags[market] || 'yourtagin-21';
  
  return `https://${domain}/dp/${asin}?tag=${tag}`;
}

async function addProduct() {
  const input = $('#urlAsin').val().trim();
  const market = $('#market').val();
  const asin = parseASIN(input);
  
  console.log('Input:', input);
  console.log('Parsed ASIN:', asin);
  console.log('Market:', market);
  
  if (!asin) {
    M.toast({ html: 'Invalid URL/ASIN - Please enter a valid Amazon URL or ASIN' });
    return;
  }
  
  M.toast({ html: 'Adding product...' });
  
  try {
    const apiUrl = API.addProduct();
    console.log('API URL:', apiUrl);
    console.log('Expected: /backend/api/add_product.php');
    console.log('Base URL:', getBaseUrl());
    
    const requestData = { asin, market };
    console.log('Sending request data:', requestData);
    
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(requestData)
    });
    
    console.log('Response status:', response.status);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const result = await response.json();
    console.log('API Response:', result);
    
    if (result.success) {
      $('#urlAsin').val('');
      M.toast({ html: '✓ Product added successfully!' });
      loadProducts();
    } else {
      M.toast({ html: result.error || 'Failed to add product' });
    }
  } catch (error) {
    console.error('Add product error:', error);
    M.toast({ html: `Error: ${error.message}` });
  }
}

async function setTarget(productId) {
  const value = $(`.target-input[data-id='${productId}']`).val();
  
  if (!value) {
    M.toast({ html: 'Please enter a target price' });
    return;
  }
  
  try {
    // Extract ASIN and market from productId
    const parts = String(productId).split('-');
    const market = parts.pop();
    const asin = parts.join('-');
    
    const response = await fetch(API.setAlert(asin), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ 
        market: market,
        target_price: Number(value)
      })
    });
    
    if (response.ok) {
      M.toast({ html: 'Alert saved successfully!' });
    } else {
      M.toast({ html: 'Failed to save alert' });
    }
  } catch (error) {
    M.toast({ html: 'Error saving alert' });
  }
}

async function removeProduct(productId) {
  try {
    // Extract ASIN and market from productId
    const parts = String(productId).split('-');
    const market = parts.pop();
    const asin = parts.join('-');
    
    const response = await fetch(API.deleteProduct(asin, market), {
      method: 'DELETE'
    });
    
    if (response.ok) {
      M.toast({ html: 'Product removed successfully!' });
      loadProducts();
    } else {
      M.toast({ html: 'Failed to remove product' });
    }
  } catch (error) {
    M.toast({ html: 'Error removing product' });
  }
}

function alertCheck(product) {
  if (product.target != null && product.last <= product.target) {
    M.toast({
      html: `🎯 <b>${product.title}</b> hit target <span class="ok">${money(product.target, product.ccy)}</span> (now ${money(product.last, product.ccy)})`
    });
  }
}

function updatePrices() {
  // Real price updates would require:
  // 1. Backend server with Amazon API access
  // 2. Web scraping service (against Amazon ToS)
  // 3. Third-party price tracking API
  console.log('Price updates disabled - demo mode');
}

function updateChartsOnly() {
  const db = readDB();
  Object.values(db.products).forEach(product => {
    const timeRange = productTimeRanges[product.id] || 30;
    // Update price display
    $(`.product-card:has([data-id="${product.id}"]) .col.s12.m3 span`).first().text(money(product.last, product.ccy));
    // Redraw chart with current time range
    drawChart(product.id, timeRange);
  });
}