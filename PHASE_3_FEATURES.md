# PHASE 3 - CHECKOUT & PAYMENT FEATURES
## UPI Payment System & Coupon Engine Documentation

---

## 📦 Deliverables - Phase 3

### **1. Coupon & Promo Code Engine** ✅

**Database Schema:**
```sql
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
```

**File:** `api/apply_coupon.php`
- REST AJAX endpoint for coupon validation
- **Actions:**
  - `apply_coupon`: Validate and apply promo code
  - `remove_coupon`: Remove applied coupon
  - `validate_coupon`: Check validity without applying
  
**Features:**
- Coupon code validation (case-insensitive)
- Expiry date checking
- Minimum order value validation
- Usage limit enforcement
- Discount cap (max_discount) support
- Percentage & fixed discount support
- Real-time validation and error messages
- **Security:** MySQLi prepared statements throughout

**API Response:**
```json
{
    "success": true,
    "message": "Coupon applied successfully!",
    "coupon": {
        "code": "SUMMER50",
        "discount_type": "percent",
        "discount_value": 50
    },
    "discount": 250.50
}
```

---

### **2. Dynamic UPI Payment System** ✅

**File:** `user/payment.php`
- Zero-fee UPI payment implementation
- Device detection (mobile vs desktop)
- Dual payment flows

**Mobile UPI Payment Flow:**
1. Device detection triggers mobile view
2. Shows 4 UPI app buttons:
   - Google Pay
   - PhonePe
   - Paytm
   - Other UPI (manual input)
3. Clicking button triggers UPI Intent deep link:
   ```
   upi://pay?pa=merchant@okaxis&pn=EPRO_Store&am=1299.50&cu=INR&tn=Order+Payment
   ```
4. User completes payment in UPI app
5. Returns to app to enter UTR number

**Desktop UPI Payment Flow:**
1. Displays dynamic QR code (Google Chart API)
2. QR encodes full UPI URI
3. User scans with phone camera or UPI app
4. Completes payment
5. Enters UTR/RRN number to confirm

**QR Code Generation:**
```javascript
const qrUrl = `https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=${encodeURIComponent(upiURI)}`;
```

**Features:**
- **Cart Summary Display:** Shows all items, quantities, prices
- **Price Breakdown:** Subtotal, discount, total with coupon info
- **Delivery Address Form:** Full name, email, phone, address
- **UTR Verification:** 12-digit UPI Reference number input
- **Mobile Responsive:** Full-width drawer on mobile
- **Frosted Glass UI:** Modern animations and transitions
- **Status Messages:** Real-time success/error feedback

**Order Confirmation Flow:**
1. User enters 12-digit UTR/RRN number
2. System creates order in database
3. Verifies UTR and marks as "completed"
4. Clears user's cart
5. Redirects to order confirmation page

---

### **3. Order Handler API** ✅

**File:** `api/order_handler.php`
- Backend order management and payment verification
- **Actions:**
  - `create_order`: Insert new order from cart
  - `verify_utr`: Verify UPI transaction
  - `get_order`: Fetch order details

**Create Order:**
```php
POST /api/order_handler.php
{
    "action": "create_order",
    "subtotal": 1500.00,
    "discount": 250.50,
    "coupon_code": "SUMMER50",
    "delivery_address": "123 Main St, City, 12345",
    "phone": "9876543210"
}
```

**Verify UTR:**
```php
POST /api/order_handler.php
{
    "action": "verify_utr",
    "order_id": 42,
    "utr_number": "123456789012"
}
```

**Features:**
- User authentication validation
- Cart existence verification
- Order creation with discount tracking
- Coupon usage count increment
- UTR verification and logging
- Cart clearing after successful payment
- Comprehensive error handling

---

### **4. Cart Drawer Enhancement** ✅

**File:** `includes/cart_drawer.php`
- **New Coupon Section:**
  - Input field for promo code
  - "Apply" button with validation
  - Applied coupon badge (green success state)
  - Remove coupon button

**New Display Elements:**
- Discount section showing:
  - Applied coupon code
  - Discount amount deducted
  - Updated grand total
- Real-time calculations on coupon apply/remove
- Persistent coupon across checkout flow

**JavaScript Functions:**
- `applyCoupon()`: Submit coupon via AJAX
- `removeCoupon()`: Clear coupon and reset totals
- `updateTotalDisplay()`: Update UI with discount amount
- `goToCheckout()`: Pass coupon/discount to payment page

**SessionStorage Integration:**
- Stores `cart_discount` and `applied_coupon` in browser storage
- Passes to payment.php for order creation
- Maintains coupon across page transitions

---

### **5. Database Enhancements** ✅

**File:** `database/epro.sql`

**Added Tables:**
- `coupons` table for promo code management

**Enhanced Tables:**
```sql
ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN utr_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'UPI';
ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN delivery_address TEXT DEFAULT NULL;
```

---

## 🔐 Security Implementation

✅ **All operations use MySQLi Prepared Statements** - SQL injection protection  
✅ **User authentication required** - No guest orders allowed  
✅ **HTTPS recommended** - For sensitive payment data  
✅ **Input validation** - Both client and server-side  
✅ **UTR logging** - All payment verifications logged for audit trail  
✅ **Coupon usage limits** - Prevents coupon abuse  
✅ **Expiry date validation** - Prevents expired coupon usage  

---

## 📋 Integration Checklist

### Database Setup:
- [ ] Run `epro.sql` migration script to add coupons table
- [ ] Alter orders table with payment columns
- [ ] Create sample coupons for testing:
  ```sql
  INSERT INTO coupons (code, discount_type, discount_value, min_order, expiry_date, status)
  VALUES ('WELCOME20', 'percent', 20, 500, '2025-12-31 23:59:59', 'active');
  
  INSERT INTO coupons (code, discount_type, discount_value, min_order, expiry_date, status)
  VALUES ('FLAT100', 'fixed', 100, 1000, '2025-12-31 23:59:59', 'active');
  ```

