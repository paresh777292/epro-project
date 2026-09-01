<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Database Connection Include
if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
}

// 2. Gmail-Style Avatar Initial
$user_initial = "";
if (!empty($_SESSION['user_name'])) {
    $user_initial = strtoupper(substr(trim($_SESSION['user_name']), 0, 1));
} elseif (!empty($_SESSION['user_email'])) {
    $user_initial = strtoupper(substr(trim($_SESSION['user_email']), 0, 1));
}

$user_id = $_SESSION['user_id'] ?? null;
$msg = "";

// 3. Cart Count (Safe Query)
$cart_count = 0;
if (isset($conn) && $conn) {
    if ($user_id) {
        $stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id IS NULL");
    }
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $c_res = mysqli_stmt_get_result($stmt);
        if ($c_res && $crow = mysqli_fetch_assoc($c_res)) {
            $cart_count = (int)($crow['total'] ?? 0);
        }
        mysqli_stmt_close($stmt);
    }
}

// 4. Fetch Unique Categories for Filter Bar
$categories = [];
if (isset($conn) && $conn) {
    $cat_query = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    if ($cat_query) {
        while ($c = mysqli_fetch_assoc($cat_query)) {
            $categories[] = $c['category'];
        }
    }
}

// 5. Filter Products by Selected Category
$selected_cat = isset($_GET['category']) ? trim($_GET['category']) : 'all';

$db_products = [];
if (isset($conn) && $conn) {
    if ($selected_cat !== 'all') {
        $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE category = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "s", $selected_cat);
        mysqli_stmt_execute($stmt);
        $p_res = mysqli_stmt_get_result($stmt);
    } else {
        $p_res = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
    }

    if ($p_res) {
        while ($row = mysqli_fetch_assoc($p_res)) {
            $db_products[] = $row;
        }
    }
}

/**
 * Smart Image Path Resolver
 * Automatically searches for product images across all project folders
 */
