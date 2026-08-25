<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Database Connection
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

// 3. Add to Cart Action
if (isset($_GET['add_cart'])) {
    $pid = intval($_GET['add_cart']);
    if ($pid > 0 && isset($conn) && $conn) {
        $uid_sql = $user_id ? "'$user_id'" : "NULL";
        $check_cart = mysqli_query($conn, "SELECT id FROM cart WHERE product_id='$pid' AND (user_id = $uid_sql OR (user_id IS NULL AND $uid_sql IS NULL))");

        if ($check_cart && mysqli_num_rows($check_cart) > 0) {
            mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE product_id='$pid' AND (user_id = $uid_sql OR (user_id IS NULL AND $uid_sql IS NULL))");
        } else {
            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($uid_sql, '$pid', 1)");
        }
        $msg = "Item successfully Cart mein add ho gaya!";
    }
}

// 4. Cart Count
$cart_count = 0;
if (isset($conn) && $conn) {
    $c_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id " . ($user_id ? "= '$user_id'" : "IS NULL"));
    if ($c_res && $crow = mysqli_fetch_assoc($c_res)) {
        $cart_count = (int)($crow['total'] ?? 0);
    }
}

// 5. Fetch Unique Categories for Filter Bar
$categories = [];
if (isset($conn) && $conn) {
    $cat_query = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    if ($cat_query) {
        while ($c = mysqli_fetch_assoc($cat_query)) {
            $categories[] = $c['category'];
        }
    }
}

// 6. Filter Products by Selected Category
$selected_cat = isset($_GET['category']) ? trim($_GET['category']) : 'all';

$db_products = [];
if (isset($conn) && $conn) {
    if ($selected_cat !== 'all') {
        $safe_cat = mysqli_real_escape_string($conn, $selected_cat);
        $p_res = mysqli_query($conn, "SELECT * FROM products WHERE category = '$safe_cat' ORDER BY id DESC");
    } else {
        $p_res = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
    }

    if ($p_res) {
        while ($row = mysqli_fetch_assoc($p_res)) {
            $db_products[] = $row;
        }
    }
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
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Poppins', sans-serif; 
            scroll-behavior: smooth; 
        }

        /* Dynamic Flowing Gradient Background */
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
        .toast-msg { 
            background: rgba(34, 197, 94, 0.25); 
            border: 1px solid #22c55e; 
            color: #4ade80; 
            padding: 12px; 
            border-radius: 10px; 
            text-align: center; 
            margin-bottom: 25px; 
            backdrop-filter: blur(8px);
        }

        /* Category Filter Tabs Bar */
        .category-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .category-filter-bar::-webkit-scrollbar {
            height: 4px;
        }
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
            margin: 20px 0 20px 0;
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

        /* Products Grid */
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

        .img-wrap {
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
        .product-card:hover .img-wrap img { transform: scale(1.08); }

        .card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        
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
        .prod-price { font-size: 18px; font-weight: 700; color: #34d399; margin-bottom: 14px; }

        .btn-add {
            width: 100%;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            text-decoration: none;
            padding: 11px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
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
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <a href="../index.php" class="logo"><i class="fa-solid fa-bolt"></i> E-PRO</a>
    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../index.php#about">About</a>
        <a href="products.php" style="color:#38bdf8; font-weight:600;">Products</a>
        <a href="feedback.php">Feedback</a>
        <a href="cart.php" class="cart-badge">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="badge-count"><?php echo $cart_count; ?></span>
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
    <?php if (!empty($msg)): ?>
        <div class="toast-msg"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

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
                $img_file = $p['image'];
                if (strpos($img_file, '/') !== false) {
                    $img_path = "../assets/images/" . $img_file;
                } else {
                    $folder = pathinfo($img_file, PATHINFO_FILENAME);
                    $img_path = "../assets/images/" . $folder . "/" . $img_file;
                    if (!file_exists($img_path)) {
                        $img_path = "../assets/images/products/" . $img_file;
                    }
                }
            ?>
            <div class="product-card">
                <div class="img-wrap">
                    <img src="<?php echo $img_path; ?>" alt="" onerror="this.onerror=null; this.src='https://placehold.co/300x300?text=No+Image';">
                </div>
                <div class="card-body">
                    <div>
                        <span class="cat-tag">
                            <i class="fa-solid fa-tag"></i> <?php echo !empty($p['category']) ? htmlspecialchars($p['category']) : 'General'; ?>
                        </span>
                        <div class="prod-title"><?php echo htmlspecialchars($p['name']); ?></div>
                    </div>
                    <div>
                        <div class="prod-price">₹<?php echo number_format($p['price'], 2); ?></div>
                        <a href="products.php?add_cart=<?php echo $p['id']; ?>" class="btn-add">
                            <i class="fa-solid fa-cart-plus"></i> Add to Cart
                        </a>
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
<script src="../assets/js/script.js"></script>
<!-- index.php ke liye: <script src="assets/js/script.js"></script> -->
</body>

</html>