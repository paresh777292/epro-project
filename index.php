<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
if (file_exists('db_connect.php')) {
    include 'db_connect.php';
} elseif (file_exists('config.php')) {
    include 'config.php';
}

// Gmail-Style Avatar Initial
$user_initial = "";
if (!empty($_SESSION['user_name'])) {
    $user_initial = strtoupper(substr(trim($_SESSION['user_name']), 0, 1));
} elseif (!empty($_SESSION['user_email'])) {
    $user_initial = strtoupper(substr(trim($_SESSION['user_email']), 0, 1));
}

$user_id = $_SESSION['user_id'] ?? null;

// Cart Count Fetch
$cart_count = 0;
if (isset($conn) && $conn) {
    $c_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id " . ($user_id ? "= '$user_id'" : "IS NULL"));
    if ($c_res && $crow = mysqli_fetch_assoc($c_res)) {
        $cart_count = (int)($crow['total'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-PRO | Home Showcase</title>
    <!-- Google Fonts & Font Awesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; scroll-behavior: smooth; }
        
        body {
            background-color: #0b0f19;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.14) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(129, 140, 248, 0.14) 0%, transparent 40%);
            min-height: 100vh;
            color: #f8fafc;
        }

        /* Frosted Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            border: 2px solid rgba(255, 255, 255, 0.3);
            text-decoration: none !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            transition: transform 0.2s;
        }
        .user-avatar-badge:hover { transform: scale(1.08); }

        /* Hero Banner */
        .hero {
            text-align: center;
            padding: 70px 20px 40px 20px;
            max-width: 850px;
            margin: auto;
        }
        .hero h1 {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 14px;
            background: linear-gradient(90deg, #38bdf8, #818cf8, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p { color: #94a3b8; font-size: 16px; margin-bottom: 25px; }

        .container { max-width: 1200px; margin: auto; padding: 0 20px; }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 25px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 12px;
        }
        .section-header h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* 4 Dedicated Category Boxes Grid */
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 60px;
        }

        .folder-card {
            background: rgba(30, 41, 59, 0.55);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 24px;
            text-decoration: none;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .folder-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .folder-card:hover {
            transform: translateY(-10px);
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
            background: rgba(30, 41, 59, 0.75);
        }

        .folder-card:hover::before {
            opacity: 1;
        }

        /* ---------------- MOVING AVATARS STAGE ---------------- */
        .avatar-stage {
            width: 100%;
            height: 180px;
            background: radial-gradient(circle at center, rgba(15, 23, 42, 0.8) 0%, rgba(11, 15, 25, 0.95) 100%);
            border-radius: 14px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
        }

        /* Continuous Animations */
        @keyframes avatarFloat {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.05); }
            100% { transform: translateY(0px) scale(1); }
        }

        @keyframes auraPulse {
            0% { transform: scale(0.9); opacity: 0.4; }
            50% { transform: scale(1.15); opacity: 0.8; }
            100% { transform: scale(0.9); opacity: 0.4; }
        }

        @keyframes ringSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Background Glowing Aura Ring */
        .aura-glow {
            position: absolute;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            filter: blur(14px);
            animation: auraPulse 3.5s ease-in-out infinite;
            z-index: 1;
        }

        /* Orbit Dashed Ring */
        .orbit-ring {
            position: absolute;
            width: 115px;
            height: 115px;
            border-radius: 50%;
            border: 1.5px dashed rgba(255, 255, 255, 0.15);
            animation: ringSpin 12s linear infinite;
            z-index: 1;
        }

        /* Moving Main Icon Avatar */
        .animated-avatar {
            position: relative;
            z-index: 2;
            font-size: 50px;
            animation: avatarFloat 3.2s ease-in-out infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .folder-card:hover .animated-avatar {
            animation-duration: 1.6s;
        }

        /* Custom Colors per Category */
        .theme-1 .aura-glow { background: rgba(56, 189, 248, 0.45); }
        .theme-1 .animated-avatar { color: #38bdf8; text-shadow: 0 0 25px rgba(56, 189, 248, 0.6); }

        .theme-2 .aura-glow { background: rgba(168, 85, 247, 0.45); }
        .theme-2 .animated-avatar { color: #c084fc; text-shadow: 0 0 25px rgba(168, 85, 247, 0.6); }

        .theme-3 .aura-glow { background: rgba(52, 211, 153, 0.45); }
        .theme-3 .animated-avatar { color: #34d399; text-shadow: 0 0 25px rgba(52, 211, 153, 0.6); }

        .theme-4 .aura-glow { background: rgba(244, 114, 182, 0.45); }
        .theme-4 .animated-avatar { color: #f472b6; text-shadow: 0 0 25px rgba(244, 114, 182, 0.6); }

        /* Card Text & Action Button */
        .folder-tag {
            font-size: 11px;
            color: #38bdf8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .folder-title {
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 6px;
        }

        .folder-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 18px;
        }

        .folder-btn {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.25);
            transition: all 0.2s ease;
        }

        .folder-card:hover .folder-btn {
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.45);
        }

        /* About Section */
        .section-card {
            background: rgba(30, 41, 59, 0.55);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 35px 30px;
            margin-bottom: 60px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.35);
        }
        .section-card h2 {
            font-size: 24px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-card p {
            color: #cbd5e1;
            line-height: 1.7;
            font-size: 15px;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <a href="index.php" class="logo"><i class="fa-solid fa-bolt"></i> E-PRO</a>
    <div class="nav-links">
        <a href="index.php" style="color:#38bdf8;">Home</a>
        <a href="#about">About</a>
        <a href="user/products.php">Products</a>
        <a href="user/feedback.php">Feedback</a>
        <a href="user/cart.php" class="cart-badge">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="badge-count"><?php echo $cart_count; ?></span>
        </a>

        <?php if (isset($_SESSION['admin'])): ?>
            <a href="admin/dashboard.php" style="color: #a78bfa;"><i class="fa-solid fa-lock"></i> Admin Panel</a>
        <?php endif; ?>

        <?php if (!empty($user_initial)): ?>
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="user/profile.php" class="user-avatar-badge" title="Profile">
                    <?php echo $user_initial; ?>
                </a>
                <a href="logout.php" style="color:#f43f5e; font-size:13px;" title="Logout"><i class="fa-solid fa-power-off"></i></a>
            </div>
        <?php else: ?>
            <a href="user/login.php" style="color:#38bdf8; font-weight:600;">Login</a>
            <a href="user/signup.php" style="background:#0284c7; color:white; padding:8px 16px; border-radius:8px; font-weight:600;">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero Section -->
<div class="hero">
    <h1>Explore Our Curated Collections</h1>
    <p>Select any product collection box below to browse all gallery variants & available stock.</p>
</div>

<div class="container">
    <!-- 4 Clickable Product Boxes with Live Moving Avatars -->
    <div class="section-header">
        <h2><i class="fa-solid fa-layer-group" style="color:#38bdf8;"></i> Featured Product Categories</h2>
    </div>

    <div class="folder-grid">
        <!-- Box 1 -> Product 1 (Audio / Earbuds Moving Avatar) -->
        <a href="user/product1.php" class="folder-card theme-1">
            <div class="avatar-stage">
                <div class="aura-glow"></div>
                <div class="orbit-ring"></div>
                <div class="animated-avatar">
                    <i class="fa-solid fa-headphones-simple"></i>
                </div>
            </div>
            <span class="folder-tag">Category 01</span>
            <div class="folder-title">Product 1</div>
            <div class="folder-subtitle">View all items inside Product 1</div>
            <div class="folder-btn">
                <span>Open Product 1</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <!-- Box 2 -> Product 2 (Streetwear / Hoodie Moving Avatar) -->
        <a href="user/product2.php" class="folder-card theme-2">
            <div class="avatar-stage">
                <div class="aura-glow"></div>
                <div class="orbit-ring" style="animation-duration: 9s;"></div>
                <div class="animated-avatar" style="animation-delay: -0.8s;">
                    <i class="fa-solid fa-vest-patches"></i>
                </div>
            </div>
            <span class="folder-tag">Category 02</span>
            <div class="folder-title">Product 2</div>
            <div class="folder-subtitle">View all items inside Product 2</div>
            <div class="folder-btn">
                <span>Open Product 2</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <!-- Box 3 -> Product 3 (Pro Gear / Studio Moving Avatar) -->
        <a href="user/product3.php" class="folder-card theme-3">
            <div class="avatar-stage">
                <div class="aura-glow"></div>
                <div class="orbit-ring" style="animation-duration: 15s;"></div>
                <div class="animated-avatar" style="animation-delay: -1.6s;">
                    <i class="fa-solid fa-headset"></i>
                </div>
            </div>
            <span class="folder-tag">Category 03</span>
            <div class="folder-title">Product 3</div>
            <div class="folder-subtitle">View all items inside Product 3</div>
            <div class="folder-btn">
                <span>Open Product 3</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <!-- Box 4 -> Product 4 (Apparel / Basics Moving Avatar) -->
        <a href="user/product4.php" class="folder-card theme-4">
            <div class="avatar-stage">
                <div class="aura-glow"></div>
                <div class="orbit-ring" style="animation-duration: 10s;"></div>
                <div class="animated-avatar" style="animation-delay: -2.4s;">
                    <i class="fa-solid fa-shirt"></i>
                </div>
            </div>
            <span class="folder-tag">Category 04</span>
            <div class="folder-title">Product 4</div>
            <div class="folder-subtitle">View all items inside Product 4</div>
            <div class="folder-btn">
                <span>Open Product 4</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
    </div>

    <!-- About Section -->
    <div id="about" class="section-card">
        <h2><i class="fa-solid fa-circle-info"></i> About E-PRO</h2>
        <p>
            <b>E-PRO</b> is an intuitive e-commerce showcase engineered for speed, clean aesthetics, and seamless user experience. Explore our specialized categories and enjoy interactive variant galleries with direct-to-cart purchasing.
        </p>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<!-- index.php ke liye: <script src="assets/js/script.js"></script> -->

</body>

</html>