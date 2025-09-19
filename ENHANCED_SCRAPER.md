# Enhanced Amazon Scraper

## Overview

The enhanced Amazon scraper extracts comprehensive product information that rivals top competitors like Keepa, CamelCamelCamel, and Honey. It provides detailed product data, market insights, and intelligent recommendations.

## 🚀 Key Features

### Basic Product Information
- **Product Title & ASIN** - Clean, formatted product names
- **Current & Original Prices** - Real-time pricing with currency support
- **Discount Percentage** - Automatic discount calculation
- **Multiple Product Images** - High-quality product images
- **Brand & Category** - Product classification and brand identification

### Advanced Product Data
- **Customer Ratings** - Star ratings with decimal precision
- **Review Count** - Total customer reviews
- **Availability Status** - Real-time stock information
- **Prime Eligibility** - Amazon Prime benefits
- **Seller Information** - Merchant details and reliability
- **Product Variants** - Size, color, and other variations
- **Product Features** - Key bullet points and specifications
- **Delivery Information** - Shipping details and timelines
- **Coupon Information** - Available discounts and offers

### Market Intelligence
- **Price Analysis** - Comprehensive pricing breakdown
- **Market Position** - Product ranking and popularity
- **Deal Quality Assessment** - Value proposition analysis
- **Purchase Recommendations** - AI-powered buying advice
- **Confidence Scoring** - Recommendation reliability metrics
- **Price Trend Estimation** - Historical price movement patterns

### Technical Features
- **Rotating User Agents** - Avoid detection with realistic browser headers
- **Rate Limiting** - Configurable delays between requests
- **Retry Logic** - Exponential backoff for failed requests
- **Fallback Data** - Realistic mock data when scraping fails
- **Multi-Market Support** - India, US, UK Amazon stores
- **JSON Structured Output** - Clean, organized data format

## 📊 Data Structure

```json
{
  "asin": "B09G9BL5CP",
  "title": "Apple iPhone 14 (128 GB) - Blue",
  "price": 69900,
  "original_price": 79900,
  "discount": 12,
  "rating": 4.5,
  "review_count": 1250,
  "availability": "In Stock",
  "brand": "Apple",
  "prime_eligible": true,
  "seller": "Amazon",
  "images": ["url1", "url2"],
  "features": ["Feature 1", "Feature 2"],
  "price_analysis": {
    "current_price": 69900,
    "savings_amount": 10000,
    "price_tier": "Premium",
    "deal_quality": "Good Deal"
  },
  "recommendation": {
    "level": "Recommended",
    "action": "BUY",
    "message": "Good product with decent value",
    "confidence": 85,
    "factors": ["Excellent rating", "Good discount"]
  },
  "market_insights": {
    "popularity_score": 95,
    "market_position": "Market Leader",
    "seller_reliability": "High"
  }
}
```

## 🛠️ Usage

### Direct API Call
```php
$scraper = new EnhancedProductScraper();
$data = $scraper->getEnhancedProductData('B09G9BL5CP', 'IN');
```

### REST API Endpoint
```bash
GET /backend/api/enhanced_scraper.php?asin=B09G9BL5CP&market=IN
```

### Batch Enhancement
```bash
# Enhance all products
php backend/scripts/enhance_products.php

# Enhance specific product
php backend/scripts/enhance_products.php single B09G9BL5CP IN

# View statistics
php backend/scripts/enhance_products.php stats
```

## 🔧 Configuration

### Rate Limiting
```php
$scraper->setRequestDelay(2); // 2 seconds between requests
```

### Proxy Support
```php
$scraper->setProxies(['proxy1:port', 'proxy2:port']);
```

## 📈 Recommendation System

The scraper includes an intelligent recommendation system that analyzes multiple factors:

### Scoring Factors
- **Customer Rating** (0-30 points) - Based on star rating
- **Review Count** (0-20 points) - Product popularity
- **Discount** (0-25 points) - Deal attractiveness
- **Availability** (0-15 points) - Stock status
- **Prime Eligibility** (0-10 points) - Shipping benefits

### Recommendation Levels
- **Highly Recommended** (80+ points) - Excellent value, buy now
- **Recommended** (60-79 points) - Good product, safe to buy
- **Consider** (40-59 points) - Average, evaluate needs
- **Wait** (20-39 points) - Below average, wait for better deals
- **Not Recommended** (<20 points) - Poor value, avoid

## 🎯 Competitive Advantages

### vs Keepa
- ✅ Real-time scraping (no API limits)
- ✅ Comprehensive product data
- ✅ Built-in recommendation engine
- ✅ Multi-market support

### vs CamelCamelCamel
- ✅ Enhanced product information
- ✅ Market intelligence insights
- ✅ Seller reliability scoring
- ✅ Prime eligibility detection

### vs Honey
- ✅ Advanced price analysis
- ✅ Deal quality assessment
- ✅ Confidence scoring
- ✅ Technical specifications

## 🔒 Best Practices

### Rate Limiting
- Minimum 1-2 seconds between requests
- Use exponential backoff for retries
- Implement random delays for natural patterns

### User Agents
- Rotate between realistic browser user agents
- Update user agents regularly
- Include mobile and desktop variants

### Error Handling
- Graceful fallback to mock data
- Comprehensive logging
- Retry failed requests with delays

### Data Validation
- Validate extracted prices and ratings
- Check for reasonable data ranges
- Implement data quality scoring

## 🚀 Testing

Visit `/test-enhanced-scraper.php` to test the scraper with sample ASINs and see comprehensive product data extraction in action.

## 📝 Legal Compliance

- Respect robots.txt guidelines
- Implement appropriate rate limiting
- Use for educational/personal purposes
- Follow Amazon's Terms of Service
- Consider using official APIs when available

## 🔄 Updates

The scraper is designed to be easily maintainable:
- Modular extraction methods
- Configurable selectors
- Fallback data generation
- Comprehensive error handling

Regular updates ensure compatibility with Amazon's changing page structure and continued reliable data extraction.