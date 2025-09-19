// Chart Management

async function loadChart(asin, market, days = 30) {
  try {
    const response = await fetch(API.productHistory(asin, market, days));
    const history = await response.json();
    
    console.log('Chart data:', history); // Debug log
    
    if (history && history.length > 0) {
      drawChart(asin + '-' + market, history, market);
    } else {
      console.log('No chart data available');
      // Show message in chart area
      const productId = asin + '-' + market;
      const $svg = $(`.chart-svg[data-id="${productId}"]`);
      if ($svg.length) {
        $svg.html('<text x="400" y="175" text-anchor="middle" class="axis-text">No price history available</text>');
      }
    }
  } catch (error) {
    console.log('Error loading chart:', error);
  }
}

function drawChart(productId, series, market) {
  const $svg = $(`.chart-svg[data-id="${productId}"]`);
  const $stats = $(`.stats[data-id="${productId}"]`);
  
  if (!$svg.length || !series.length) return;
  
  $svg.empty();
  
  const W = 800, H = 350, PAD_LEFT = 60, PAD_RIGHT = 20, PAD_TOP = 20, PAD_BOTTOM = 40;
  const CHART_W = W - PAD_LEFT - PAD_RIGHT;
  const CHART_H = H - PAD_TOP - PAD_BOTTOM;
  
  let minY = Math.min(...series.map(p => p.price));
  let maxY = Math.max(...series.map(p => p.price));
  
  if (minY === maxY) {
    maxY += maxY * 0.1;
    minY -= minY * 0.1;
  }
  
  const n = series.length;
  
  function x(i) {
    return PAD_LEFT + (i / (n - 1)) * CHART_W;
  }
  
  function y(value) {
    return PAD_TOP + CHART_H - ((value - minY) / (maxY - minY)) * CHART_H;
  }
  
  // Create gradient
  const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
  const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
  gradient.setAttribute('id', `gradient-${productId}`);
  gradient.setAttribute('x1', '0%');
  gradient.setAttribute('y1', '0%');
  gradient.setAttribute('x2', '0%');
  gradient.setAttribute('y2', '100%');
  
  const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
  stop1.setAttribute('offset', '0%');
  stop1.setAttribute('stop-color', '#1976d2');
  stop1.setAttribute('stop-opacity', '0.3');
  
  const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
  stop2.setAttribute('offset', '100%');
  stop2.setAttribute('stop-color', '#1976d2');
  stop2.setAttribute('stop-opacity', '0.1');
  
  gradient.appendChild(stop1);
  gradient.appendChild(stop2);
  defs.appendChild(gradient);
  $svg[0].appendChild(defs);
  
  // Draw Y-axis grid lines and labels
  const yTicks = 5;
  for (let i = 0; i <= yTicks; i++) {
    const value = minY + (maxY - minY) * (i / yTicks);
    const yPos = y(value);
    
    // Grid line
    const gridLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    gridLine.setAttribute('x1', PAD_LEFT);
    gridLine.setAttribute('y1', yPos);
    gridLine.setAttribute('x2', PAD_LEFT + CHART_W);
    gridLine.setAttribute('y2', yPos);
    gridLine.setAttribute('class', 'grid-line');
    $svg[0].appendChild(gridLine);
    
    // Y-axis label
    const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    label.setAttribute('x', PAD_LEFT - 5);
    label.setAttribute('y', yPos + 4);
    label.setAttribute('text-anchor', 'end');
    label.setAttribute('class', 'axis-text');
    label.textContent = formatPrice(value, currencyFor(market));
    $svg[0].appendChild(label);
  }
  
  // Draw X-axis grid lines and labels
  const xTicks = Math.min(6, n);
  for (let i = 0; i < xTicks; i++) {
    const index = Math.floor((i / (xTicks - 1)) * (n - 1));
    const xPos = x(index);
    const date = new Date(series[index].ts);
    
    // Grid line
    const gridLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    gridLine.setAttribute('x1', xPos);
    gridLine.setAttribute('y1', PAD_TOP);
    gridLine.setAttribute('x2', xPos);
    gridLine.setAttribute('y2', PAD_TOP + CHART_H);
    gridLine.setAttribute('class', 'grid-line');
    $svg[0].appendChild(gridLine);
    
    // X-axis label
    const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    label.setAttribute('x', xPos);
    label.setAttribute('y', PAD_TOP + CHART_H + 15);
    label.setAttribute('text-anchor', 'middle');
    label.setAttribute('class', 'axis-text');
    label.textContent = formatDate(date, days);
    $svg[0].appendChild(label);
  }
  
  // Draw axes
  const yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
  yAxis.setAttribute('x1', PAD_LEFT);
  yAxis.setAttribute('y1', PAD_TOP);
  yAxis.setAttribute('x2', PAD_LEFT);
  yAxis.setAttribute('y2', PAD_TOP + CHART_H);
  yAxis.setAttribute('class', 'axis-line');
  $svg[0].appendChild(yAxis);
  
  const xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
  xAxis.setAttribute('x1', PAD_LEFT);
  xAxis.setAttribute('y1', PAD_TOP + CHART_H);
  xAxis.setAttribute('x2', PAD_LEFT + CHART_W);
  xAxis.setAttribute('y2', PAD_TOP + CHART_H);
  xAxis.setAttribute('class', 'axis-line');
  $svg[0].appendChild(xAxis);
  
  // Create path data
  let pathData = '';
  let areaData = '';
  
  series.forEach((point, i) => {
    const X = x(i);
    const Y = y(point.price);
    pathData += (i === 0 ? `M ${X} ${Y}` : ` L ${X} ${Y}`);
    
    if (i === 0) {
      areaData = `M ${X} ${PAD_TOP + CHART_H} L ${X} ${Y}`;
    } else {
      areaData += ` L ${X} ${Y}`;
    }
  });
  
  areaData += ` L ${x(n-1)} ${PAD_TOP + CHART_H} Z`;
  
  // Draw area
  const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  area.setAttribute('d', areaData);
  area.setAttribute('fill', `url(#gradient-${productId})`);
  $svg[0].appendChild(area);
  
  // Draw line
  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', pathData);
  path.setAttribute('stroke', '#1976d2');
  path.setAttribute('stroke-width', '2');
  path.setAttribute('fill', 'none');
  $svg[0].appendChild(path);
  
  // Add hover functionality
  addHoverInteraction($svg[0], series, productId, x, y, PAD_LEFT, CHART_W, PAD_TOP, CHART_H, market);
  
  // Update statistics
  const currentPrice = series[series.length - 1].price;
  const change = ((currentPrice - series[0].price) / series[0].price) * 100;
  const currency = currencyFor(market);
  
  $stats.find('.st-min').text(money(minY, currency));
  $stats.find('.st-max').text(money(maxY, currency));
  $stats.find('.st-chg').html(`<span class="${change >= 0 ? 'ok' : 'bad'}">${change.toFixed(2)}%</span>`);
}

