<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Connections (Root Level)
if (file_exists('db_connect.php')) {
    include 'db_connect.php';
} elseif (file_exists('config.php')) {
    include 'config.php';
}

// Special / Featured Products
$special_products = [];
if (isset($conn)) {
    $sp_query = "SELECT * FROM products WHERE is_special = 1 LIMIT 4";
    $sp_res = mysqli_query($conn, $sp_query);
    if ($sp_res && mysqli_num_rows($sp_res) > 0) {
        while ($row = mysqli_fetch_assoc($sp_res)) {
            $special_products[] = $row;
        }
    }
}

// Distinct Categories
$categories = [];
if (isset($conn)) {
    $cat_res = mysqli_query($conn, "SELECT DISTINCT category FROM products");
    if ($cat_res && mysqli_num_rows($cat_res) > 0) {
        while ($c_row = mysqli_fetch_assoc($cat_res)) {
            if (!empty($c_row['category'])) {
                $categories[] = $c_row['category'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-PRO Store - Home</title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            background: #f8f9fa; 
            margin: 0; 
            padding: 0; 
        }
        header { 
            background: #007bff; 
            color: white; 
            padding: 20px; 
            text-align: center; 
            font-size: 28px; 
            font-weight: bold; 
        }
        nav { 
            background: white; 
            padding: 12px 25px; 
            display: flex; 
            justify-content: space-between; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        nav a { 
            text-decoration: none; 
            color: #333; 
            margin: 0 12px; 
            font-weight: bold; 
            font-size: 15px; 
        }
        nav a:hover { color: #007bff; }
        .admin-link { color: #d9534f !important; }
        
        .hero { 
            background: linear-gradient(135deg, #007bff, #00c6ff); 
            color: white; 
            text-align: center; 
            padding: 60px 20px; 
        }
        .hero h1 { font-size: 38px; margin-bottom: 12px; }
        .hero p { font-size: 18px; margin-bottom: 25px; }
        .hero .shop-btn { 
            background: #28a745; 
            color: white; 
            padding: 12px 30px; 
            border-radius: 25px; 
            text-decoration: none; 
            font-size: 16px; 
            font-weight: bold; 
        }
        
        .section-title { 
            text-align: center; 
            margin: 40px 0 20px 0; 
            font-size: 24px; 
            color: #333; 
        }
        
        .categories-grid { 
            display: flex; 
            justify-content: center; 
            flex-wrap: wrap; 
            gap: 15px; 
            padding: 0 20px; 
        }
        .category-card { 
            background: white; 
            border: 2px solid #e9ecef; 
            border-radius: 10px; 
            padding: 15px 25px; 
            text-decoration: none; 
            color: #333; 
            font-weight: bold; 
            transition: 0.3s; 
        }
        .category-card:hover { 
            border-color: #007bff; 
            background: #007bff; 
            color: white; 
            transform: translateY(-3px); 
        }
        
        .products-grid { 
            display: flex; 
            justify-content: center; 
            flex-wrap: wrap; 
            padding: 20px; 
            max-width: 1200px; 
            margin: auto; 
        }
        .product-card { 
            background: white; 
            border-radius: 12px; 
            margin: 15px; 
            padding: 15px; 
            text-align: center; 
            width: 220px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            transition: 0.3s; 
        }
        .product-card:hover { transform: translateY(-5px); }
        .product-card img { 
            width: 100%; 
            height: 180px; 
            border-radius: 8px; 
            object-fit: cover; 
            background: #f9f9f9; 
        }
        .product-card h3 { 
            font-size: 16px; 
            margin: 10px 0 5px 0; 
            color: #333; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        .product-card p { font-weight: bold; color: #28a745; margin: 6px 0; }
        .product-card a { 
            display: inline-block; 
            margin-top: 8px; 
            padding: 8px 14px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 13px; 
            font-weight: bold; 
        }
        
        footer { 
            background: #333; 
            color: white; 
            text-align: center; 
            padding: 20px; 
            margin-top: 50px; 
        }
    </style>
</head>
<body>

<header>E-PRO Store</header>

<nav>
    <div>
        <a href="index.php">Home</a>
        <a href="pages/products.php">Products</a>
        <a href="pages/cart.php">Cart</a>
        <a href="pages/feedback.php">Feedback</a>
        <a href="admin/dashboard.php" class="admin-link">Admin Panel</a>
    </div>
    <?php if (isset($_SESSION['user_id']) || isset($_SESSION['id'])): ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login / Register</a>
    <?php endif; ?>
</nav>

<div class="hero">
    <h1>Welcome to E-PRO Store</h1>
    <p>Discover Top Quality Clothing, Electronics, Perfumes & Shoes</p>
    <a href="pages/products.php" class="shop-btn">Explore All Products</a>
</div>

<div class="section-title">Shop by Categories</div>
<div class="categories-grid">
    <?php foreach ($categories as $cat): ?>
        <a href="pages/products.php?category=<?php echo urlencode($cat); ?>" class="category-card">
            <?php echo htmlspecialchars(ucfirst($cat)); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="section-title">Featured Products</div>
<div class="products-grid">
    <?php if (!empty($special_products)): ?>
        <?php foreach ($special_products as $row): 
            $img_file = $row['image'];
            if (strpos($img_file, '/') !== false) {
                $img_path = "assets/images/" . $img_file;
            } else {
                $folder_name = pathinfo($img_file, PATHINFO_FILENAME);
                $img_path = "assets/images/" . $folder_name . "/" . $img_file;

                if (!file_exists($img_path)) {
                    $img_path = "assets/images/products/" . $img_file;
                }
            }
        ?>
            <div class="product-card">
                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/220x180?text=No+Image';">
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <p>₹<?php echo number_format($row['price'], 2); ?></p>
                <a href="pages/products.php?category=<?php echo urlencode($row['category']); ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#666;">No featured products found.</p>
    <?php endif; ?>
</div>

<footer>&copy; <?php echo date("Y"); ?> E-PRO Store. All rights reserved.</footer>

</body>
</html>