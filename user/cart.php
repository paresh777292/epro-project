<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// 1. PRODUCT ADD LOGIC (Direct query handling)
if (isset($_GET['add']) && isset($conn)) {
    $p_id = intval($_GET['add']);
    
    if ($p_id > 0) {
        $check_query = $user_id 
            ? "SELECT id, quantity FROM cart WHERE user_id='$user_id' AND product_id='$p_id'"
            : "SELECT id, quantity FROM cart WHERE (user_id IS NULL OR user_id=0) AND product_id='$p_id'";
            
        $check_res = mysqli_query($conn, $check_query);
        
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $row = mysqli_fetch_assoc($check_res);
            $cart_id = $row['id'];
            mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE id='$cart_id'");
        } else {
            $insert_query = $user_id
                ? "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$p_id', 1)"
                : "INSERT INTO cart (user_id, product_id, quantity) VALUES (NULL, '$p_id', 1)";
            mysqli_query($conn, $insert_query);
        }
    }
    header("Location: cart.php");
    exit();
}

// 2. FETCH ALL CART ITEMS (Guest + Logged In)
$total_bill = 0;
$cart_items = [];

if (isset($conn)) {
    if ($user_id) {
        $query = "SELECT c.id AS cart_id, c.quantity, p.name, p.price, p.image 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = '$user_id' OR c.user_id IS NULL OR c.user_id = 0";
    } else {
        $query = "SELECT c.id AS cart_id, c.quantity, p.name, p.price, p.image 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id";
    }
    
    $res = mysqli_query($conn, $query);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $cart_items[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Shopping Cart</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #fff; min-height: 100vh; margin: 0; padding: 20px; text-align: center; }
        h2 { color: #38bdf8; margin-bottom: 25px; }
        .cart-container { max-width: 900px; margin: auto; background: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #0284c7; color: white; }
        th, td { padding: 14px; border-bottom: 1px solid #334155; text-align: center; }
        img { border-radius: 8px; object-fit: cover; background: #334155; }
        .empty-msg { padding: 40px; font-size: 18px; color: #94a3b8; }
        .btn-group { margin-top: 25px; }
        .btn { padding: 10px 20px; text-decoration: none; color: white; border-radius: 6px; font-weight: bold; margin: 5px; display: inline-block; }
        .btn-pay { background: #10b981; }
        .btn-clear { background: #ef4444; }
        .btn:hover { opacity: 0.9; }
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
                <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $row): 
                        $subtotal = $row['price'] * $row['quantity'];
                        $total_bill += $subtotal;
                        
                        $img_file = $row['image'];
                        $final_img = (strpos($img_file, '/') !== false) ? "../assets/images/" . $img_file : "../assets/images/products/" . $img_file;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <img src="<?php echo $final_img; ?>" width="60" height="60" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/60x60?text=Product';">
                        </td>
                        <td>₹<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>₹<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4" style="text-align:right; font-weight:bold;">Grand Total:</td>
                        <td style="font-weight:bold; color:#38bdf8;">₹<?php echo number_format($total_bill, 2); ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty-msg">Your Cart is Empty!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="btn-group">
        <a href="payment.php" class="btn btn-pay">Proceed to Payment</a>
        <a href="clear_cart.php" class="btn btn-clear" onclick="return confirm('Clear entire cart?');">Clear Cart</a>
        <br><br>
        <a href="../index.php" style="text-decoration: none; color: #38bdf8;">← Continue Shopping</a>
    </div>

</body>
</html>