function resolveProductImage($img_file) {
    if (empty($img_file)) {
        return 'https://placehold.co/300x300/1e293b/38bdf8?text=No+Image';
    }

    $img_file = trim($img_file);

    // If external URL
    if (strpos($img_file, 'http://') === 0 || strpos($img_file, 'https://') === 0 || strpos($img_file, '//') === 0) {
        return $img_file;
    }

    $clean = ltrim($img_file, '/');

    // List of possible subfolder paths in E-PRO
    $candidates = [
        '../assets/images/' . $clean,
        '../assets/images/products/' . $clean,
        '../assets/images/product1/' . $clean,
        '../assets/images/product2/' . $clean,
        '../assets/images/product3/' . $clean,
        '../assets/images/product4/' . $clean,
        '../assets/images/clothing/' . $clean,
        '../assets/images/fashion/' . $clean,
        '../' . $clean
    ];

    foreach ($candidates as $cand) {
        if (file_exists(__DIR__ . '/' . $cand) && !is_dir(__DIR__ . '/' . $cand)) {
            return $cand;
        }
    }

    // Default fallback path
    return (strpos($clean, 'assets/') === 0) ? '../' . $clean : '../assets/images/' . $clean;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | E-PRO Store</title>
    <!-- Google Fonts & Font Awesome -->
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
            scroll-behavior: smooth; 
        }

        /* Dynamic Background */
        @keyframes dynamicColorFlow {
            0% { background-position: 0% 50%; }
            25% { background-position: 50% 100%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 50% 0%; }
            100% { background-position: 0% 50%; }
        }

        body {
            background: linear-gradient(-45deg, #091e3a, #2b1055, #4c1d95, #0f4c5c, #1e1b4b, #3b0764);
            background-size: 400% 400%;
            animation: dynamicColorFlow 14s ease infinite;
            min-height: 100vh;
            color: #f8fafc;
        }

        /* Navbar */
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
        .logo { font-size: 22px; font-weight: 700; color: #38bdf8; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-links { display: flex; align-items: center; gap: 22px; }
        .nav-links a { color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .nav-links a:hover { color: #38bdf8; }
        
        .cart-badge {
            position: relative;
            font-size: 16px;
            color: #cbd5e1;
            text-decoration: none;
            cursor: pointer;
        }
        .badge-count {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #f43f5e;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
        }

        .user-avatar-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #06b6d4);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            border: 2px solid rgba(255, 255, 255, 0.4);
            text-decoration: none !important;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5);
            transition: transform 0.2s;
        }
        .user-avatar-badge:hover { transform: scale(1.1); }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        /* Filter Tabs */
        .category-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .category-filter-bar::-webkit-scrollbar { height: 4px; }
        .category-filter-bar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .cat-pill {
            padding: 9px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: #cbd5e1;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            white-space: nowrap;
            transition: all 0.3s ease;
            text-transform: capitalize;
        }

        .cat-pill:hover {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border-color: rgba(56, 189, 248, 0.3);
            transform: translateY(-2px);
        }

        .cat-pill.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.35);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            padding-bottom: 10px;
        }
        .section-header h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Products Grid & Cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .product-card {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
        }
        
        .product-card:hover { 
            transform: translateY(-8px); 
            border-color: rgba(56, 189, 248, 0.5); 
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.45); 
            background: rgba(15, 23, 42, 0.65);
        }

        /* Image Wrapper with Floating Heart */
        .img-wrap {
            position: relative !important;
            width: 100%;
            height: 220px;
            background: rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .img-wrap img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            transition: transform 0.4s ease; 
        }
        
        .product-card:hover .img-wrap img { 
            transform: scale(1.08); 
        }

        /* Full Circular Floating Heart Button */
        .wishlist-btn {
            position: absolute !important;
            top: 12px !important;
            right: 12px !important;
            width: 38px !important;
            height: 38px !important;
            background: #ffffff !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.28) !important;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            padding: 0 !important;
            z-index: 25 !important;
            outline: none !important;
        }

        .wishlist-btn:hover {
            transform: scale(1.15) !important;
        }

        .wishlist-btn svg {
            width: 20px !important;
            height: 20px !important;
            fill: none !important;
            stroke: #334155 !important;
            stroke-width: 2.2 !important;
            stroke-linecap: round !important;
            stroke-linejoin: round !important;
            transition: fill 0.2s ease, stroke 0.2s ease, transform 0.2s ease !important;
        }

        /* Active Liked State */
        .wishlist-btn.active svg {
            fill: #ef4444 !important;
            stroke: #ef4444 !important;
            transform: scale(1.08) !important;
        }

        .card-body { 
            padding: 18px; 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
        }
        
        .cat-tag { 
            display: inline-block;
            font-size: 11px; 
            text-transform: uppercase; 
            color: #38bdf8; 
            font-weight: 600; 
            letter-spacing: 0.5px; 
            background: rgba(56, 189, 248, 0.12); 
            padding: 3px 10px; 
            border-radius: 12px; 
            border: 1px solid rgba(56, 189, 248, 0.25); 
            margin-bottom: 6px; 
        }

        .prod-title { font-size: 16px; font-weight: 600; margin: 4px 0 8px 0; color: #f8fafc; }

        .btn-add {
            width: 100%;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border: none;
            padding: 11px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
            cursor: pointer;
            display: block;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }
        .btn-add:hover { opacity: 0.9; box-shadow: 0 6px 16px rgba(2, 132, 199, 0.5); }

        .no-prods {
            text-align: center;
            padding: 50px 20px;
            color: #94a3b8;
        }

        /* Search Bar Styles */
        .search-bar-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            max-width: 500px;
            position: relative;
            margin: 0 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px 18px;
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(15, 23, 42, 0.6);
            color: #f8fafc;
            font-size: 13px;
            outline: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .search-input::placeholder { color: #94a3b8; }

        .search-input:focus {
            border-color: #38bdf8;
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
        }

        .search-results-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 14px;
            max-height: 380px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(16px);
        }

        .search-results-dropdown.active { display: block; }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover { background: rgba(56, 189, 248, 0.12); }

        .search-result-image {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: cover;
            background: #1e293b;
            flex-shrink: 0;
        }

        .search-result-info { flex: 1; min-width: 0; }

        .search-result-name {
            font-size: 13px;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-result-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #94a3b8;
        }

        .search-result-price { font-weight: 700; color: #38bdf8; }
        .search-no-results { padding: 16px; text-align: center; color: #94a3b8; font-size: 13px; }

        /* Discount Badge */
        .discount-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            z-index: 20;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Price with Strikethrough */
        .prod-price-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .prod-price { font-size: 18px; font-weight: 700; color: #34d399; }
        .prod-price-mrp {
            font-size: 13px;
            color: #94a3b8;
            text-decoration: line-through;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <a href="../index.php" class="logo"><i class="fa-solid fa-bolt"></i> E-PRO</a>
    
    <!-- Live Search Bar -->
    <div class="search-bar-wrapper">
        <input 
            type="text" 
            id="searchInput" 
            class="search-input" 
            placeholder="Search products, categories..."
            autocomplete="off"
        >
        <div id="searchResultsDropdown" class="search-results-dropdown"></div>
    </div>

    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../index.php#about">About</a>
        <a href="products.php" style="color:#38bdf8; font-weight:600;">Products</a>
        <a href="feedback.php">Feedback</a>
        <a href="javascript:void(0)" class="cart-badge" onclick="if(typeof CartDrawerManager !== 'undefined') CartDrawerManager.open();" title="Open Shopping Cart">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="badge-count" id="cartCountBadge"><?php echo $cart_count; ?></span>
        </a>

        <?php if (isset($_SESSION['admin'])): ?>
            <a href="../admin/dashboard.php" style="color: #a78bfa;"><i class="fa-solid fa-lock"></i> Admin Panel</a>
        <?php endif; ?>

        <?php if (!empty($user_initial)): ?>
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="profile.php" class="user-avatar-badge" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                    <?php echo $user_initial; ?>
                </a>
                <a href="../logout.php" style="color:#f43f5e; font-size:13px;" title="Logout"><i class="fa-solid fa-power-off"></i></a>
            </div>
        <?php else: ?>
            <a href="login.php" style="color:#38bdf8; font-weight:600;">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">

    <!-- Category Filter Bar -->
    <div class="category-filter-bar">
        <a href="products.php?category=all" class="cat-pill <?php echo ($selected_cat === 'all') ? 'active' : ''; ?>">
            <i class="fa-solid fa-border-all"></i> All Products
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?php echo urlencode($cat); ?>" class="cat-pill <?php echo ($selected_cat === $cat) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Products Header -->
    <div class="section-header">
        <h2>
            <i class="fa-solid fa-tags" style="color:#38bdf8;"></i> 
            <?php echo ($selected_cat === 'all') ? 'All Store Collection' : ucfirst(htmlspecialchars($selected_cat)) . ' Collection'; ?>
        </h2>
    </div>

    <!-- Products Grid -->
    <?php if (!empty($db_products)): ?>
        <div class="products-grid">
            <?php foreach ($db_products as $p): 
                $img_path = resolveProductImage($p['image'] ?? '');
            ?>
            <div class="product-card" id="product-<?php echo $p['id']; ?>">
                <div class="img-wrap">
                    <!-- Discount Badge -->
                    <?php
                    $discount = 0;
                    if (!empty($p['mrp']) && $p['mrp'] > $p['price']) {
                        $discount = round(((floatval($p['mrp']) - floatval($p['price'])) / floatval($p['mrp'])) * 100);
                    }
                    if ($discount > 0):
                    ?>
                    <div class="discount-badge">
                        -<?php echo $discount; ?>% OFF
                    </div>
                    <?php endif; ?>

                    <!-- Wishlist Button -->
                    <button type="button" class="wishlist-btn" onclick="toggleHeart(this, <?php echo $p['id']; ?>)" aria-label="Like Product">
                        <svg viewBox="0 0 24 24">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
                    <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/300x300/1e293b/38bdf8?text=Product';">
                </div>
                <div class="card-body">
                    <div>
                        <span class="cat-tag">
                            <i class="fa-solid fa-tag"></i> <?php echo !empty($p['category']) ? htmlspecialchars($p['category']) : 'General'; ?>
                        </span>
                        <div class="prod-title"><?php echo htmlspecialchars($p['name']); ?></div>
                    </div>
                    <div>
                        <div class="prod-price-wrapper">
                            <div class="prod-price">₹<?php echo number_format($p['price'], 2); ?></div>
                            <?php if (!empty($p['mrp']) && $p['mrp'] > $p['price']): ?>
                            <div class="prod-price-mrp">₹<?php echo number_format($p['mrp'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                        <button class="btn-add" onclick="addToCartAjax(<?php echo $p['id']; ?>, this)" type="button">
                            <i class="fa-solid fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-prods">
            <i class="fa-solid fa-box-open" style="font-size: 40px; margin-bottom: 12px; color: #64748b;"></i>
            <h3>No Products Found</h3>
            <p>Is category mein abhi koi product available nahi hai.</p>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * Add Product to Cart via AJAX
 */
function addToCartAjax(productId, btn) {
    btn.disabled = true;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

    const formData = new FormData();
    formData.append('action', 'add_item');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('/EPRO/api/cart_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;

        if (data && (data.success || data.status === 'success')) {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Added to cart!', 'success');
            }
            updateCartCount();
            if (typeof CartDrawerManager !== 'undefined' && CartDrawerManager.open) {
                setTimeout(() => CartDrawerManager.open(), 300);
            }
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Error adding to cart', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        if (typeof showToast === 'function') {
            showToast('Failed to add to cart', 'error');
        }
    });
}

/**
 * Update cart count badge
 */
function updateCartCount() {
    fetch('/EPRO/api/cart_handler.php?action=get_cart')
        .then(response => response.json())
        .then(data => {
            if (data && data.success && data.items) {
                const count = data.items.reduce((sum, item) => sum + (parseInt(item.quantity) || 1), 0);
                const badge = document.getElementById('cartCountBadge');
                if (badge) badge.textContent = count;
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

/**
 * Toggle Wishlist
 */
function toggleHeart(btn, productId) {
    btn.classList.toggle('active');
    btn.style.transform = 'scale(1.25)';
    setTimeout(() => { btn.style.transform = ''; }, 200);

    const isActive = btn.classList.contains('active');
    const action = isActive ? 'add_to_wishlist' : 'remove_from_wishlist';

    const formData = new FormData();
    formData.append('action', action);
    formData.append('product_id', productId);

    fetch('/EPRO/api/wishlist_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data && (data.success || data.status === 'success')) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
        } else {
            btn.classList.toggle('active');
            if (typeof showToast === 'function') showToast(data.message || 'Error updating wishlist', 'error');
        }
    })
    .catch(err => {
        console.error('Wishlist error:', err);
        btn.classList.toggle('active');
        if (typeof showToast === 'function') showToast('Connection error', 'error');
    });
}

/**
 * Live Search Handler
 */
function initLiveSearch() {
    const searchInput = document.getElementById('searchInput');
    const dropdown = document.getElementById('searchResultsDropdown');
    let debounceTimer;

    if (!searchInput || !dropdown) return;

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length === 0) {
            dropdown.classList.remove('active');
            dropdown.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
}

function performSearch(query) {
    const dropdown = document.getElementById('searchResultsDropdown');

    fetch(`/EPRO/api/search_suggest.php?q=${encodeURIComponent(query)}&limit=8`)
        .then(response => response.json())
        .then(data => {
            let results = [];
            if (Array.isArray(data)) {
                results = data;
            } else if (data && data.results && Array.isArray(data.results)) {
                results = data.results;
            }

            showDropdown(results, query);
        })
        .catch(error => {
            console.error('Search error:', error);
            showDropdown([], query);
        });
}

function showDropdown(results, query) {
    const dropdown = document.getElementById('searchResultsDropdown');

    if (!results || results.length === 0) {
        dropdown.innerHTML = `<div class="search-no-results">No products found for "${htmlEscape(query)}"</div>`;
        dropdown.classList.add('active');
        return;
    }

    let html = '';
    results.forEach(product => {
        let img = product.image || '';
        if (!img.startsWith('http') && !img.startsWith('/')) {
            if (img.startsWith('assets/')) {
                img = `/EPRO/${img}`;
            } else {
                img = `/EPRO/assets/images/${img}`;
            }
        }
        
        const price = parseFloat(product.price || 0).toFixed(2);
        
        html += `
            <a href="javascript:void(0)" class="search-result-item" onclick="goToProduct(${product.id})">
                <img src="${img}" alt="${htmlEscape(product.name)}" class="search-result-image" onerror="this.src='https://placehold.co/100x100?text=Product'">
                <div class="search-result-info">
                    <div class="search-result-name">${htmlEscape(product.name)}</div>
                    <div class="search-result-meta">
                        <span>${htmlEscape(product.category || 'General')}</span>
                        <span class="search-result-price">₹${price}</span>
                    </div>
                </div>
            </a>
        `;
    });

    dropdown.innerHTML = html;
    dropdown.classList.add('active');
}

function goToProduct(productId) {
    const target = document.getElementById(`product-${productId}`);
    const dropdown = document.getElementById('searchResultsDropdown');
    if (dropdown) dropdown.classList.remove('active');
    
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.style.transition = 'box-shadow 0.3s ease';
        target.style.boxShadow = '0 0 25px rgba(56, 189, 248, 0.8)';
        setTimeout(() => {
            target.style.boxShadow = '';
        }, 2000);
    }
}

function htmlEscape(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    initLiveSearch();
    if (typeof CartDrawerManager !== 'undefined' && CartDrawerManager.init) {
        setTimeout(() => CartDrawerManager.init(), 100);
    }
});
</script>

<?php
// Include Cart Drawer Component
if (file_exists(__DIR__ . '/../includes/cart_drawer.php')) {
    include __DIR__ . '/../includes/cart_drawer.php';
}
?>
</body>
</html>