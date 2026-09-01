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
    <!-- Font Awesome Icons for Sharp Vectors -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            gap: 24px;
            flex-wrap: wrap;
            max-width: 1300px;
            margin: 0 auto;
        }

        /* 3. Product Card Design */
        .product-box {
            background: white;
            width: 260px;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid #efefef;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 14px 35px rgba(0,0,0,0.12);
        }

        /* 4. Product Image Container & Floating Heart Button */
        .product-image-wrapper {
            position: relative;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: #f9f9f9;
            margin-bottom: 15px;
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            display: block;
            transition: transform 0.3s ease;
        }

        .product-box:hover .product-img {
            transform: scale(1.04);
        }

        /* White Floating Heart Button */
        .wishlist-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 38px;
            height: 38px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), background 0.2s ease;
            padding: 0;
            z-index: 10;
            outline: none;
        }

        .wishlist-btn:hover {
            transform: scale(1.15);
        }

        .wishlist-btn i {
            font-size: 18px;
            color: #64748b;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        /* Red Fill State when Liked */
        .wishlist-btn.active i {
            color: #ef4444 !important;
            transform: scale(1.1);
        }

        /* 5. Typography */
        .product-box h2 {
            font-size: 20px;
            margin: 10px 0 6px 0;
            color: #2d3436;
        }

        .product-box p {
            font-size: 13px;
            color: #d63031;
            font-weight: 600;
            margin-bottom: 12px;
        }

        /* 6. Price & Button */
        .price {
            font-size: 22px;
            font-weight: 800;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .btn-add-cart {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #0984e3;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-add-cart:hover {
            background: #074b83;
        }

        .btn-add-cart:active {
            transform: scale(0.98);
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
                <div class="product-image-wrapper">
                    <button type="button" class="wishlist-btn" onclick="toggleHeart(this, 1)" aria-label="Like Perfume">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <img src="../assets/images/product1/product1d.png" class="product-img" alt="Perfume" onerror="this.src='https://placehold.co/200x200?text=Perfume';">
                </div>
            </div>
            <div>
                <div class="price">₹1299</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Perfume">
                    <input type="hidden" name="product_price" value="1299">
                    <input type="hidden" name="product_category" value="Fragrance">
                    <input type="hidden" name="product_image" value="product1/product1d.png">
                    <button type="submit" name="add_to_cart" class="btn-add-cart">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 2: Trimmer -->
        <div class="product-box">
            <div>
                <h2>Trimmer</h2>
                <p>Limited Stock Available</p>
                <div class="product-image-wrapper">
                    <button type="button" class="wishlist-btn" onclick="toggleHeart(this, 2)" aria-label="Like Trimmer">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <img src="../assets/images/product1/trim.png" class="product-img" alt="Trimmer" onerror="this.src='https://placehold.co/200x200?text=Trimmer';">
                </div>
            </div>
            <div>
                <div class="price">₹1450</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Trimmer">
                    <input type="hidden" name="product_price" value="1450">
                    <input type="hidden" name="product_category" value="Grooming">
                    <input type="hidden" name="product_image" value="product1/trim.png">
                    <button type="submit" name="add_to_cart" class="btn-add-cart">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 3: Hair Dryer -->
        <div class="product-box">
            <div>
                <h2>Hair Dryer</h2>
                <p>Limited Stock Available</p>
                <div class="product-image-wrapper">
                    <button type="button" class="wishlist-btn" onclick="toggleHeart(this, 3)" aria-label="Like Hair Dryer">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <img src="../assets/images/product1/hair.png" class="product-img" alt="Hair Dryer" onerror="this.src='https://placehold.co/200x200?text=Hair+Dryer';">
                </div>
            </div>
            <div>
                <div class="price">₹2200</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Hair Dryer">
                    <input type="hidden" name="product_price" value="2200">
                    <input type="hidden" name="product_category" value="Appliances">
                    <input type="hidden" name="product_image" value="product1/hair.png">
                    <button type="submit" name="add_to_cart" class="btn-add-cart">Add to Cart</button>
                </form>
            </div>
        </div>

        <!-- Product 4: Smart Ring -->
        <div class="product-box">
            <div>
                <h2>Smart ring</h2>
                <p>Limited Stock Available</p>
                <div class="product-image-wrapper">
                    <button type="button" class="wishlist-btn" onclick="toggleHeart(this, 4)" aria-label="Like Smart ring">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <img src="../assets/images/product1/ring.png" class="product-img" alt="Smart ring" onerror="this.src='https://placehold.co/200x200?text=Smart+Ring';">
                </div>
            </div>
            <div>
                <div class="price">₹12500</div>
                <form method="POST" action="">
                    <input type="hidden" name="product_name" value="Smart ring">
                    <input type="hidden" name="product_price" value="12500">
                    <input type="hidden" name="product_category" value="Wearables">
                    <input type="hidden" name="product_image" value="product1/ring.png">
                    <button type="submit" name="add_to_cart" class="btn-add-cart">Add to Cart</button>
                </form>
            </div>
        </div>

    </div>

    <script src="../assets/js/script.js"></script>
    <script>
    function toggleHeart(btn, productId) {
        const icon = btn.querySelector('i');
        btn.classList.toggle('active');

        // Toggle Regular (Outline) vs Solid (Filled Red)
        if (btn.classList.contains('active')) {
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
        } else {
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular');
        }

        // AJAX Request to Backend
        const targetUrl = window.location.pathname.includes('/user/') ? '../like_dislike.php' : 'like_dislike.php';
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('action', 'like');

        fetch(targetUrl, {
            method: 'POST',
            body: formData
        }).catch(err => console.error('Wishlist error:', err));
    }
    </script>
</body>
</html>