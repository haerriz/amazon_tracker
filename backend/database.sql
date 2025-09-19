-- For local development
-- CREATE DATABASE IF NOT EXISTS price_tracker;
-- USE price_tracker;

-- For production, database already exists: u434561653_amazon_tracker
-- Run this script in your production database

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asin VARCHAR(10) NOT NULL,
    market VARCHAR(2) NOT NULL,
    title VARCHAR(500),
    image_url VARCHAR(500),
    current_price DECIMAL(10,2),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product (asin, market)
);

CREATE TABLE price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_timestamp (product_id, timestamp)
);

CREATE TABLE price_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    target_price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);