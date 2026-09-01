# 🔴 EPRO LINUX SERVER - CRITICAL DATABASE FIXES

**Date:** 2026-09-01  
**Status:** 3 Critical Issues Identified  
**Affected Server:** defaultdb database on Linux server (`/var/www/html/`)

---

## 🆘 Issues Reported

| Issue | Cause | Fix |
|-------|-------|-----|
| ❌ Feedback page crashes | `feedback` table missing from database | Create table |
| ❌ Only 1 product showing | Database has only 1 product | Insert 4 test products |
| ❌ Product images not visible | Images not created or wrong paths in DB | Upload images & verify paths |
| ❌ Like button not working | `product_reactions` table missing | Create table |

---

## ✅ STEP-BY-STEP FIX

### **Step 1: Access Your Database**

**Option A: Using phpMyAdmin** (Recommended for beginners)
```
1. Open your hosting control panel (cPanel, Plesk, etc.)
2. Click "phpMyAdmin"
3. Select database "defaultdb" from left sidebar
4. Click "SQL" tab at top
5. Copy-paste the entire content from LINUX_SERVER_FIX.sql (see below)
6. Click "Go" or "Execute" button
```

**Option B: Using MySQL Command Line** (For advanced users)
```bash
# Login to MySQL
mysql -h localhost -u epro_user -p defaultdb

# Paste entire SQL script from LINUX_SERVER_FIX.sql
# Press Ctrl+D to exit
```

### **Step 2: Run The Complete SQL Fix**

Copy the entire SQL script below and execute in phpMyAdmin:

```sql
-- ========================================
-- EPRO DATABASE FIX FOR LINUX SERVER
-- ========================================

-- 1. CREATE MISSING FEEDBACK TABLE
CREATE TABLE IF NOT EXISTS feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_name VARCHAR(100) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CREATE MISSING PRODUCT_REACTIONS TABLE (Like/Wishlist)
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

-- 4. ADD MRP COLUMN FOR DISCOUNTS
ALTER TABLE products ADD COLUMN IF NOT EXISTS mrp DECIMAL(10,2) DEFAULT NULL AFTER price;

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

-- 6. ENHANCE ORDERS TABLE WITH PAYMENT DETAILS
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT NULL AFTER payment_status;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER coupon_code;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS utr_number VARCHAR(50) DEFAULT NULL AFTER discount_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'UPI' AFTER utr_number;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_status VARCHAR(50) DEFAULT 'pending' AFTER payment_method;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address TEXT DEFAULT NULL AFTER order_status;

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

-- 8. ADD MISSING COLUMNS TO USERS TABLE
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT NULL AFTER email;

-- 9. ADD MISSING COLUMNS TO ORDERS TABLE
ALTER TABLE orders ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) DEFAULT 0 AFTER total_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0 AFTER discount_amount;

-- 10. INSERT TEST PRODUCTS
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

-- 11. VERIFY DATA
SELECT COUNT(*) as product_count FROM products;
SELECT COUNT(*) as feedback_count FROM feedback;
SELECT COUNT(*) as reactions_count FROM product_reactions;
SELECT id, name, price, mrp, image, category FROM products;
```

### **Step 3: Verify The Fix**

After running the SQL, you should see:
- ✅ **Product Count:** 4 (or more if you had existing products)
- ✅ **Feedback Count:** 0 (empty table, ready for feedback)
- ✅ **Reactions Count:** 0 (empty table, ready for likes)

Output should show:
```
id | name                    | price  | mrp    | image                 | category
1  | E-Pro Stylish Hoody     | 3500   | 4500   | product1/product1.jpg | Clothing
2  | E-Pro Premium T-Shirt   | 1500   | 2000   | product2/product2.jpg | Clothing
3  | E-Pro Casual Jeans      | 2000   | 2800   | product3/product3.jpg | Clothing
4  | E-Pro Sports Cap        | 800    | 1200   | product4/product4.jpg | Accessories
```

---

## 🖼️ STEP 4: FIX MISSING PRODUCT IMAGES

The products now reference images in:
- `/var/www/html/assets/images/product1/product1.jpg`
- `/var/www/html/assets/images/product2/product2.jpg`
- `/var/www/html/assets/images/product3/product3.jpg`
- `/var/www/html/assets/images/product4/product4.jpg`

### **Check if images folder exists:**
```bash
# SSH into your server
ssh user@your-hosting.com

# Check if folders exist
ls -la /var/www/html/assets/images/

# You should see:
# product1/
# product2/
# product3/
# product4/
```

