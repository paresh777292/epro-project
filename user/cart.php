<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// 1. PRODUCT ADD LOGIC (SECURE - prepared statement)
if (isset($_GET['add']) && isset($conn)) {
    $p_id = intval($_GET['add']);
    
    if ($p_id > 0) {
        // Check if product already in cart
        if ($user_id) {
            $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $check_stmt->bind_param("ii", $user_id, $p_id);
        } else {
            $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id IS NULL AND product_id = ?");
            $check_stmt->bind_param("i", $p_id);
        }
        
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $cart_id = $row['id'];
            
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
            $update_stmt->bind_param("i", $cart_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $insert_stmt->bind_param("ii", $user_id, $p_id);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
    header("Location: cart.php");
    exit();
}

// 2. FETCH ALL CART ITEMS (SECURE - prepared statement)
$total_bill = 0;
$cart_items = [];

if (isset($conn)) {
    if ($user_id) {
        $query_stmt = $conn->prepare(
            "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?"
        );
        $query_stmt->bind_param("i", $user_id);
    } else {
        $query_stmt = $conn->prepare(
            "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id IS NULL"
        );
    }
    
    $query_stmt->execute();
    $res = $query_stmt->get_result();
    
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cart_items[] = $row;
        }
    }
    $query_stmt->close();
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