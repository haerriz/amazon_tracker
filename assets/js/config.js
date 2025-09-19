// Environment detection and configuration
function getBaseUrl() {
  const hostname = window.location.hostname;
  const isLocal = hostname === 'localhost' || hostname === '127.0.0.1';
  
  console.log('Current hostname:', hostname);
  console.log('Is local:', isLocal);
  
  return isLocal ? '/tracker' : '';
}

// API endpoints
const API = {
  products: () => {
    const url = `${getBaseUrl()}/backend/api/products.php`;
    console.log('Products API URL:', url);
    return url;
  },
  addProduct: () => {
    const url = `${getBaseUrl()}/backend/api/products.php/add`;
    console.log('Add Product API URL:', url);
    return url;
  },
  productHistory: (asin, market, days) => `${getBaseUrl()}/backend/api/products.php/${asin}/history?market=${market}&days=${days}`,
  setAlert: (asin) => `${getBaseUrl()}/backend/api/products.php/${asin}/alert`,
  deleteProduct: (asin, market) => `${getBaseUrl()}/backend/api/products.php/${asin}?market=${market}`
};