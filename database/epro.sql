-- 1. Users Table
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Admin Table
CREATE TABLE admin (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin insert
INSERT INTO admin (username, password) VALUES ('admin', 'admin123');

-- 3. Products Table (is_special column ke saath)
CREATE TABLE products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'General',
    is_special INT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Cart Table
CREATE TABLE cart (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    product_id INT(11) DEFAULT NULL,
    quantity INT(11) DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY product_id (product_id),
    CONSTRAINT cart_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT cart_ibfk_2 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Feedback Table
CREATE TABLE feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_name VARCHAR(100) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Orders Table
CREATE TABLE orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    total_amount DECIMAL(10,2) DEFAULT NULL,
    payment_status VARCHAR(50) DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    CONSTRAINT orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Product2 wali row add karna
INSERT INTO products (name, price, image, category, is_special) 
VALUES ('E-Pro Stylish Hoody', 3500.00, 'product2.png', 'Clothing', 1);

-- 8. Product Reactions (Wishlist) Table
CREATE TABLE product_reactions (
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

-- ========== PHASE 2 MIGRATIONS ==========

-- 9. Alter Products Table - Add MRP (Original Price) for Discounts
ALTER TABLE products ADD COLUMN mrp DECIMAL(10,2) DEFAULT NULL AFTER price;
-- UPDATE products SET mrp = price WHERE mrp IS NULL; -- Run this to initialize existing products

-- 10. Product Reviews Table
CREATE TABLE product_reviews (
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

-- ========== PHASE 3 MIGRATIONS ==========

-- 11. Coupons / Promo Codes Table
CREATE TABLE coupons (
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

-- 12. Enhance Orders Table with Payment Details
ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER payment_status;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER coupon_code;
ALTER TABLE orders ADD COLUMN utr_number VARCHAR(50) DEFAULT NULL AFTER discount_amount;
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'UPI' AFTER utr_number;
ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'pending' AFTER payment_method;
ALTER TABLE orders ADD COLUMN delivery_address TEXT DEFAULT NULL AFTER order_status;

-- ========== PHASE 4 MIGRATIONS ==========

-- 13. Order Items Table (Track products in each order)
CREATE TABLE order_items (
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

-- 14. Add phone field to users table (if not exists)
ALTER TABLE users ADD COLUMN phone VARCHAR(15) DEFAULT NULL AFTER email;

-- 15. Add tax tracking to orders
ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0 AFTER discount_amount;
ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0 AFTER total_amount;