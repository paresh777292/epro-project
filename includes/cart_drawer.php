<?php
/**
 * Cart Drawer Component - Include this in your layout
 * This drawer slides in from the right when items are added to cart
 * 
 * Usage: Include in your header or main layout file
 * <?php include __DIR__ . '/../includes/cart_drawer.php'; ?>
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get session user_id or null for guest
$drawer_user_id = $_SESSION['user_id'] ?? null;
?>

<!-- Cart Drawer HTML Structure -->
<div id="cartDrawerOverlay" class="cart-drawer-overlay"></div>
<div id="cartDrawer" class="cart-drawer">
    <div class="cart-drawer-header">
        <h2>Shopping Cart</h2>
        <button id="cartDrawerClose" class="cart-drawer-close" aria-label="Close cart">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div id="cartDrawerContent" class="cart-drawer-content">
        <!-- Cart items will be loaded here via AJAX -->
        <div class="cart-loading">
            <div class="spinner"></div>
            <p>Loading cart...</p>
        </div>
    </div>

    <div class="cart-drawer-footer">
        <div class="cart-subtotal">
            <span>Subtotal:</span>
            <span id="cartDrawerSubtotal">₹0.00</span>
        </div>

        <!-- Coupon Section -->
        <div id="couponSection" class="coupon-section" style="display: none;">
            <div class="coupon-form">
                <input type="text" id="couponInput" class="coupon-input" placeholder="Enter coupon code" />
                <button id="applyCouponBtn" class="coupon-apply-btn">Apply</button>
            </div>
            <div id="appliedCoupon" class="applied-coupon" style="display: none;">
                <small>Applied: <strong id="appliedCouponCode"></strong></small>
                <button id="removeCouponBtn" class="remove-coupon-btn" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="couponMessage" class="coupon-message"></div>
        </div>

        <!-- Discount Display -->
        <div id="discountSection" class="discount-section" style="display: none;">
            <div class="discount-row">
                <span>Discount:</span>
                <span id="discountAmount">-₹0.00</span>
            </div>
            <div class="total-row">
                <span>Total:</span>
                <span id="cartDrawerTotal">₹0.00</span>
            </div>
        </div>

        <button id="checkoutBtn" class="btn-checkout">Proceed to Checkout</button>
    </div>
</div>

<style>
/* Drawer Container */
.cart-drawer {
    position: fixed;
    right: 0;
    top: 0;
    width: 400px;
    height: 100vh;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-left: 1px solid rgba(56, 189, 248, 0.2);
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.5);
    z-index: 9998;
    transform: translateX(100%);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(14px);
}

.cart-drawer.active {
    transform: translateX(0);
}

/* Drawer Header */
.cart-drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 22px 20px;
    border-bottom: 1px solid rgba(56, 189, 248, 0.15);
    background: rgba(56, 189, 248, 0.04);
}

.cart-drawer-header h2 {
    margin: 0;
    font-size: 20px;
    color: #38bdf8;
    font-weight: 700;
}

.cart-drawer-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 20px;
    cursor: pointer;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
}

.cart-drawer-close:hover {
    background: rgba(56, 189, 248, 0.1);
    color: #38bdf8;
}

/* Drawer Content */
.cart-drawer-content {
    flex: 1;
    overflow-y: auto;
    padding: 18px;
}

.cart-drawer-content::-webkit-scrollbar {
    width: 5px;
}

.cart-drawer-content::-webkit-scrollbar-track {
    background: transparent;
}

.cart-drawer-content::-webkit-scrollbar-thumb {
    background: rgba(56, 189, 248, 0.25);
    border-radius: 3px;
}

/* Empty Cart State */
.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
    text-align: center;
}

.cart-empty i {
    font-size: 54px;
    color: rgba(56, 189, 248, 0.3);
    margin-bottom: 14px;
}

.cart-empty p {
    margin: 6px 0;
    font-size: 15px;
    color: #cbd5e1;
    font-weight: 600;
}

