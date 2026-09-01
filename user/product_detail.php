<?php
/**
 * Product Detail Page with Reviews - Phase 2
 * Shows detailed product information and customer reviews
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Fetch product details using prepared statement
$product_stmt = $conn->prepare(
    "SELECT id, name, price, mrp, image, category, description FROM products WHERE id = ?"
);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

// Calculate discount
$discount = 0;
if (!empty($product['mrp']) && $product['mrp'] > $product['price']) {
    $discount = round(((floatval($product['mrp']) - floatval($product['price'])) / floatval($product['mrp'])) * 100);
}

// Get cart count
$cart_count = 0;
if (isset($conn) && $conn) {
    $c_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id " . ($user_id ? "= '$user_id'" : "IS NULL"));
    if ($c_res && $crow = mysqli_fetch_assoc($c_res)) {
        $cart_count = (int)($crow['total'] ?? 0);
    }
}

// User initial for avatar
$user_initial = "";
if (!empty($user_name)) {
    $user_initial = strtoupper(substr(trim($user_name), 0, 1));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | E-PRO</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/EPRO/assets/js/toast.js"></script>
    <script src="/EPRO/assets/js/stars.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 35px;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            color: #38bdf8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #38bdf8;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .product-section {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.05) 0%, rgba(129, 140, 248, 0.05) 100%);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 20px;
            padding: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            backdrop-filter: blur(10px);
            margin-bottom: 40px;
        }

        .product-image-wrapper {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: #0f172a;
            aspect-ratio: 1;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h1 {
            font-size: 32px;
            margin-bottom: 16px;
            color: #e2e8f0;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .product-category {
            display: inline-block;
            padding: 6px 14px;
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            font-size: 12px;
            color: #38bdf8;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .product-price-section {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .price-current {
            font-size: 36px;
            font-weight: 700;
            color: #34d399;
        }

        .price-original {
            font-size: 20px;
            color: #94a3b8;
            text-decoration: line-through;
        }

        .discount-badge-large {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
        }

        .product-description {
            font-size: 15px;
            line-height: 1.6;
            color: #cbd5e1;
            margin-bottom: 32px;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #38bdf8;
            background: transparent;
            color: #38bdf8;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: #38bdf8;
            color: #0f172a;
        }

        .qty-display {
            min-width: 60px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
        }

        .btn-add-to-cart {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #0f172a;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px #38bdf840;
        }

        .btn-add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px #38bdf8;
        }

        /* Reviews Section */
        .reviews-section {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.05) 0%, rgba(129, 140, 248, 0.05) 100%);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .reviews-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
        }

        .rating-summary {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .rating-big {
            font-size: 64px;
            font-weight: 700;
            color: #38bdf8;
        }

        .rating-stats {
            flex: 1;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .rating-bar-label {
            min-width: 40px;
            font-size: 13px;
            color: #94a3b8;
        }

        .rating-bar-fill {
            flex: 1;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .rating-bar-progress {
            height: 100%;
            background: linear-gradient(90deg, #38bdf8, #0284c7);
            border-radius: 4px;
            transition: width 0.3s;
        }

        .rating-count {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 12px;
        }

        /* Review Form */
        .review-form {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 40px;
        }

        .review-form h3 {
            font-size: 18px;
            margin-bottom: 24px;
            color: #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #cbd5e1;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            color: #e2e8f0;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #38bdf8;
            background: rgba(15, 23, 42, 0.9);
            outline: none;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .rating-input-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rating-input-group label {
            margin: 0;
        }

        .btn-submit-review {
            padding: 12px 32px;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #0f172a;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px #38bdf8;
        }

        /* Reviews List */
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .review-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(56, 189, 248, 0.15);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s;
        }

        .review-card:hover {
            border-color: #38bdf8;
            background: rgba(30, 41, 59, 0.8);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .review-author {
            font-weight: 700;
            color: #e2e8f0;
        }

        .review-date {
            font-size: 12px;
            color: #94a3b8;
        }

        .review-rating {
            margin-bottom: 12px;
        }

        .review-text {
            color: #cbd5e1;
            line-height: 1.6;
            font-size: 14px;
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .product-section {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .reviews-header {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .product-info h1 {
                font-size: 24px;
            }

            .price-current {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="../index.php" class="logo"><i class="fa-solid fa-bolt"></i> E-PRO</a>
        <div class="nav-links">
            <a href="../index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="feedback.php">Feedback</a>
            <a href="javascript:void(0)" onclick="CartDrawerManager.open()" style="cursor: pointer;">
                <i class="fa-solid fa-cart-shopping"></i> Cart
            </a>
            <?php if (!empty($user_initial)): ?>
                <a href="profile.php"><?php echo htmlspecialchars($user_name); ?></a>
                <a href="../logout.php" style="color: #ef4444;">Logout</a>
            <?php else: ?>
                <a href="login.php" style="color: #38bdf8;">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <!-- Product Details -->
        <div class="product-section">
            <div class="product-image-wrapper">
                <img 
                    src="/EPRO/assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                    class="product-image"
                    onerror="this.src='/EPRO/assets/images/placeholder.png'"
                >
            </div>

            <div class="product-info">
                <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>

                <div id="productRating" class="product-rating"></div>

                <div class="product-price-section">
                    <div class="price-current">₹<?php echo number_format($product['price'], 2); ?></div>
                    <?php if ($discount > 0): ?>
                        <div class="price-original">₹<?php echo number_format($product['mrp'], 2); ?></div>
                        <div class="discount-badge-large">-<?php echo $discount; ?>% OFF</div>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <?php echo !empty($product['description']) ? htmlspecialchars($product['description']) : 'Premium E-PRO Product'; ?>
                </div>

                <div class="quantity-selector">
                    <button class="qty-btn" onclick="decreaseQuantity()">−</button>
                    <span class="qty-display" id="quantityDisplay">1</span>
                    <button class="qty-btn" onclick="increaseQuantity()">+</button>
                </div>

                <button class="btn-add-to-cart" onclick="addProductToCart(<?php echo $product['id']; ?>)">
                    <i class="fa-solid fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 style="margin-bottom: 40px; font-size: 28px;">Customer Reviews</h2>

            <div class="reviews-header">
                <!-- Rating Summary -->
                <div class="rating-summary">
                    <div>
                        <div class="rating-big" id="averageRating">0</div>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 8px;" id="totalReviews">No reviews yet</div>
                    </div>
                </div>

                <!-- Rating Distribution -->
                <div class="rating-stats" id="ratingDistribution"></div>
            </div>

            <!-- Review Submission Form -->
            <div class="review-form">
                <h3>Share Your Experience</h3>
                <form id="reviewForm" onsubmit="submitReview(event)">
                    <div class="form-group">
                        <label for="userName">Your Name</label>
                        <input type="text" id="userName" name="user_name" required minlength="2" placeholder="Enter your name">
                    </div>

                    <div class="form-group">
                        <label for="userEmail">Email (Optional)</label>
                        <input type="email" id="userEmail" name="email" placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label>Rating</label>
                        <div class="rating-input-group">
                            <label style="margin: 0;">How would you rate this product?</label>
                            <div id="ratingInput"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reviewText">Your Review</label>
                        <textarea id="reviewText" name="review_text" required minlength="10" placeholder="Share your thoughts about this product..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit-review">
                        <i class="fa-solid fa-paper-plane"></i> Submit Review
                    </button>
                </form>
            </div>

            <!-- Reviews List -->
            <div id="reviewsList" class="reviews-list"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        let currentQuantity = 1;
        let selectedRating = 0;

        function decreaseQuantity() {
            if (currentQuantity > 1) {
                currentQuantity--;
                document.getElementById('quantityDisplay').textContent = currentQuantity;
            }
        }

        function increaseQuantity() {
            currentQuantity++;
            document.getElementById('quantityDisplay').textContent = currentQuantity;
        }

        function addProductToCart(productId) {
            const formData = new FormData();
            formData.append('action', 'add_item');
            formData.append('product_id', productId);
            formData.append('quantity', currentQuantity);

            fetch('/EPRO/api/cart_handler.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Added ${currentQuantity} item(s) to cart!`, 'success');
                        setTimeout(() => CartDrawerManager.open(), 500);
                    } else {
                        showToast(data.message || 'Error adding to cart', 'error');
                    }
                })
                .catch(err => showToast('Connection error', 'error'));
        }

        // Load product rating and reviews on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadProductRating();
            loadReviews();
            initRatingInput();

            if (typeof CartDrawerManager !== 'undefined') {
                CartDrawerManager.init();
            }
        });

        function loadProductRating() {
            fetch(`/EPRO/api/product_reviews.php?action=get_average_rating&product_id=<?php echo $product_id; ?>`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const rating = data.data.average_rating;
                        const count = data.data.total_reviews;

                        document.getElementById('averageRating').textContent = rating.toFixed(1);
                        document.getElementById('totalReviews').textContent = `Based on ${count} review${count !== 1 ? 's' : ''}`;

                        // Display stars
                        const ratingDiv = document.getElementById('productRating');
                        new StarRating(ratingDiv, rating, false);

                        // Load rating distribution
                        loadRatingDistribution();
                    }
                });
        }

        function loadRatingDistribution() {
            fetch(`/EPRO/api/product_reviews.php?action=get_rating_distribution&product_id=<?php echo $product_id; ?>`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const dist = data.data.distribution;
                        const total = data.data.total_reviews;
                        let html = '';

                        for (let star = 5; star >= 1; star--) {
                            const count = dist[star] || 0;
                            const percent = total > 0 ? (count / total) * 100 : 0;
                            html += `
                                <div class="rating-bar">
                                    <span class="rating-bar-label">${star}★</span>
                                    <div class="rating-bar-fill">
                                        <div class="rating-bar-progress" style="width: ${percent}%;"></div>
                                    </div>
                                    <span class="rating-bar-label">${count}</span>
                                </div>
                            `;
                        }

                        document.getElementById('ratingDistribution').innerHTML = html;
                    }
                });
        }

        function loadReviews() {
            fetch(`/EPRO/api/product_reviews.php?action=get_reviews&product_id=<?php echo $product_id; ?>&limit=10`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(review => {
                            html += `
                                <div class="review-card">
                                    <div class="review-header">
                                        <span class="review-author">${review.user_name}</span>
                                        <span class="review-date">${new Date(review.created_at).toLocaleDateString()}</span>
                                    </div>
                                    <div class="review-rating">
                                        ${createStarDisplay(review.rating, true)}
                                    </div>
                                    <div class="review-text">${review.review_text}</div>
                                </div>
                            `;
                        });
                        document.getElementById('reviewsList').innerHTML = html;
                    } else {
                        document.getElementById('reviewsList').innerHTML = '<div class="no-reviews">No reviews yet. Be the first to review!</div>';
                    }
                });
        }

        function initRatingInput() {
            const ratingDiv = document.getElementById('ratingInput');
            const ratingInput = new StarRating(ratingDiv, 0, true, (rating) => {
                selectedRating = rating;
            });
        }

        function submitReview(e) {
            e.preventDefault();

            if (selectedRating === 0) {
                showToast('Please select a rating', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'submit_review');
            formData.append('product_id', <?php echo $product_id; ?>);
            formData.append('user_name', document.getElementById('userName').value);
            formData.append('email', document.getElementById('userEmail').value);
            formData.append('rating', selectedRating);
            formData.append('review_text', document.getElementById('reviewText').value);

            fetch('/EPRO/api/product_reviews.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Review submitted successfully!', 'success');
                        document.getElementById('reviewForm').reset();
                        selectedRating = 0;
                        loadProductRating();
                        loadReviews();
                        loadRatingDistribution();
                    } else {
                        showToast(data.message || 'Error submitting review', 'error');
                    }
                })
                .catch(err => showToast('Connection error', 'error'));
        }
    </script>

    <?php include __DIR__ . '/../includes/cart_drawer.php'; ?>
</body>
</html>
