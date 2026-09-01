# PHASE 3 - QUICK START GUIDE
## UPI Payment & Coupon System Implementation

---

## ⚡ 5-Minute Setup

### 1️⃣ Run Database Migration
```sql
-- Execute in your MySQL database
-- File: database/epro.sql (already contains the full migration)

-- Add coupons table
CREATE TABLE coupons (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percent', 'fixed'),
    discount_value DECIMAL(10,2),
    min_order DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT(11) DEFAULT NULL,
    usage_count INT(11) DEFAULT 0,
    expiry_date DATETIME,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY code_unique (code),
    INDEX status_idx (status),
    INDEX expiry_idx (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhance orders table
ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN utr_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'UPI';
ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN delivery_address TEXT DEFAULT NULL;
```

### 2️⃣ Deploy Files
Copy these files to your EPRO directory:
```
api/
├── apply_coupon.php       (NEW - Coupon validation)
└── order_handler.php      (NEW - Order & payment verification)

user/
└── payment.php            (UPDATED - Full Phase 3 payment system)

includes/
└── cart_drawer.php        (UPDATED - Coupon input added)

database/
└── epro.sql               (UPDATED - Coupons table & orders enhancement)
```

### 3️⃣ Configure UPI ID
Edit `user/payment.php` around line 135:
```php
$UPI_ID = 'your_merchant_upi@bankname'; // CHANGE THIS!
```

### 4️⃣ Test with Sample Coupon
```sql
-- Insert test coupon
INSERT INTO coupons (code, discount_type, discount_value, min_order, expiry_date, status)
VALUES ('TEST20', 'percent', 20, 500, '2025-12-31 23:59:59', 'active');
```

### 5️⃣ Verify Integration
- ✅ Add product to cart → Cart drawer opens
- ✅ Enter "TEST20" coupon → Should show 20% discount
- ✅ Click checkout → Payment page loads
- ✅ See order summary with discount applied

---

## 🎯 Key Integration Points

### In Your Header/Footer:
```php
<!-- Include Toast System (for notifications) -->
<script src="/EPRO/assets/js/toast.js"></script>

<!-- Include Cart Drawer (at bottom of body) -->
<?php include __DIR__ . '/../includes/cart_drawer.php'; ?>
```

### In Products Page:
```html
<!-- "Add to Cart" button should trigger: -->
<button onclick="addToCart(productId)">Add to Cart</button>

<!-- JavaScript function (already in cart_drawer.js): -->
<script>
function addToCart(productId) {
    // AJAX call to cart_handler.php
    // Opens drawer automatically
    // Shows toast notification
}
</script>
```

---

## 📱 Mobile vs Desktop Flows

### Mobile User:
1. Adds product to cart
2. Opens payment page
3. Sees 4 UPI app buttons
4. Clicks GPay/PhonePe/Paytm
5. Completes payment in app
6. Returns to page
7. Enters UTR number
8. Order confirmed ✅

### Desktop User:
1. Adds product to cart
2. Opens payment page
3. Sees dynamic QR code
4. Scans with phone
5. Completes payment
6. Enters UTR number
7. Order confirmed ✅

---

## 🧪 Quick Test Checklist

**Coupon Testing:**
- [ ] Create coupon: `INSERT INTO coupons ...`
- [ ] Add to cart
- [ ] Apply coupon → shows discount
- [ ] Remove coupon → discount removed
- [ ] Test expired coupon → error message
- [ ] Test min order not met → error message

**Payment Testing:**
- [ ] Mobile: Test UPI app links
- [ ] Desktop: QR code loads (Google Chart API)
- [ ] Form validation works
- [ ] UTR validation (12+ digits required)
- [ ] Order created in database
- [ ] Cart cleared after payment

---

## 🔧 Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| Coupon not applying | Check expiry date & min order in DB |
| QR not showing | Verify Google Chart API accessible (HTTPS) |
| UTR not accepted | Must be 12+ digits, numeric only |
| Cart not clearing | Check order_handler.php error logs |
| UPI link not working | Verify UPI ID format: `user@bankname` |

---

## 📊 Database Queries

