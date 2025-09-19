// Product Management



function cardHtml(product) {
  const imageUrl = getProductImage(product.asin, product.market);
  const productId = `${product.asin}-${product.market}`;
  const selectedRange = productTimeRanges[productId] || 30;
  
  return `
    <div class="col s12 product-card">
      <div class="card">
        <div class="card-content">
          <div class="row" style="margin-bottom:0">
            <div class="col s12 m3">
              <img class="product-img" src="${imageUrl}" alt="${product.title || 'Product'}" 
                   onerror="this.src='https://via.placeholder.com/300x300/e0e0e0/757575?text=No+Image'">
              <div style="margin-top:10px">
                <span style="font-weight:700;font-size:1.3rem">${product.current_price ? money(product.current_price, currencyFor(product.market)) : 'Price unavailable'}</span>
                <div class="subtle">ASIN: ${product.asin} • ${product.market}</div>
              </div>
            </div>
            <div class="col s12 m9">
              <div class="row">
                <div class="col s12 m8">
                  <span class="card-title">${product.title || 'Loading product...'}</span>
                  <div class="chart-container">
                    <div class="range-chips">
                      <a class="chip range ${selectedRange === 7 ? 'active' : ''}" data-id="${productId}" data-days="7">7d</a>
                      <a class="chip range ${selectedRange === 30 ? 'active' : ''}" data-id="${productId}" data-days="30">30d</a>
                      <a class="chip range ${selectedRange === 90 ? 'active' : ''}" data-id="${productId}" data-days="90">90d</a>
                      <a class="chip range ${selectedRange === 365 ? 'active' : ''}" data-id="${productId}" data-days="365">1y</a>
                      <a class="chip range ${selectedRange === 0 ? 'active' : ''}" data-id="${productId}" data-days="0">All</a>
                    </div>
                    <div class="svg-wrap" style="position: relative;">
                      <svg class="chart-svg" data-id="${productId}" width="100%" height="100%" viewBox="0 0 800 350" preserveAspectRatio="none"></svg>
                      <div class="chart-tooltip" id="tooltip-${productId}"></div>
                    </div>
                    <div class="stats" data-id="${productId}">
                      <span class="stat">Min: <b class="st-min">—</b></span>
                      <span class="stat">Max: <b class="st-max">—</b></span>
                      <span class="stat">Change: <b class="st-chg">—</b></span>
                    </div>
                  </div>
                </div>
                <div class="col s12 m4">
                  <div class="row" style="margin-bottom:0">
                    <div class="input-field col s12">
                      <input class="target-input" data-id="${productId}" type="number" step="0.01" 
                             value="${product.target_price ?? ''}" placeholder="Target price">
                      <label class="active subtle">Target price</label>
                    </div>
                    <div class="col s12">
                      <a class="btn btn-outline set-target waves-effect" data-id="${productId}" style="width:100%;margin-bottom:8px">Set Alert</a>
                      <a class="btn red darken-2 remove-btn waves-effect" data-id="${productId}" style="width:100%;margin-bottom:8px">Remove</a>
                      <a class="btn btn-green waves-effect" href="${product.url}" target="_blank" style="width:100%">
                        <i class="material-icons left">shopping_cart</i>Buy
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Store selected time ranges for each product
const productTimeRanges = {};

async function loadProducts() {
  try {
    const response = await fetch(API.products());
    const products = await response.json();
    
    const $cards = $('#cards');
    $cards.empty();
    
    if (!products.length) {
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
    M.toast({ html: 'Error loading products' });
  }
}

function refreshList() {
  loadProducts();
}

function getProductImage(asin, market) {
  // Generate Amazon product image URL
  const domain = market === 'IN' ? 'amazon.in' : (market === 'US' ? 'amazon.com' : 'amazon.co.uk');
  return `https://images-na.ssl-images-amazon.com/images/P/${asin}.01.L.jpg`;
}

async function addProduct() {
  const input = $('#urlAsin').val().trim();
  const market = $('#market').val();
  const asin = parseASIN(input);
  
  if (!asin) {
    M.toast({ html: 'Invalid URL/ASIN' });
    return;
  }
  
  M.toast({ html: 'Adding product...' });
  
  try {
    const response = await fetch(API.addProduct(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ asin, market })
    });
    
    const result = await response.json();
    
    if (result.success) {
      $('#urlAsin').val('');
      M.toast({ html: 'Product added successfully!' });
      loadProducts();
    } else {
      M.toast({ html: result.error || 'Failed to add product' });
    }
  } catch (error) {
    M.toast({ html: 'Error connecting to server' });
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