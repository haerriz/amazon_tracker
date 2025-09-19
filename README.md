# Amazon Price Tracker

A complete Amazon price tracking application with real-time price monitoring, affiliate integration, and interactive charts.

## 🚀 Features

- **Real Amazon Price Scraping** - Fetches actual product prices
- **Price History Charts** - Interactive SVG charts with hover details
- **Price Alerts** - Get notified when products hit target prices
- **Affiliate Integration** - Automatic affiliate link generation
- **Multi-Theme Support** - Light, dark, and system themes
- **Multi-Environment** - Works on local development and production
- **Responsive Design** - Material Design UI that works on all devices

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (jQuery), Material Design
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Charts**: Custom SVG with interactive features
- **Scraping**: cURL with rotating user agents

## 📦 Quick Setup

### Local Development
```bash
# 1. Clone repository
git clone https://github.com/haerriz/amazon_tracker.git
cd amazon_tracker

# 2. Create database
mysql -u root -p -e "CREATE DATABASE price_tracker"

# 3. Run setup
http://localhost/amazon_tracker/backend/setup.php
```

### Production Deployment
```bash
# 1. Upload files to your hosting
# 2. Run setup script
https://yourdomain.com/backend/setup.php

# 3. Add cron job for price updates
*/30 * * * * php /path/to/backend/cron/update_prices.php
```

## 🔧 Configuration

The system automatically detects environment:
- **Local**: `localhost` → Uses local database settings
- **Production**: Any other domain → Uses production settings

Edit `backend/config/config.php` for custom configuration.

## 📱 Usage

1. **Add Products**: Paste any Amazon URL or ASIN
2. **Set Alerts**: Enter target price for notifications
3. **View Charts**: Interactive price history with multiple time ranges
4. **Affiliate Links**: All buy buttons include your affiliate tag

## 🔗 Supported URLs

- Direct ASIN: `B09G9BL5CP`
- Product URLs: `https://amazon.in/dp/B09G9BL5CP`
- Short URLs: `https://amzn.to/3IjXoMI`
- Complex URLs: Full Amazon URLs with tracking parameters

## 📊 API Endpoints

- `GET /backend/api/products.php` - Get all products
- `POST /backend/api/products.php/add` - Add new product
- `GET /backend/api/products.php/{asin}/history` - Price history
- `PUT /backend/api/products.php/{asin}/alert` - Set price alert

## ⚖️ Legal Notice

This tool is for educational purposes. Please respect Amazon's Terms of Service and use responsibly with appropriate rate limiting.

## 📄 License

MIT License - see LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📞 Support

For issues and questions, please open a GitHub issue or contact [haerriz](https://github.com/haerriz).

---

**⭐ Star this repo if you find it useful!**