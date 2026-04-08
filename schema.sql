-- London Labels E-Commerce Web Application
-- Database Schema
-- Generated from ER Model (3NF Normalized)

-- Drop existing tables (for fresh setup)
DROP TABLE IF EXISTS Product_Review_Helpful;
DROP TABLE IF EXISTS Order_Items;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Product_Reviews;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS Product_Images;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS Users;

-- ===== CATEGORIES TABLE =====
CREATE TABLE Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== USERS TABLE =====
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    default_shipping_address VARCHAR(255),
    default_address_line2 VARCHAR(255),
    default_city VARCHAR(50),
    default_postal_code VARCHAR(20),
    default_country VARCHAR(50),
    delivery_notes TEXT,
    comm_order_updates TINYINT(1) NOT NULL DEFAULT 1,
    comm_promos TINYINT(1) NOT NULL DEFAULT 0,
    role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    profile_image_url VARCHAR(255),
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PASSWORD RESETS TABLE =====
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_user_id (user_id),
    INDEX idx_password_resets_token (token),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCTS TABLE =====
CREATE TABLE Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    source_label VARCHAR(120) NOT NULL DEFAULT 'London, United Kingdom',
    condition_label VARCHAR(50) NOT NULL DEFAULT 'New',
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id)
        ON DELETE RESTRICT,
    INDEX idx_category (category_id),
    INDEX idx_name (name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCT_IMAGES TABLE =====
CREATE TABLE Product_Images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ORDERS TABLE =====
CREATE TABLE Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    payment_method ENUM('cash','card','transfer') DEFAULT 'cash',
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    shipping_address VARCHAR(255),
    city VARCHAR(50),
    postal_code VARCHAR(20),
    phone VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE RESTRICT,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_date (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ORDER_ITEMS TABLE =====
CREATE TABLE Order_Items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    UNIQUE KEY unique_order_product (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES Orders(order_id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE RESTRICT,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCT_REVIEWS TABLE =====
CREATE TABLE Product_Reviews (
    review_id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id           INT NOT NULL,
    user_id              INT NOT NULL,
    rating               TINYINT NOT NULL,
    title                VARCHAR(120) NULL,
    review_text          TEXT NOT NULL,
    media_url            VARCHAR(255) NULL,
    status               ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    is_verified_purchase TINYINT(1) NOT NULL DEFAULT 1,
    approved_by          INT NULL,
    approved_at          DATETIME NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_product_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_product_user_review (product_id, user_id),
    INDEX idx_reviews_product_status (product_id, status, created_at),
    INDEX idx_reviews_status (status),
    INDEX idx_reviews_user (user_id),
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_approved_by FOREIGN KEY (approved_by) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Product_Review_Helpful (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    is_helpful TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_user_vote (review_id, user_id),
    INDEX idx_helpful_review (review_id, is_helpful),
    INDEX idx_helpful_user (user_id),
    FOREIGN KEY (review_id) REFERENCES Product_Reviews(review_id)
        ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== SAMPLE DATA =====
-- Sample Categories
INSERT INTO Categories (name, description) VALUES
('Fashion', 'Thrift and affordable fashion items'),
('Technology', 'Electronics and tech gadgets'),
('Accessories', 'Bags, belts, and other accessories');

-- Sample Admin User (password: admin123)
INSERT INTO Users (username, email, password_hash, role) VALUES
('admin', 'admin@londonlabels.com', '$2y$10$92IY.TQKQ35NyORBfGqXsuIvPrL/.EzTW/BgqCrUQpz/rVh0nO6j.', 'admin');

-- Sample Customer User (password: customer123)
INSERT INTO Users (username, email, password_hash, role) VALUES
('customer', 'customer@example.com', '$2y$10$sV0eNLVRFHnPqDuNLnEJ1uLarqGNhvxFMnBo5R4qzRJMQT/x4QJSy', 'customer');

-- Sample Products
INSERT INTO Products (name, description, category_id, price, quantity) VALUES
('Vintage Denim Jacket', 'Classic blue vintage denim jacket in excellent condition', 1, 45.99, 10),
('Designer Handbag', 'Premium designer handbag with leather finish', 1, 89.99, 5),
('Wireless Earbuds', 'High-quality wireless earbuds with noise cancellation', 2, 79.99, 15),
('USB-C Hub', 'Multi-port USB-C hub for laptops and devices', 2, 39.99, 8),
('Leather Belt', 'Genuine brown leather belt with classic buckle', 3, 24.99, 20);


