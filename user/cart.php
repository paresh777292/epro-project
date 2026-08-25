<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Database Connection & Auth Include
if (file_exists(__DIR__ . '/../db_connect.php')) {
    include __DIR__ . '/../db_connect.php';
}
if (file_exists(__DIR__ . '/../config.php')) {
    include __DIR__ . '/../config.php';
}
if (file_exists(__DIR__ . '/../includes/auth.php')) {
    include __DIR__ . '/../includes/auth.php';
}

// 2. User Identification (Logged-in User ya Guest User)
$user_id = null;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = intval($_SESSION['id']);
}

// Agar session me user nahi hai, toh database se pehla valid user id fetch karein
if (!$user_id && isset($conn)) {
    $u_check = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if ($u_check && mysqli_num_rows($u_check) > 0) {
        $u_row = mysqli_fetch_assoc($u_check);
        $user_id = intval($u_row['id']);
    }
}

// 3. PRODUCT ADD LOGIC
if (isset($_GET['add']) && isset($conn)) {
    $p_id = intval($_GET['add']);
    
    if ($p_id > 0) {
        // Check karein item pehle se cart me hai ya nahi
        if ($user_id) {
            $check_res = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id' AND product_id='$p_id'");
        } else {
            $check_res = mysqli_query($conn, "SELECT * FROM cart WHERE user_id IS NULL AND product_id='$p_id'");
        }
        
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            if ($user_id) {
                mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE user_id='$user_id' AND product_id='$p_id'");
            } else {
                mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE user_id IS NULL AND product_id='$p_id'");
            }
        } else {
            if ($user_id) {
                mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$p_id', 1)");
            } else {
                mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (NULL, '$p_id', 1)");
            }
        }
    }
    header("Location: cart.php");
    exit();
}

// 4. DATA FETCH LOGIC
$total_bill = 0; 
$result = false;

if (isset($conn)) {
    if ($user_id) {
        $query = "SELECT c.id AS cart_id, c.quantity, p.name, p.price, p.image, p.is_special 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = '$user_id'";
    } else {
        $query = "SELECT c.id AS cart_id, c.quantity, p.name, p.price, p.image, p.is_special 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id IS NULL";
    }
    $result = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Shopping Cart</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #d1d8e0, #fbc2eb); min-height: 100vh; margin: 0; padding: 20px; text-align: center; }
        h2 { color: #333; margin-bottom: 30px; }
        .cart-container { max-width: 900px; margin: auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #007bff; color: white; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: center; }
        img { border-radius: 8px; object-fit: cover; background: #f9f9f9; }
        .empty-msg { padding: 40px; font-size: 18px; color: #666; }
        .btn-group { margin-top: 20px; }
        .btn { padding: 12px 25px; text-decoration: none; color: white; border-radius: 8px; font-weight: bold; margin: 5px; display: inline-block; }
        .btn-pay { background: #28a745; }
        .btn-clear { background: #dc3545; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); transition: 0.3s; }
    </style>
</head>
<body>

    <h2>Your Shopping Cart</h2>

    <div class="cart-container">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        $subtotal = $row['price'] * $row['quantity'];
                        $total_bill += $subtotal; 
                        
                        // Image Path Handler
                        $img_file = $row['image'];
                        if (strpos($img_file, '/') !== false) {
                            $final_img = "../assets/images/" . $img_file;
                        } else {
                            $folder_from_name = pathinfo($img_file, PATHINFO_FILENAME);
                            $final_img = "../assets/images/" . $folder_from_name . "/" . $img_file;
                            
                            if (!file_exists($final_img)) {
                                $final_img = "../assets/images/products/" . $img_file;
                            }
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <img src="<?php echo $final_img; ?>" width="60" height="60" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/60x60?text=No+Image';">
                        </td>
                        <td>₹<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>₹<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="4" style="text-align:right; font-weight:bold;">Grand Total:</td>
                        <td style="font-weight:bold; color:#007bff;">₹<?php echo number_format($total_bill, 2); ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty-msg">Cart is empty!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="btn-group">
        <a href="payment.php" class="btn btn-pay">Proceed to Payment</a>
        <a href="clear_cart.php" class="btn btn-clear" onclick="return confirm('Clear entire cart?');">Clear Cart</a>
        <br><br>
        <a href="products.php" style="text-decoration: none; color: #007bff;">← Continue Shopping</a>
    </div>

</body>
</html>