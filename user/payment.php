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

$user_id = null;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = intval($_SESSION['id']);
}

// Cart Total Fetch
$total_bill = 0;
$cart_items = [];

if (isset($conn) && $conn) {
    if ($user_id) {
        $cart_query = "SELECT c.quantity, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = '$user_id'";
    } else {
        $cart_query = "SELECT c.quantity, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id IS NULL";
    }
    
    $cart_res = mysqli_query($conn, $cart_query);
    if ($cart_res && mysqli_num_rows($cart_res) > 0) {
        while ($row = mysqli_fetch_assoc($cart_res)) {
            $cart_items[] = $row;
            $total_bill += ($row['price'] * $row['quantity']);
        }
    }
}

$order_success = false;
$error_msg = "";

// PAYMENT SUBMIT LOGIC -> INSERT INTO ORDERS (With Explicit order_date for Sales Graph)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    if ($total_bill > 0 && isset($conn) && $conn) {
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'Online');
        $uid_val = $user_id ? "'$user_id'" : "NULL";
        
        // 1. Insert Order with Explicit Timestamp for Sales Graph Accuracy
        $insert_order = "INSERT INTO `orders` (`user_id`, `total_amount`, `payment_method`, `order_date`) 
                        VALUES ($uid_val, '$total_bill', '$payment_method', NOW())";
        
        if (mysqli_query($conn, $insert_order)) {
            // 2. Clear Cart after successful order insert
            if ($user_id) {
                mysqli_query($conn, "DELETE FROM `cart` WHERE `user_id` = '$user_id'");
            } else {
                mysqli_query($conn, "DELETE FROM `cart` WHERE `user_id` IS NULL");
            }
            $order_success = true;
        } else {
            $error_msg = "Order save karne mein error aaya: " . mysqli_error($conn);
        }
    }
}

if ($total_bill <= 0 && !$order_success) {
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout & Payment - EPRO</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body { background:#0f172a; color:#f8fafc; min-height:100vh; padding:30px 20px; }
        .payment-container { max-width:650px; margin:auto; background:rgba(30,41,59,0.7); backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:30px; box-shadow:0 15px 35px rgba(0,0,0,0.4); }
        h2 { text-align:center; margin-bottom:20px; background:linear-gradient(90deg, #38bdf8, #818cf8); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .summary-box { background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:18px; margin-bottom:25px; }
        .summary-row { display:flex; justify-content:space-between; margin:8px 0; font-size:14px; color:#cbd5e1; }
        .total-amount { font-size:18px; font-weight:700; color:#34d399; border-top:1px solid rgba(255,255,255,0.1); padding-top:10px; margin-top:10px; }
        .method-card { border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:14px; margin-bottom:12px; cursor:pointer; display:flex; align-items:center; background:rgba(15,23,42,0.5); transition:0.2s; }
        .method-card:hover { border-color:#38bdf8; background:rgba(56,189,248,0.1); }
        .method-card input { margin-right:12px; transform:scale(1.2); }
        .method-card label { font-weight:600; cursor:pointer; width:100%; display:flex; justify-content:space-between; }
        .method-details { display:none; padding:15px; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.08); border-radius:10px; margin-top:-6px; margin-bottom:15px; }
        .input-field { width:100%; padding:10px; background:#1e293b; border:1px solid rgba(255,255,255,0.15); border-radius:8px; color:white; margin-top:8px; outline:none; }
        .input-field:focus { border-color:#38bdf8; }
        .qr-box { text-align:center; padding:10px; }
        .qr-box img { width:140px; height:140px; border-radius:8px; border:2px solid rgba(255,255,255,0.2); }
        .btn-submit { width:100%; background:linear-gradient(135deg, #22c55e, #16a34a); color:white; border:none; padding:14px; border-radius:10px; font-size:16px; font-weight:600; cursor:pointer; margin-top:15px; }
        .btn-submit:hover { opacity:0.9; }
        .back-link { display:block; text-align:center; margin-top:15px; text-decoration:none; color:#38bdf8; font-size:13px; }
        .success-card { text-align:center; padding:30px 10px; }
        .success-icon { font-size:60px; color:#34d399; margin-bottom:15px; }
        .error-alert { background:rgba(244,63,94,0.2); border:1px solid #f43f5e; color:#fda4af; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-size:13px; }
    </style>
</head>
<body>

<div class="payment-container">
    <?php if ($order_success): ?>
        <div class="success-card">
            <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
            <h2>Payment Successful!</h2>
            <p style="color:#cbd5e1; margin-top:5px;">Order has been placed and live sales graph is updated.</p>
            <br>
            <a href="products.php" class="btn-submit" style="display:inline-block; width:auto; text-decoration:none; padding:10px 25px;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <h2>Choose Payment Method</h2>

        <?php if (!empty($error_msg)): ?>
            <div class="error-alert"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="summary-box">
            <div class="summary-row"><span>Total Cart Items:</span> <b><?php echo count($cart_items); ?></b></div>
            <div class="summary-row total-amount"><span>Total Payable:</span> <span>₹<?php echo number_format($total_bill, 2); ?></span></div>
        </div>

        <form method="POST" action="">
            <div class="method-card" onclick="selectMethod('gpay')">
                <input type="radio" id="gpay" name="payment_method" value="Google Pay / UPI" checked>
                <label for="gpay"><span>Google Pay / PhonePe / Paytm</span><span style="color:#38bdf8;">UPI Fast</span></label>
            </div>
            <div id="gpay_details" class="method-details" style="display:block;">
                <label style="font-size:12px; color:#94a3b8;">Enter UPI ID:</label>
                <input type="text" class="input-field" placeholder="username@okhdfcbank">
                <div class="qr-box">
                    <p style="margin:10px 0 5px 0; font-size:12px; color:#94a3b8;">Or Scan QR code to pay:</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=upi://pay?pa=store@upi%26pn=EProStore%26am=<?php echo $total_bill; ?>%26cu=INR" alt="QR">
                </div>
            </div>

            <div class="method-card" onclick="selectMethod('cod')">
                <input type="radio" id="cod" name="payment_method" value="Cash On Delivery">
                <label for="cod"><span>Cash on Delivery (COD)</span><span>Doorstep</span></label>
            </div>
            <div id="cod_details" class="method-details">
                <p style="font-size:13px; color:#94a3b8; margin:0;">Pay cash to delivery partner on arrival.</p>
            </div>

            <button type="submit" name="pay_now" class="btn-submit">Confirm & Pay ₹<?php echo number_format($total_bill, 2); ?></button>
            <a href="cart.php" class="back-link">← Back to Cart</a>
        </form>
    <?php endif; ?>
</div>

<script>
function selectMethod(type) {
    document.getElementById(type).checked = true;
    document.getElementById('gpay_details').style.display = (type === 'gpay') ? 'block' : 'none';
    document.getElementById('cod_details').style.display = (type === 'cod') ? 'block' : 'none';
}
</script>
<script src="../assets/js/script.js"></script>
<!-- index.php ke liye: <script src="assets/js/script.js"></script> -->
</body>

</html>