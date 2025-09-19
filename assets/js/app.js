// Theme Management
let currentTheme = localStorage.getItem('theme') || 'system';

function setTheme(theme) {
  currentTheme = theme;
  document.body.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
  
  const themeText = {
    'light': 'Light',
    'dark': 'Dark', 
    'system': 'Auto'
  };
  
  const themeIcons = {
    'light': 'wb_sunny',
    'dark': 'brightness_2',
    'system': 'brightness_6'
  };
  
  $('#themeText').text(themeText[theme]);
  $('#themeToggle i').text(themeIcons[theme]);
}

function toggleTheme() {
  const themes = ['system', 'light', 'dark'];
  const currentIndex = themes.indexOf(currentTheme);
  const nextTheme = themes[(currentIndex + 1) % themes.length];
  setTheme(nextTheme);
}

// Main Application Logic
$(document).ready(function() {
  // Initialize theme
  setTheme(currentTheme);
  
  // Initialize Materialize components
  $('select').formSelect();
  $('.modal').modal();
  
  // Load initial data
  loadProducts();
  
  // Real price updates disabled in demo mode
  // setInterval(updatePrices, 20000);
});

// Event Handlers

// Theme toggle
$('#themeToggle').on('click', toggleTheme);

// Add product button
$('#btnAdd').on('click', addProduct);

// Set target price
$(document).on('click', '.set-target', function() {
  const productId = $(this).data('id');
  setTarget(productId);
});

// Remove product
$(document).on('click', '.remove-btn', function() {
  const productId = $(this).data('id');
  removeProduct(productId);
});

// Chart time range selection
$(document).on('click', '.range', function() {
  const productId = $(this).data('id');
  const days = Number($(this).data('days'));
  
  // Ensure productId is a string
  const productIdStr = String(productId);
  
  // Store the selected time range for this product
  productTimeRanges[productIdStr] = days;
  
  // Update active state for this product's chips
  $(`.range[data-id="${productId}"]`).removeClass('active');
  $(this).addClass('active');
  
  // Extract ASIN and market from productId
  const parts = productIdStr.split('-');
  const market = parts.pop();
  const asin = parts.join('-');
  
  // Reload chart with new time range
  loadChart(asin, market, days);
});

// Export data
$('#btnExport').on('click', exportData);

// Import data
$('#fileImport').on('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  importData(file);
});