**Create sample coupons:**
```sql
-- 20% off for orders over ₹500
INSERT INTO coupons VALUES 
(NULL, 'WELCOME20', 'percent', 20, 500, NULL, NULL, 0, '2025-12-31 23:59:59', 'active', NOW());

-- ₹100 off for orders over ₹1000
INSERT INTO coupons VALUES 
(NULL, 'FLAT100', 'fixed', 100, 1000, NULL, NULL, 0, '2025-12-31 23:59:59', 'active', NOW());

-- 50% but max ₹500 discount
INSERT INTO coupons VALUES 
(NULL, 'MEGA50', 'percent', 50, 500, 500, NULL, 0, '2025-12-31 23:59:59', 'active', NOW());
```

**Check applied coupons:**
```sql
-- Orders with coupons
SELECT order_id, total_amount, coupon_code, discount_amount, utr_number, order_status 
FROM orders 
WHERE coupon_code IS NOT NULL
ORDER BY created_at DESC;
```

**Track coupon usage:**
```sql
-- Coupon usage stats
SELECT code, discount_type, discount_value, usage_count, usage_limit, status 
FROM coupons 
ORDER BY usage_count DESC;
```

---

## 🎨 UI Customization

### Cart Drawer Coupon Section (CSS):
```css
.coupon-section {
    background: #1e293b;
    border: 1px solid #38bdf825;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 16px;
}

.coupon-input {
    padding: 8px 12px;
    background: #0f172a;
    border: 1px solid #38bdf840;
    border-radius: 6px;
    color: #e2e8f0;
}

.coupon-apply-btn {
    background: #38bdf8;
    color: #0f172a;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 700;
}
```

### QR Code Section:
```css
.qr-container {
    background: #0f172a;
    border: 2px solid #38bdf825;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.qr-image {
    width: 200px;
    height: 200px;
    margin: 0 auto;
}
```

---

## 📞 API Endpoints Reference

### Apply Coupon:
```
POST /EPRO/api/apply_coupon.php
Body: action=apply_coupon&code=TEST20&subtotal=1000

Response:
{
    "success": true,
    "discount": 200,
    "coupon": {"code": "TEST20", "discount_type": "percent"}
}
```

### Create Order:
```
POST /EPRO/api/order_handler.php
Body: action=create_order&subtotal=1000&discount=200&coupon_code=TEST20&delivery_address=123+Main+St&phone=9876543210

Response:
{
    "success": true,
    "data": {"order_id": 42, "total_amount": 800}
}
```

### Verify Payment:
```
POST /EPRO/api/order_handler.php
Body: action=verify_utr&order_id=42&utr_number=123456789012

Response:
{
    "success": true,
    "message": "Payment verified successfully! Order confirmed."
}
```

---

## 🚀 Performance Tips

1. **QR Generation:** Cached client-side, no server processing
2. **Coupon Lookups:** Indexed on `code` and `status`
3. **Cart Operations:** AJAX prevents full page reload
4. **Validation:** Early client-side validation reduces server load

---

## ✅ Pre-Launch Checklist

- [ ] Database migration executed
- [ ] All files uploaded to correct directories
- [ ] UPI ID configured in payment.php
- [ ] Test coupons created in database
- [ ] Tested on mobile device
- [ ] Tested on desktop
- [ ] Verified cart clearing after payment
- [ ] Checked error logs for issues
- [ ] Toast notifications working
- [ ] Cart drawer animations smooth

---

## 📚 Related Documentation

- **Full Details:** See `PHASE_3_FEATURES.md`
- **Phase 1:** `PHASE_1_FEATURES.md`
- **Phase 2:** See documentation in `QUICK_START.md`
- **Database Schema:** `database/epro.sql`

---

## 🎓 Architecture Overview

```
User Flow:
  Products → Add to Cart → Cart Drawer (with Coupon)
    ↓
  Apply Coupon (apply_coupon.php)
    ↓
  Checkout → Payment Page (payment.php)
    ↓
  Mobile: UPI App     OR     Desktop: QR Code
    ↓                              ↓
  Complete Payment          Complete Payment
    ↓                              ↓
  Return & Enter UTR
    ↓
  Verify Payment (order_handler.php)
    ↓
  Create Order & Clear Cart
    ↓
  Order Confirmed ✅
```

---

## 🎯 Next Steps

1. **Run Database Migration** - Execute SQL
2. **Deploy Files** - Copy to EPRO directory
3. **Update Configuration** - Set UPI ID
4. **Create Test Coupon** - Insert sample data
5. **Test Flows** - Mobile & Desktop
6. **Monitor Logs** - Check for errors
7. **Go Live!** 🚀

---

**Quick Start Guide - Phase 3**  
**Status:** Ready for Production  
**Last Updated:** 2025-01-01
