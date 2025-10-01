// Helper Functions

// Affiliate configuration
const AFFILIATE_TAGS = {
  'IN': 'haerriz06-21',
  'US': 'haerriz06-20',  // You'll need US tag if you want US affiliate links
  'UK': 'haerriz06-21'   // You'll need UK tag if you want UK affiliate links
};

function parseASIN(input) {
  if (!input) return null;
  
  // Clean input
  input = input.trim();
  
  // Direct ASIN check
  const re = /^[A-Z0-9]{10}$/i;
  if (re.test(input)) return input.toUpperCase();
  
  // Extract ASIN from various URL patterns
  const patterns = [
    // Standard Amazon URLs
    /\/dp\/([A-Z0-9]{10})(?:\/|\?|#|$)/i,
    /\/product\/([A-Z0-9]{10})(?:\/|\?|#|$)/i,
    /\/gp\/product\/([A-Z0-9]{10})(?:\/|\?|#|$)/i,
    
    // Query parameters
    /[?&]asin=([A-Z0-9]{10})/i,
    /[?&]pd_rd_i=([A-Z0-9]{10})/i,
    
    // Amazon domain patterns
    /amazon\.[a-z.]+\/.*\/dp\/([A-Z0-9]{10})/i,
    /amazon\.[a-z.]+\/([A-Z0-9]{10})(?:\/|\?|#|$)/i,
    
    // Short URLs
    /amzn\.to\/([A-Z0-9]{10})/i,
    /a\.co\/([A-Z0-9]{10})/i,
    
    // Any 10-character alphanumeric string that looks like ASIN
    /([A-Z0-9]{10})/i
  ];
  
  for (const pattern of patterns) {
    const match = input.match(pattern);
    if (match && match[1] && /^[A-Z0-9]{10}$/i.test(match[1])) {
      return match[1].toUpperCase();
    }
  }
  
  return null;
}

function currencyFor(market) {
  return market === 'IN' ? 'INR' : (market === 'US' ? 'USD' : 'GBP');
}

function getAffiliateUrl(asin, market, originalUrl = null) {
  const domains = {
    'IN': 'amazon.in',
    'US': 'amazon.com', 
    'UK': 'amazon.co.uk'
  };
  
  const domain = domains[market];
  const tag = AFFILIATE_TAGS[market];
  
  if (!domain || !tag) {
    return `https://amazon.in/dp/${asin}`;
  }
  
  let url = `https://${domain}/dp/${asin}?tag=${tag}`;
  
  // Preserve important Amazon parameters from original URL
  if (originalUrl) {
    try {
      const urlObj = new URL(originalUrl);
      const preserveParams = ['th', 'psc', 'ref', 'keywords'];
      
      preserveParams.forEach(param => {
        const value = urlObj.searchParams.get(param);
        if (value) {
          url += `&${param}=${encodeURIComponent(value)}`;
        }
      });
    } catch (e) {
      // Ignore URL parsing errors
    }
  }
  
  return url;
}

function money(amount, currency) {
  if (amount == null) return '—';
  
  try {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: currency
    }).format(amount);
  } catch (e) {
    return '₹' + Number(amount).toFixed(2);
  }
}

function genHistory(days, startPrice) {
  const out = [];
  let price = startPrice;
  const now = Date.now();
  
  for (let i = days - 1; i >= 0; i--) {
    const t = new Date(now - i * 86400000);
    price += (Math.random() - 0.5) * 3;
    price = Math.max(50, price);
    out.push({
      ts: t.toISOString(),
      price: Number(price.toFixed(2))
    });
  }
  
  return out;
}