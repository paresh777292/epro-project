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
        $p_cat   = mysqli_real_escape_string($conn, $_POST['product_category'] ?? 'General');
        $uid_sql = $user_id ? "'$user_id'" : "NULL";

        // 1. Check if product already exists in `products` table
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
    <title>Exclusive Collection - E-PRO Store</title>
    <style>
        /* 1. Basic Reset */
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 40px 20px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #1a1a1a;
            margin-bottom: 50px;
            font-size: 2.5rem;
        }

        /* 2. Container */
        .container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            max-width: 1300px;
            margin: 0 auto;
        }

        /* 3. Product Card Design */
        .product-box {
            background: white;
            width: 260px;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid #efefef;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        /* 4. Image Styling */
        .product-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 10px;
        }

        /* 5. Typography */
        .product-box h2 {
            font-size: 20px;
            margin: 10px 0;
            color: #2d3436;
        }

        .product-box p {
            font-size: 13px;
            color: #d63031;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* 6. Price & Button */
        .price {
            font-size: 22px;
            font-weight: 800;
            color: #27ae60;
            margin-bottom: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #0984e3;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #074b83;
        }
    </style>
</head>
<body>

    <h1>Exclusive Collection ⚠️</h1>

    <div class="container">

        <!-- Product 1: Perfume -->
        <div class="product-box">
            <div>
                <h2>Perfume</h2>
                <p>Limited Stock Available</p>
                <img src="../assets/images/product1/product1d.png" class="product-img" alt="Perfume" onerror="this.src='https://placehold.co/200x200?text=Perfume';">
            </div>
            <div>
                <div class="price">₹1299</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Perfume">
                    <input type="hidden" name="product_price" value="1299">
                    <input type="hidden" name="product_category" value="Fragrance">
                    <input type="hidden" name="product_image" value="product1/product1d.png">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 2: Trimmer -->
        <div class="product-box">
            <div>
                <h2>Trimmer</h2>
                <p>Limited Stock Available</p>
                <img src="../assets/images/product1/trim.png" class="product-img" alt="Trimmer" onerror="this.src='https://placehold.co/200x200?text=Trimmer';">
            </div>
            <div>
                <div class="price">₹1450</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Trimmer">
                    <input type="hidden" name="product_price" value="1450">
                    <input type="hidden" name="product_category" value="Grooming">
                    <input type="hidden" name="product_image" value="product1/trim.png">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 3: Hair Dryer -->
        <div class="product-box">
            <div>
                <h2>Hair Dryer</h2>
                <p>Limited Stock Available</p>
                <img src="../assets/images/product1/hair.png" class="product-img" alt="Hair Dryer" onerror="this.src='https://placehold.co/200x200?text=Hair+Dryer';">
            </div>
            <div>
                <div class="price">₹2200</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Hair Dryer">
                    <input type="hidden" name="product_price" value="2200">
                    <input type="hidden" name="product_category" value="Appliances">
                    <input type="hidden" name="product_image" value="product1/hair.png">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 4: Smart Ring -->
        <div class="product-box">
            <div>
                <h2>Smart ring</h2>
                <p>Limited Stock Available</p>
                <img src="../assets/images/product1/ring.png" class="product-img" alt="Smart ring" onerror="this.src='https://placehold.co/200x200?text=Smart+Ring';">
            </div>
            <div>
                <div class="price">₹12500</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Smart ring">
                    <input type="hidden" name="product_price" value="12500">
                    <input type="hidden" name="product_category" value="Wearables">
                    <input type="hidden" name="product_image" value="product1/ring.png">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            </div>
        </div>

    </div>

</body>
<script src="../assets/js/script.js"></script>
<!-- index.php ke liye: <script src="assets/js/script.js"></script> -->
</html>