### **If folders are missing, create them:**
```bash
mkdir -p /var/www/html/assets/images/product1
mkdir -p /var/www/html/assets/images/product2
mkdir -p /var/www/html/assets/images/product3
mkdir -p /var/www/html/assets/images/product4
```

### **Upload Images:**

You can either:

**Option 1: Via FTP Client (FileZilla)**
1. Download FileZilla
2. Connect: FTP credentials from your hosting
3. Navigate to `/var/www/html/assets/images/product1/`
4. Upload `product1.jpg` (any JPG image)
5. Repeat for product2, product3, product4

**Option 2: Via Web File Manager (cPanel)**
1. Login to cPanel
2. Click "File Manager"
3. Navigate to `/public_html/assets/images/`
4. Upload product images to respective folders

**Option 3: Create placeholder images (Quick Test)**
```bash
# Use ImageMagick to create placeholder images
convert -size 300x300 xc:skyblue /var/www/html/assets/images/product1/product1.jpg
convert -size 300x300 xc:lightgreen /var/www/html/assets/images/product2/product2.jpg
convert -size 300x300 xc:salmon /var/www/html/assets/images/product3/product3.jpg
convert -size 300x300 xc:yellow /var/www/html/assets/images/product4/product4.jpg
```

---

## 🧪 STEP 5: TEST YOUR FIXES

### **Test 1: Feedback Page (Should Not Crash)**
```
Visit: http://your-domain.com/user/feedback.php
Expected: Feedback form loads without error
```

### **Test 2: Products Page (Should Show 4 Products)**
```
Visit: http://your-domain.com/user/products.php
Expected: 4 product cards with images, prices, and like buttons
```

### **Test 3: Like Button Works**
```
On any product: Click the heart icon in top-right corner
Expected: Heart fills with red color (becomes solid)
```

### **Test 4: Cart Works**
```
On any product: Click "Add to Cart"
Expected: Cart count in navbar increases
```

---

## 🚨 TROUBLESHOOTING

### **Problem: "Table 'defaultdb.feedback' doesn't exist"**
**Solution:** Run Step 2 SQL fix again (SQL statement 1)

### **Problem: "Only 1 product still showing"**
**Solution:** 
1. Check Step 3 output - should show 4 products
2. Clear browser cache (Ctrl+Shift+Delete)
3. Refresh page (Ctrl+F5)

### **Problem: "Product images still not showing"**
**Solution:**
1. Check Step 4 - verify image files exist
2. Check file permissions: `chmod 644 /var/www/html/assets/images/*/product*.jpg`
3. Check image paths in database match actual files

### **Problem: "Like button still not showing"**
**Solution:**
1. Check if `product_reactions` table created (Step 2, SQL 2)
2. Check browser developer console for JavaScript errors
3. Clear cache and hard refresh (Ctrl+Shift+R on Linux)

---

## 📝 QUICK CHECKLIST

After completing all steps, verify:

- [ ] Ran all SQL statements from Step 2
- [ ] SQL execution completed without errors
- [ ] Product count shows 4 (or more)
- [ ] Image folders created at `/var/www/html/assets/images/product1-4/`
- [ ] Product images uploaded to folders
- [ ] Feedback page loads without error
- [ ] Products page shows 4 products with images
- [ ] Like button appears on product cards
- [ ] Like button works (turns red when clicked)
- [ ] Cart functionality works

---

## 📞 IMPORTANT NOTES

**Database Connection Check:**
Your app uses: `/var/www/html/db_connect.php`

Make sure it has:
```php
$conn = new mysqli('localhost', 'epro_user', 'password', 'defaultdb');
```

Replace `epro_user` and `password` with your actual database credentials.

**File Permissions:**
```bash
# Make sure files are readable
chmod 755 /var/www/html/assets/images/
chmod 755 /var/www/html/assets/images/product*/
chmod 644 /var/www/html/assets/images/product*/*.jpg
```

---

## ✅ EXPECTED RESULT

After all fixes:
- ✅ All pages load without "table doesn't exist" errors
- ✅ Products page shows 4 different products
- ✅ Product images display correctly
- ✅ Like/Wishlist button works on all products
- ✅ Feedback page works
- ✅ Cart functionality works

---

**Status:** Ready for deployment  
**Time to fix:** ~15-20 minutes  
**Difficulty:** Beginner-Friendly
