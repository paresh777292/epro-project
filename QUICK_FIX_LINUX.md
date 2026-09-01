# ✅ EPRO LINUX SERVER - QUICK FIX GUIDE

## 🎯 Your 3 Problems:

1. **❌ Feedback Table Missing**
   - Error: "Table 'defaultdb.feedback' doesn't exist"
   - Fix: Create table

2. **❌ Only 1 Product Showing**
   - Issue: Database has only 1 product
   - Fix: Insert 4 test products

3. **❌ Like Button Not Working**
   - Missing: `product_reactions` table
   - Fix: Create table

---

## 📋 SIMPLE 2-MINUTE FIX

### **Step 1: Copy This SQL** 

```sql
-- CREATE MISSING FEEDBACK TABLE
CREATE TABLE IF NOT EXISTS feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_name VARCHAR(100) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE MISSING PRODUCT_REACTIONS (LIKE BUTTON)
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

-- ADD MISSING COLUMNS
ALTER TABLE products ADD COLUMN IF NOT EXISTS mrp DECIMAL(10,2) DEFAULT NULL AFTER price;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT NULL AFTER email;

-- INSERT 4 PRODUCTS
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
```

### **Step 2: Paste in phpMyAdmin**

1. Open **cPanel** → **phpMyAdmin**
2. Select database **defaultdb** (left side)
3. Click **SQL** tab
4. Paste the SQL above
5. Click **Go** ✅

---

## 🖼️ Image Problem - IMPORTANT!

After SQL runs, products need **image files** in these folders:

```
/var/www/html/assets/images/product1/product1.jpg
/var/www/html/assets/images/product2/product2.jpg
/var/www/html/assets/images/product3/product3.jpg
/var/www/html/assets/images/product4/product4.jpg
```

### **Quick: Use FileZilla (FTP)**

1. Download **FileZilla** (free)
2. Connect with FTP credentials:
   - Server: `ftp.your-domain.com`
   - Username: from hosting
   - Password: from hosting
3. Navigate to: `assets/images/`
4. Create folders: `product1`, `product2`, `product3`, `product4`
5. Upload any JPG images to each folder

**That's it!** ✅

---

## ✅ VERIFY EVERYTHING WORKS

Visit these URLs:

1. **Feedback Page:**
   ```
   http://your-domain.com/user/feedback.php
   ```
   ✅ Should load without error

2. **Products Page:**
   ```
   http://your-domain.com/user/products.php
   ```
   ✅ Should show 4 products with images
   ✅ Heart like button visible

3. **Click Heart Button:**
   ✅ Should turn RED when clicked

---

## 💡 If Images Don't Show:

File sizes must be correct:
- Min: 100x100 px
- Best: 300x300 px or bigger

Use these free sites to find images:
- unsplash.com
- pexels.com
- pixabay.com

---

## 🆘 Still Getting Error?

Run FULL fix from: **LINUX_SERVER_FIX.sql**

See full guide: **LINUX_SERVER_FIXES.md**

---

**Time:** 2-5 minutes ⏱️  
**Difficulty:** Very Easy ✅  
**Result:** All 3 problems solved! 🎉