function formatPrice(price, currency) {
  if (currency === 'INR') return '₹' + Math.round(price);
  if (currency === 'USD') return '$' + Math.round(price);
  return '£' + Math.round(price);
}

function formatDate(date, days) {
  if (days <= 7) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  } else if (days <= 90) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  } else {
    return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
  }
}

function addHoverInteraction(svg, series, productId, xFunc, yFunc, padLeft, chartW, padTop, chartH, market) {
  const tooltip = $(`#tooltip-${productId}`);
  let hoverLine, hoverDot;
  
  // Create hover elements
  hoverLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
  hoverLine.setAttribute('class', 'hover-line');
  hoverLine.style.display = 'none';
  svg.appendChild(hoverLine);
  
  hoverDot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
  hoverDot.setAttribute('class', 'hover-dot');
  hoverDot.setAttribute('r', '4');
  hoverDot.style.display = 'none';
  svg.appendChild(hoverDot);
  
  // Create invisible overlay for mouse events
  const overlay = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
  overlay.setAttribute('x', padLeft);
  overlay.setAttribute('y', padTop);
  overlay.setAttribute('width', chartW);
  overlay.setAttribute('height', chartH);
  overlay.setAttribute('fill', 'transparent');
  overlay.style.cursor = 'crosshair';
  svg.appendChild(overlay);
  
  overlay.addEventListener('mousemove', function(e) {
    const rect = svg.getBoundingClientRect();
    const mouseX = ((e.clientX - rect.left) / rect.width) * 800;
    
    if (mouseX < padLeft || mouseX > padLeft + chartW) return;
    
    // Find closest data point
    const relativeX = (mouseX - padLeft) / chartW;
    const dataIndex = Math.round(relativeX * (series.length - 1));
    const dataPoint = series[Math.max(0, Math.min(dataIndex, series.length - 1))];
    
    if (!dataPoint) return;
    
    const pointX = xFunc(dataIndex);
    const pointY = yFunc(dataPoint.price);
    
    // Update hover elements
    hoverLine.setAttribute('x1', pointX);
    hoverLine.setAttribute('y1', padTop);
    hoverLine.setAttribute('x2', pointX);
    hoverLine.setAttribute('y2', padTop + chartH);
    hoverLine.style.display = 'block';
    
    hoverDot.setAttribute('cx', pointX);
    hoverDot.setAttribute('cy', pointY);
    hoverDot.style.display = 'block';
    
    // Update tooltip
    const date = new Date(dataPoint.ts);
    const formattedDate = date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric',
      year: 'numeric'
    });
    const formattedPrice = money(dataPoint.price, currencyFor(market));
    
    tooltip.html(`<div><strong>${formattedPrice}</strong></div><div>${formattedDate}</div>`);
    tooltip.css({
      display: 'block',
      left: e.clientX - rect.left + 10,
      top: e.clientY - rect.top - 10
    });
  });
  
  overlay.addEventListener('mouseleave', function() {
    hoverLine.style.display = 'none';
    hoverDot.style.display = 'none';
    tooltip.css('display', 'none');
  });
}