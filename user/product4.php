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
        $p_cat   = mysqli_real_escape_string($conn, $_POST['product_category'] ?? 'Clothing');
        $uid_sql = $user_id ? "'$user_id'" : "NULL";

        // 1. Check if product exists in `products` table
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
    <title>Product 4 - Epro Store</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 50px 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }

        /* --- FLEXBOX CONTAINER: Isse images side-by-side aayengi --- */
        .container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap; /* Screen choti hone par boxes niche aayenge */
            max-width: 1400px;
            margin: 0 auto;
        }

        .product-box {
            width: 280px; /* Width thodi kam ki hai taaki 4 side mein aa sakein */
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-box:hover {
            transform: translateY(-10px);
        }

        .product-box h2 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: contain; /* Image stretch nahi hogi */
            border-radius: 12px;
            margin-bottom: 15px;
            background: #fbfbfb;
        }

        .price {
            font-size: 22px;
            color: #28a745;
            margin: 10px 0;
            font-weight: bold;
        }

        .rating {
            color: gold;
            margin-bottom: 10px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<h1>Our Top Rated Products ⭐</h1>

<div class="container">

    <!-- Product 1: Blue Hoodie -->
    <div class="product-box">
        <div>
            <h2>Blue Hoodie ⭐</h2>
            <p><b>Top Rated by Customers</b></p>
            <div class="rating">⭐⭐⭐⭐⭐ (4.9/5)</div>
            <img src="../assets/images/product4/product4.png" alt="Blue Hoodie" class="product-img" onerror="this.src='https://placehold.co/200x200?text=Blue+Hoodie';">
        </div>
        <div>
            <div class="price">₹1499</div>
            <form method="POST" action="">
                <input type="hidden" name="product_name" value="Blue Hoodie">
                <input type="hidden" name="product_price" value="1499">
                <input type="hidden" name="product_category" value="Clothing">
                <input type="hidden" name="product_image" value="product4/product4.png">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        </div>
    </div>

    <!-- Product 2: Red Hoodie -->
    <div class="product-box">
        <div>
            <h2>Red Hoodie ⭐</h2>
            <p><b>Top Rated by Customers</b></p>
            <div class="rating">⭐⭐⭐⭐⭐ (4.9/5)</div>
            <img src="../assets/images/product4/red.png" alt="Red Hoodie" class="product-img" onerror="this.src='https://placehold.co/200x200?text=Red+Hoodie';">
        </div>
        <div>
            <div class="price">₹599</div>
            <form method="POST" action="">
                <input type="hidden" name="product_name" value="Red Hoodie">
                <input type="hidden" name="product_price" value="599">
                <input type="hidden" name="product_category" value="Clothing">
                <input type="hidden" name="product_image" value="product4/red.png">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        </div>
    </div>

    <!-- Product 3: Grey T-Shirt -->
    <div class="product-box">
        <div>
            <h2>Grey T-Shirt ⭐</h2>
            <p><b>Top Rated by Customers</b></p>
            <div class="rating">⭐⭐⭐⭐⭐ (4.9/5)</div>
            <img src="../assets/images/product4/grey.png" alt="Grey T-Shirt" class="product-img" onerror="this.src='https://placehold.co/200x200?text=Grey+T-Shirt';">
        </div>
        <div>
            <div class="price">₹899</div>
            <form method="POST" action="">
                <input type="hidden" name="product_name" value="Grey T-Shirt">
                <input type="hidden" name="product_price" value="899">
                <input type="hidden" name="product_category" value="Clothing">
                <input type="hidden" name="product_image" value="product4/grey.png">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        </div>
    </div>

    <!-- Product 4: White Shirt -->
    <div class="product-box">
        <div>
            <h2>White Shirt ⭐</h2>
            <p><b>Top Rated by Customers</b></p>
            <div class="rating">⭐⭐⭐⭐⭐ (4.9/5)</div>
            <img src="../assets/images/product4/shirt.png" alt="White Shirt" class="product-img" onerror="this.src='https://placehold.co/200x200?text=White+Shirt';">
        </div>
        <div>
            <div class="price">₹1099</div>
            <form method="POST" action="">
                <input type="hidden" name="product_name" value="White Shirt">
                <input type="hidden" name="product_price" value="1099">
                <input type="hidden" name="product_category" value="Clothing">
                <input type="hidden" name="product_image" value="product4/shirt.png">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        </div>
    </div>

</div> 
</body>
</html>