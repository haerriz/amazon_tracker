# Deployment Guide

## Local Development Setup

1. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE price_tracker;
   ```

2. **Run Setup**
   ```
   http://localhost/tracker/backend/setup.php
   ```

3. **Configure Cron (Optional)**
   ```bash
   # Add to crontab
   */30 * * * * php /var/www/html/tracker/backend/cron/update_prices.php
   ```

## Production Server Setup (amazon-tracker.haerriz.com)

1. **Upload Files**
   - Upload entire `tracker` folder to your hosting

2. **Database Configuration**
   - Database: `u434561653_amazon_tracker`
   - Username: `u434561653_amazon_tracker`
   - Password: `Whatsapp@2026`

3. **Run Setup**
   ```
   https://amazon-tracker.haerriz.com/backend/setup.php
   ```

4. **Configure Cron Job in cPanel**
   ```
   */30 * * * * php /home/u434561653/public_html/backend/cron/update_prices.php
   ```

## Environment Detection

The system automatically detects:
- **Local**: `localhost`, `127.0.0.1` → Uses local database
- **Production**: Any other domain → Uses production database

## API Endpoints

- `GET /backend/api/products.php` - Get all products
- `POST /backend/api/products.php/add` - Add new product
- `GET /backend/api/products.php/{asin}/history` - Get price history
- `PUT /backend/api/products.php/{asin}/alert` - Set price alert

## Features

✅ **Real Amazon Price Scraping**
✅ **Automatic Price Updates** (every 30 minutes)
✅ **Price History Charts**
✅ **Price Alerts**
✅ **Affiliate Link Integration**
✅ **Multi-environment Support**