/* Loading State */
.cart-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
}

.spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(56, 189, 248, 0.15);
    border-top: 3px solid #38bdf8;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 14px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Cart Item */
.cart-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    background: rgba(30, 41, 59, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    margin-bottom: 12px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.cart-item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #0f172a;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.cart-item-details {
    flex: 1;
    min-width: 0;
}

.cart-item-name {
    font-size: 13px;
    font-weight: 600;
    color: #f1f5f9;
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cart-item-price {
    font-size: 13px;
    color: #38bdf8;
    font-weight: 700;
    margin-bottom: 8px;
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-btn {
    background: #0f172a;
    border: 1px solid rgba(56, 189, 248, 0.4);
    color: #38bdf8;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: #38bdf8;
    color: #0f172a;
}

.qty-display {
    min-width: 24px;
    text-align: center;
    color: #e2e8f0;
    font-size: 12px;
    font-weight: 600;
}

.remove-btn {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 15px;
    padding: 6px;
    transition: all 0.2s;
    margin-left: auto;
}

.remove-btn:hover {
    color: #fca5a5;
    transform: scale(1.15);
}

/* Drawer Footer */
.cart-drawer-footer {
    padding: 18px 20px;
    border-top: 1px solid rgba(56, 189, 248, 0.15);
    background: rgba(15, 23, 42, 0.4);
}

.cart-subtotal {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    font-size: 15px;
    font-weight: 700;
    color: #e2e8f0;
}

/* Coupon Section */
.coupon-section {
    margin-bottom: 14px;
    padding: 10px;
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(56, 189, 248, 0.15);
    border-radius: 8px;
}

.coupon-form {
    display: flex;
    gap: 8px;
}

.coupon-input {
    flex: 1;
    padding: 7px 12px;
    background: #0f172a;
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 12px;
    outline: none;
}

.coupon-input:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 8px rgba(56, 189, 248, 0.3);
}

.coupon-apply-btn {
    padding: 7px 14px;
    background: #38bdf8;
    color: #0f172a;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.coupon-apply-btn:hover {
    background: #0284c7;
    color: white;
}

.applied-coupon {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    background: rgba(52, 211, 153, 0.12);
    border: 1px solid #34d399;
    border-radius: 6px;
}

.applied-coupon small {
    color: #34d399;
    font-weight: 600;
}

.remove-coupon-btn {
    background: none;
    border: none;
    color: #34d399;
    cursor: pointer;
    font-size: 13px;
}

.coupon-message {
    font-size: 11px;
    padding-top: 4px;
    color: #94a3b8;
}

.coupon-message.success { color: #34d399; }
.coupon-message.error { color: #ef4444; }

/* Discount & Total Section */
.discount-section {
    padding: 8px 0;
    margin-bottom: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.discount-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #34d399;
    margin-bottom: 6px;
    font-weight: 600;
}

.total-row {
    display: flex;
    justify-content: space-between;
    font-size: 16px;
    color: #38bdf8;
    font-weight: 700;
}

.btn-checkout {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(56, 189, 248, 0.35);
}

.btn-checkout:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(56, 189, 248, 0.5);
}

/* Overlay Backdrop */
.cart-drawer-overlay {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0);
    z-index: 9997;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s;
}

.cart-drawer-overlay.active {
    background: rgba(0, 0, 0, 0.6);
    opacity: 1;
    pointer-events: auto;
    backdrop-filter: blur(4px);
}

@media (max-width: 480px) {
    .cart-drawer { width: 100%; }
}
</style>

<script>
/**
 * Cart Drawer Manager
 */
const CartDrawerManager = {
    drawer: null,
    overlay: null,
    isOpen: false,
    currentDiscount: 0,
    appliedCoupon: null,

    init() {
        this.drawer = document.getElementById('cartDrawer');
        this.overlay = document.getElementById('cartDrawerOverlay');

        if (!this.drawer || !this.overlay) return;

        // Close button
        const closeBtn = document.getElementById('cartDrawerClose');
        if (closeBtn) closeBtn.addEventListener('click', () => this.close());

        // Overlay click to close
        this.overlay.addEventListener('click', () => this.close());

        // Checkout button
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) checkoutBtn.addEventListener('click', () => this.goToCheckout());

        // Coupon button
        const applyBtn = document.getElementById('applyCouponBtn');
        const couponInput = document.getElementById('couponInput');
        if (applyBtn && couponInput) {
            applyBtn.addEventListener('click', () => this.applyCoupon());
            couponInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.applyCoupon();
            });
        }

        // Remove coupon button
        const removeCouponBtn = document.getElementById('removeCouponBtn');
        if (removeCouponBtn) {
            removeCouponBtn.addEventListener('click', () => this.removeCoupon());
        }
    },

    open() {
        if (!this.drawer) this.init();
        this.isOpen = true;
        this.drawer.classList.add('active');
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        this.loadCartItems();

        const escListener = (e) => {
            if (e.key === 'Escape') {
                this.close();
                document.removeEventListener('keydown', escListener);
            }
        };
        document.addEventListener('keydown', escListener);
    },

    close() {
        this.isOpen = false;
        if (this.drawer) this.drawer.classList.remove('active');
        if (this.overlay) this.overlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    loadCartItems() {
        const contentDiv = document.getElementById('cartDrawerContent');
        if (!contentDiv) return;

        contentDiv.innerHTML = '<div class="cart-loading"><div class="spinner"></div><p>Loading cart...</p></div>';

        fetch('/EPRO/api/cart_handler.php?action=get_cart')
            .then(response => response.json())
            .then(data => {
                if (data && data.success && data.items && data.items.length > 0) {
                    this.renderCartItems(data.items, data.subtotal);
                } else {
                    contentDiv.innerHTML = `
                        <div class="cart-empty">
                            <i class="fas fa-shopping-bag"></i>
                            <p>Your cart is empty</p>
                            <small>Add items to get started</small>
                        </div>
                    `;
                    document.getElementById('cartDrawerSubtotal').textContent = '₹0.00';
                    document.getElementById('couponSection').style.display = 'none';
                    document.getElementById('discountSection').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading cart:', error);
                contentDiv.innerHTML = '<div class="cart-empty"><p style="color:#ef4444;">Failed to load cart</p></div>';
            });
    },

    renderCartItems(items, subtotal) {
        const contentDiv = document.getElementById('cartDrawerContent');
        let itemsHTML = '';

        items.forEach(item => {
            // Dynamic path resolver for images
            let itemImg = item.image || '';
            if (!itemImg.startsWith('http') && !itemImg.startsWith('/')) {
                if (itemImg.startsWith('assets/')) {
                    itemImg = `/EPRO/${itemImg}`;
                } else {
                    itemImg = `/EPRO/assets/images/${itemImg}`;
                }
            }

            const price = parseFloat(item.price || 0).toFixed(2);

            itemsHTML += `
                <div class="cart-item" data-cart-id="${item.cart_id}">
                    <img 
                        src="${itemImg}" 
                        alt="${this.escapeHtml(item.name)}" 
                        class="cart-item-image" 
                        onerror="this.onerror=null; this.src='https://placehold.co/100x100/1e293b/38bdf8?text=Product';"
                    >
                    <div class="cart-item-details">
                        <p class="cart-item-name">${this.escapeHtml(item.name)}</p>
                        <p class="cart-item-price">₹${price}</p>
                        <div class="cart-item-controls">
                            <button class="qty-btn" onclick="CartDrawerManager.updateQuantity(${item.cart_id}, -1)">−</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn" onclick="CartDrawerManager.updateQuantity(${item.cart_id}, 1)">+</button>
                            <button class="remove-btn" onclick="CartDrawerManager.removeItem(${item.cart_id})" title="Remove item">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        contentDiv.innerHTML = itemsHTML;

        const subtotalAmount = parseFloat(subtotal || 0);
        document.getElementById('cartDrawerSubtotal').textContent = `₹${subtotalAmount.toFixed(2)}`;
        document.getElementById('couponSection').style.display = 'block';

        this.updateTotalDisplay(subtotalAmount);
    },

    updateTotalDisplay(subtotal) {
        const discountSection = document.getElementById('discountSection');
        const totalElement = document.getElementById('cartDrawerTotal');

        if (this.currentDiscount > 0) {
            discountSection.style.display = 'block';
            const total = subtotal - this.currentDiscount;
            document.getElementById('discountAmount').textContent = `-₹${this.currentDiscount.toFixed(2)}`;
            totalElement.textContent = `₹${Math.max(0, total).toFixed(2)}`;
        } else {
            discountSection.style.display = 'none';
        }
    },

    applyCoupon() {
        const couponCode = document.getElementById('couponInput').value.trim();
        const subtotal = parseFloat(document.getElementById('cartDrawerSubtotal').textContent.replace('₹', ''));
        const messageDiv = document.getElementById('couponMessage');

        if (!couponCode) {
            messageDiv.textContent = 'Please enter a coupon code';
            messageDiv.className = 'coupon-message error';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'apply_coupon');
        formData.append('code', couponCode);
        formData.append('subtotal', subtotal);

        fetch('/EPRO/api/apply_coupon.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentDiscount = data.discount;
                this.appliedCoupon = data.coupon;

                document.getElementById('appliedCoupon').style.display = 'flex';
                document.getElementById('appliedCouponCode').textContent = data.coupon.code;
                document.querySelector('.coupon-form').style.display = 'none';

                this.updateTotalDisplay(subtotal);

                messageDiv.textContent = `✓ ${data.message}`;
                messageDiv.className = 'coupon-message success';
                if (typeof showToast === 'function') showToast(data.message, 'success');
            } else {
                messageDiv.textContent = data.message;
                messageDiv.className = 'coupon-message error';
                if (typeof showToast === 'function') showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.textContent = 'Failed to apply coupon';
            messageDiv.className = 'coupon-message error';
        });
    },

    removeCoupon() {
        this.currentDiscount = 0;
        this.appliedCoupon = null;

        document.getElementById('appliedCoupon').style.display = 'none';
        document.getElementById('appliedCouponCode').textContent = '';
        document.querySelector('.coupon-form').style.display = 'flex';
        document.getElementById('couponInput').value = '';
        document.getElementById('couponMessage').textContent = '';

        const subtotal = parseFloat(document.getElementById('cartDrawerSubtotal').textContent.replace('₹', ''));
        this.updateTotalDisplay(subtotal);

        if (typeof showToast === 'function') showToast('Coupon removed', 'info');
    },

    updateQuantity(cartId, change) {
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('cart_id', cartId);
        formData.append('change', change);

        fetch('/EPRO/api/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadCartItems();
                if (typeof updateCartCount === 'function') updateCartCount();
                if (typeof showToast === 'function') showToast(data.message, 'success');
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Error updating quantity', 'error');
            }
        })
        .catch(error => console.error('Error:', error));
    },

    removeItem(cartId) {
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('cart_id', cartId);

        fetch('/EPRO/api/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadCartItems();
                if (typeof updateCartCount === 'function') updateCartCount();
                if (typeof showToast === 'function') showToast('Item removed from cart', 'success');
            } else {
                if (typeof showToast === 'function') showToast('Error removing item', 'error');
            }
        })
        .catch(error => console.error('Error:', error));
    },

    goToCheckout() {
        sessionStorage.setItem('cart_discount', this.currentDiscount);
        if (this.appliedCoupon) {
            sessionStorage.setItem('applied_coupon', JSON.stringify(this.appliedCoupon));
        }
        window.location.href = '/EPRO/user/payment.php';
    },

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => CartDrawerManager.init());
</script>