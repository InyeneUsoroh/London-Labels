<?php
// Railway Master Migration Script
// This script will build your entire database structure without needing external .sql files.

require 'bootstrap.php';
$pdo = get_pdo();

$sql = "
-- 1. Categories
CREATE TABLE IF NOT EXISTS Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Products
CREATE TABLE IF NOT EXISTS Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    source_label VARCHAR(120) DEFAULT 'London, UK',
    condition_label VARCHAR(50) DEFAULT 'New',
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    tags TEXT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_new_arrival TINYINT(1) DEFAULT 0,
    exclude_from_new_arrival TINYINT(1) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Product_Images
CREATE TABLE IF NOT EXISTS Product_Images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Product_Variants
CREATE TABLE IF NOT EXISTS Product_Variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_type ENUM('clothing','footwear','one_size') DEFAULT 'clothing',
    size VARCHAR(40) NOT NULL,
    quantity INT DEFAULT 0,
    price_modifier DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Data Injection
INSERT INTO Categories (name, description) VALUES ('Fashion', 'Premium London Collection') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO Products (name, description, category_id, price, quantity, is_featured) 
    VALUES ('Vintage Denim Jacket', 'Authentic London design.', 1, 45000.00, 10, 1) 
    ON DUPLICATE KEY UPDATE name=name;
";

try {
    // Railway MySQL sometimes requires splitting multiple queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $query) {
        if ($query !== '') {
            $pdo->exec($query);
        }
    }
    echo "<div style='font-family:sans-serif;padding:40px;text-align:center;'>";
    echo "<h1 style='color:#e8357e;'>✅ DEPLOYMENT SUCCESSFUL!</h1>";
    echo "<p style='font-size:18px;color:#333;'>Your boutique's brain has been initialized on the live server.</p>";
    echo "<a href='index.php' style='display:inline-block;padding:12px 24px;background:#e8357e;color:#fff;text-decoration:none;border-radius:8px;'>Visit Homepage</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='font-family:sans-serif;padding:40px;'>";
    echo "<h1 style='color:#dc2626;'>❌ Error:</h1>";
    echo "<pre style='background:#fef2f2;padding:20px;border-radius:8px;'>" . $e->getMessage() . "</pre>";
    echo "</div>";
}
