-- ========================================
-- EPRO DATABASE FIX FOR LINUX SERVER
-- ========================================
-- Run these SQL commands on your defaultdb database
-- This fixes: missing tables and missing products
-- Database: defaultdb | User: likely 'epro' or 'root'

-- ========== PHASE 1: CORE TABLES (CHECK IF EXISTS) ==========

-- 1. CREATE MISSING FEEDBACK TABLE
CREATE TABLE IF NOT EXISTS feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_name VARCHAR(100) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CREATE MISSING PRODUCT_REACTIONS TABLE (for Like/Wishlist button)
CREATE TABLE IF NOT EXISTS product_reactions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    user_ip VARCHAR(45) DEFAULT NULL,
    reaction_type ENUM('like', 'wishlist') DEFAULT 'wishlist',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_reaction (product_id, user_id, user_ip),
    KEY product_id (product_id),
    KEY user_id (user_id),
    CONSTRAINT reactions_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT reactions_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========== PHASE 2: REVIEWS & DISCOUNTS ==========

-- 3. CREATE MISSING PRODUCT_REVIEWS TABLE
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    rating INT(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    CONSTRAINT reviews_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ADD MRP COLUMN FOR DISCOUNTS (if not exists)
ALTER TABLE products ADD COLUMN IF NOT EXISTS mrp DECIMAL(10,2) DEFAULT NULL AFTER price;

-- ========== PHASE 3: COUPONS & PAYMENTS ==========

-- 5. CREATE MISSING COUPONS TABLE
CREATE TABLE IF NOT EXISTS coupons (
    id INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percent', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT(11) DEFAULT NULL,
    usage_count INT(11) DEFAULT 0,
    expiry_date DATETIME NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code_unique (code),
    INDEX status_idx (status),
    INDEX expiry_idx (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. ENHANCE ORDERS TABLE WITH PAYMENT DETAILS (ALTER if columns don't exist)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT NULL AFTER payment_status;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER coupon_code;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS utr_number VARCHAR(50) DEFAULT NULL AFTER discount_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'UPI' AFTER utr_number;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_status VARCHAR(50) DEFAULT 'pending' AFTER payment_method;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address TEXT DEFAULT NULL AFTER order_status;

-- ========== PHASE 4: ORDER ITEMS & TRACKING ==========

-- 7. CREATE MISSING ORDER_ITEMS TABLE
CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT(11) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY order_id (order_id),
    KEY product_id (product_id),
    CONSTRAINT order_items_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT order_items_ibfk_2 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ADD MISSING COLUMNS TO USERS
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT NULL AFTER email;

-- 9. ADD MISSING COLUMNS TO ORDERS
ALTER TABLE orders ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) DEFAULT 0 AFTER total_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0 AFTER discount_amount;

-- ========== DATA: ADD TEST PRODUCTS ==========

-- 10. DELETE DUPLICATE/INCOMPLETE PRODUCTS (optional - run only if needed)
-- DELETE FROM products WHERE image IS NULL OR image = '' OR image = 'product2.png';

-- 11. INSERT TEST PRODUCTS WITH PROPER IMAGE PATHS
-- Images should be in: /var/www/html/assets/images/product1/, product2/, product3/, product4/
INSERT INTO products (name, price, mrp, image, category, is_special) VALUES 
('E-Pro Stylish Hoody', 3500.00, 4500.00, 'product1/product1.jpg', 'Clothing', 1),
('E-Pro Premium T-Shirt', 1500.00, 2000.00, 'product2/product2.jpg', 'Clothing', 1),
('E-Pro Casual Jeans', 2000.00, 2800.00, 'product3/product3.jpg', 'Clothing', 0),
('E-Pro Sports Cap', 800.00, 1200.00, 'product4/product4.jpg', 'Accessories', 0)
ON DUPLICATE KEY UPDATE 
    price = VALUES(price), 
    mrp = VALUES(mrp), 
    image = VALUES(image), 
    category = VALUES(category), 
    is_special = VALUES(is_special);

-- ========== VERIFICATION ==========

-- 12. CHECK IF ALL TABLES AND DATA ARE PRESENT
SELECT COUNT(*) as product_count FROM products;
SELECT COUNT(*) as feedback_count FROM feedback;
SELECT COUNT(*) as reactions_count FROM product_reactions;
SELECT COUNT(*) as reviews_count FROM product_reviews;
SELECT COUNT(*) as coupons_count FROM coupons;

-- 13. SHOW ALL PRODUCTS TO VERIFY
SELECT id, name, price, mrp, image, category, is_special FROM products;
