// Environment detection and configuration
function getBaseUrl() {
  const hostname = window.location.hostname;
  const isLocal = hostname === 'localhost' || hostname === '127.0.0.1';
  
  if (isLocal) {
    return '/tracker';
  }
  
  // Production environment
  return '';
}

// API endpoints
const API = {
  products: () => `${getBaseUrl()}/backend/api/products.php`,
  addProduct: () => `${getBaseUrl()}/backend/api/add_product.php`,
  productHistory: (asin, market, days) => `${getBaseUrl()}/backend/api/products.php/${asin}/history?market=${market}&days=${days}`,
  setAlert: (asin) => `${getBaseUrl()}/backend/api/products.php/${asin}/alert`,
  deleteProduct: (asin, market) => `${getBaseUrl()}/backend/api/products.php/${asin}?market=${market}`
};