### File Deployment:
- [ ] `api/apply_coupon.php` - Coupon validation endpoint
- [ ] `api/order_handler.php` - Order creation and verification
- [ ] `user/payment.php` - Payment checkout page (updated)
- [ ] `includes/cart_drawer.php` - Cart drawer with coupon (updated)
- [ ] `database/epro.sql` - Database schema (updated)

### Include Statements:
```php
<!-- In your pages that need toast notifications -->
<script src="/EPRO/assets/js/toast.js"></script>

<!-- In your layout footer, before closing body -->
<?php include __DIR__ . '/../includes/cart_drawer.php'; ?>
```

### Configuration:
Update UPI ID in `user/payment.php`:
```php
$UPI_ID = 'your_upi_id@bankname'; // Replace with actual merchant UPI ID
```

---

## 🎨 User Experience Flow

### Customer Journey - Desktop:
1. Browse products → Add to cart
2. Cart drawer opens with coupon input
3. Enter coupon code → Real-time validation
4. Click "Proceed to Checkout"
5. Payment page loads with QR code
6. Scan QR → Complete payment in UPI app
7. Return to page → Enter UTR/RRN number
8. Click "Verify & Confirm"
9. Order confirmed → Redirect to profile

### Customer Journey - Mobile:
1. Browse products → Add to cart
2. Cart drawer slides up with coupon input
3. Enter coupon → Validation with toast
4. "Proceed to Checkout"
5. Payment page shows 4 UPI app buttons
6. Click desired app → UPI Intent deep link
7. Complete payment in app
8. Manual app switch back
9. Enter UTR/RRN
10. Confirm order

---

## 🧪 Testing Guide

### Test Coupon Scenarios:
```
1. Valid coupon:
   Code: WELCOME20 | Expected: 20% discount applied
   
2. Expired coupon:
   Code: EXPIRED20 | Expected: "Coupon has expired"
   
3. Minimum order not met:
   Code: SUMMER50 (min ₹1000) with ₹500 cart
   Expected: "Minimum order value of ₹1000 required"
   
4. Usage limit exceeded:
   Expected: "Coupon has reached its usage limit"
   
5. Invalid code:
   Code: INVALID123 | Expected: "Invalid or inactive coupon"
```

### Test Payment Flow:
```
1. Mobile device:
   - Verify UPI app buttons appear
   - Test clicking each app
   
2. Desktop:
   - Verify QR code displays
   - Scan with phone UPI app
   - Test manual UTR entry
   
3. Order verification:
   - Enter 12+ digit UTR
   - Verify order created in DB
   - Check cart cleared
```

---

## 📊 Admin Dashboard Features

**Future Admin Enhancements (Post Phase 3):**
- View all applied coupons
- Track coupon usage statistics
- Edit coupon details (expiry, status, limits)
- View payment UTR logs for verification
- Export order data with coupon breakdown
- Monitor discount impact on sales

---

## 🚀 Performance Optimization

- **Coupon validation:** Caches status/expiry for 1 request
- **QR generation:** Client-side via Google Chart API (no server load)
- **AJAX operations:** Debounced for 300ms+ typeset intervals
- **Database:** Indexes on `code`, `status`, `expiry_date`

---

## ⚠️ Common Issues & Troubleshooting

| Issue | Solution |
|-------|----------|
| QR code not loading | Check Google Chart API access, verify HTTPS |
| UTR not verifying | Ensure 12+ digit format, check server logs |
| Coupon not applying | Verify coupon exists, check expiry date, min order |
| Cart not clearing | Check database permissions, review order_handler.php |
| Mobile UPI links not working | Verify UPI ID format, check app installation |

---

## 📚 API Reference

### Coupon Endpoint: `/api/apply_coupon.php`

**Apply Coupon:**
```
POST /api/apply_coupon.php
Content-Type: application/x-www-form-urlencoded

action=apply_coupon&code=SUMMER50&subtotal=1500
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Coupon applied successfully!",
    "coupon": {
        "code": "SUMMER50",
        "discount_type": "percent",
        "discount_value": 50
    },
    "discount": 750.00
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "This coupon has expired"
}
```

---

### Order Endpoint: `/api/order_handler.php`

**Create Order:**
```
POST /api/order_handler.php
Content-Type: application/x-www-form-urlencoded

action=create_order&subtotal=1500&discount=100&coupon_code=SUMMER50&delivery_address=123%20Main%20St&phone=9876543210
```

**Verify UTR:**
```
POST /api/order_handler.php
Content-Type: application/x-www-form-urlencoded

action=verify_utr&order_id=42&utr_number=123456789012
```

---

## 🎯 Next Phase Recommendations

- **Email Notifications:** Send order confirmation with payment details
- **Order Tracking:** Real-time delivery status updates
- **Refund Processing:** Handle payment reversals for cancellations
- **Analytics:** Track coupon effectiveness and payment success rates
- **Fraud Detection:** Detect and block duplicate UTR submissions
- **Multi-UPI Support:** Accept multiple merchant UPI IDs

---

## 📝 Notes

- All timestamps use server timezone
- Discount calculations use banker's rounding (ROUND half to even)
- Order status flow: pending → confirmed → shipped → delivered
- Payment status flow: pending → completed → failed/refunded
- Coupon codes are case-insensitive (converted to uppercase)

---

**Created:** Phase 3 Implementation  
**Last Updated:** 2025-01-01  
**Status:** Production Ready ✅
