<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
}

$user_id = $_SESSION['user_id'] ?? null;

// Handle Add to Cart Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (isset($conn) && $conn) {
        $p_name  = mysqli_real_escape_string($conn, $_POST['product_name']);
        $p_price = floatval($_POST['product_price']);
        $p_img   = mysqli_real_escape_string($conn, $_POST['product_image']);
        $p_cat   = mysqli_real_escape_string($conn, $_POST['product_category'] ?? 'Footwear');
        $uid_sql = $user_id ? "'$user_id'" : "NULL";

        // 1. Check if product exists in `products` table, else insert
        $check_prod = mysqli_query($conn, "SELECT id FROM products WHERE name = '$p_name' LIMIT 1");
        if ($check_prod && mysqli_num_rows($check_prod) > 0) {
            $row = mysqli_fetch_assoc($check_prod);
            $pid = $row['id'];
        } else {
            mysqli_query($conn, "INSERT INTO products (name, price, category, image) VALUES ('$p_name', '$p_price', '$p_cat', '$p_img')");
            $pid = mysqli_insert_id($conn);
        }

        // 2. Add or Update in `cart` table
        $check_cart = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE product_id = '$pid' AND (user_id = $uid_sql OR (user_id IS NULL AND $uid_sql IS NULL))");
        if ($check_cart && mysqli_num_rows($check_cart) > 0) {
            mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE product_id = '$pid' AND (user_id = $uid_sql OR (user_id IS NULL AND $uid_sql IS NULL))");
        } else {
            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($uid_sql, '$pid', 1)");
        }

        // 3. Redirect to Cart Page
        header("Location: cart.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Sellers - Epro Store</title>
    <style>
        /* Modern CSS Design */
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
        }

        .header-nav {
            text-align: right;
            max-width: 1200px;
            margin: 0 auto 20px auto;
        }

        .view-cart-btn {
            background: #0984e3;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }

        h1 {
            text-align: center;
            color: #2d3436;
            margin-bottom: 40px;
        }

        /* FLEXBOX CONTAINER: Sab products ko side-by-side lane ke liye */
        .container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-box {
            background: white;
            width: 260px;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-box:hover {
            transform: translateY(-10px);
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: #fafafa;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .price {
            font-size: 22px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .add-btn {
            width: 100%;
            border: none;
            cursor: pointer;
            background: #ff7675;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            transition: 0.3s;
        }

        .add-btn:hover {
            background: #d63031;
        }

        .fire-icon { color: #e17055; }
    </style>
</head>
<body>

    <div class="header-nav">
        <a href="cart.php" class="view-cart-btn">🛒 View My Cart</a>
    </div>

    <h1>Best Sellers of the Month <span class="fire-icon">🔥</span></h1>

    <div class="container">

        <!-- Product 1: Sneakers -->
        <div class="product-box">
            <div>
                <h2>Sneakers</h2>
                <img src="../assets/images/product2/sneaker.png" class="product-img" alt="Sneakers" onerror="this.src='https://placehold.co/200x200?text=Sneakers';">
            </div>
            <div>
                <div class="price">₹999</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Sneakers">
                    <input type="hidden" name="product_price" value="999">
                    <input type="hidden" name="product_category" value="Footwear">
                    <input type="hidden" name="product_image" value="product2/sneaker.png">
                    <button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 2: Leather Pair -->
        <div class="product-box">
            <div>
                <h2>Leather Pair</h2>
                <img src="../assets/images/product2/pair.png" class="product-img" alt="Leather Pair" onerror="this.src='https://placehold.co/200x200?text=Leather+Pair';">
            </div>
            <div>
                <div class="price">₹999</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Leather Pair">
                    <input type="hidden" name="product_price" value="999">
                    <input type="hidden" name="product_category" value="Footwear">
                    <input type="hidden" name="product_image" value="product2/pair.png">
                    <button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 3: Sports Shoes -->
        <div class="product-box">
            <div>
                <h2>Sports Shoes</h2>
                <img src="../assets/images/product2/sports.png" class="product-img" alt="Sports Shoes" onerror="this.src='https://placehold.co/200x200?text=Sports+Shoes';">
            </div>
            <div>
                <div class="price">₹999</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Sports Shoes">
                    <input type="hidden" name="product_price" value="999">
                    <input type="hidden" name="product_category" value="Footwear">
                    <input type="hidden" name="product_image" value="product2/sports.png">
                    <button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 4: Sandals -->
        <div class="product-box">
            <div>
                <h2>Sandals</h2>
                <img src="../assets/images/product2/sandle.png" class="product-img" alt="Sandals" onerror="this.src='https://placehold.co/200x200?text=Sandals';">
            </div>
            <div>
                <div class="price">₹999</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Sandals">
                    <input type="hidden" name="product_price" value="999">
                    <input type="hidden" name="product_category" value="Footwear">
                    <input type="hidden" name="product_image" value="product2/sandle.png">
                    <button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>