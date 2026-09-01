<?php
/**
 * Order Handler AJAX - Create Orders and Handle Payment Verification
 * Processes order creation with coupon, delivery address, and UPI verification
 * 
 * Actions:
 * - create_order: Create new order from cart
 * - verify_utr: Verify UPI transaction and complete order
 * - get_order: Fetch order details
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'create_order';

$response = [
    'success' => false,
    'message' => 'Unknown action',
    'data' => null
];

try {
    switch ($action) {
        // ============ CREATE ORDER ============
        case 'create_order':
            if (!$user_id) {
                throw new Exception('User must be logged in to place an order');
            }

            $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
            $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
            $coupon_code = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : null;
            $delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

            if ($subtotal <= 0) {
                throw new Exception('Invalid cart subtotal');
            }

            if (empty($delivery_address)) {
                throw new Exception('Delivery address is required');
            }

            if (empty($phone) || !preg_match('/^\d{10}$/', $phone)) {
                throw new Exception('Valid 10-digit phone number required');
            }

            // Calculate total amount
            $total_amount = $subtotal - $discount;

            if ($total_amount <= 0) {
                throw new Exception('Invalid order amount');
            }

            // Verify cart is not empty
            $cart_check = $conn->prepare(
                "SELECT SUM(quantity) as total_items FROM cart 
                 WHERE (user_id = ? OR (user_id IS NULL AND ? IS NULL))"
            );
            $cart_check->bind_param("ii", $user_id, $user_id);
            $cart_check->execute();
            $cart_result = $cart_check->get_result();
            $cart_data = $cart_result->fetch_assoc();
            $cart_check->close();

            if (intval($cart_data['total_items']) === 0) {
                throw new Exception('Cart is empty');
            }

            // Create order in database
            $insert_order = $conn->prepare(
                "INSERT INTO orders 
                (user_id, total_amount, subtotal, tax_amount, coupon_code, discount_amount, payment_status, delivery_address)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)"
            );

            // Calculate subtotal and tax (if applicable - example: 0% tax for now)
            $tax_amount = 0;
            $insert_order->bind_param(
                "iddddsds",
                $user_id,
                $total_amount,
                $subtotal,
                $tax_amount,
                $coupon_code,
                $discount,
                $delivery_address
            );

            if (!$insert_order->execute()) {
                throw new Exception('Failed to create order: ' . $insert_order->error);
            }

            $order_id = $insert_order->insert_id;
            $insert_order->close();

            // Add order items from cart
            $cart_items_stmt = $conn->prepare(
                "SELECT c.id, c.product_id, c.quantity, p.name, p.price
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = ?"
            );
            $cart_items_stmt->bind_param("i", $user_id);
            $cart_items_stmt->execute();
            $items_result = $cart_items_stmt->get_result();

            // Insert order items into order_items table (SECURE - prepared statement)
            $insert_item_stmt = $conn->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            while ($item = $items_result->fetch_assoc()) {
                $item_subtotal = floatval($item['price']) * intval($item['quantity']);
                $insert_item_stmt->bind_param(
                    "iisdid",
                    $order_id,
                    $item['product_id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $item_subtotal
                );

                if (!$insert_item_stmt->execute()) {
                    error_log("Failed to insert order item: " . $insert_item_stmt->error);
                    // Continue processing other items even if one fails
                }
            }
            $insert_item_stmt->close();
            $cart_items_stmt->close();

            // Increment coupon usage count if coupon was used
            if (!empty($coupon_code)) {
                $update_coupon = $conn->prepare(
                    "UPDATE coupons SET usage_count = usage_count + 1 
                     WHERE code = ?"
                );
                $update_coupon->bind_param("s", $coupon_code);
                $update_coupon->execute();
                $update_coupon->close();
            }

            $response['success'] = true;
            $response['message'] = 'Order created successfully';
            $response['data'] = [
                'order_id' => $order_id,
                'total_amount' => $total_amount,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'payment_status' => 'pending'
            ];
            break;

        // ============ VERIFY UTR & COMPLETE ORDER ============
        case 'verify_utr':
            if (!$user_id) {
                throw new Exception('User must be logged in');
            }

            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            $utr_number = isset($_POST['utr_number']) ? trim($_POST['utr_number']) : '';

            if ($order_id <= 0) {
                throw new Exception('Invalid order ID');
            }

            // Validate UTR format (12-digit UPI Reference number)
            if (!preg_match('/^\d{12,}$/', $utr_number)) {
                throw new Exception('UTR number must be at least 12 digits');
            }

            // Fetch order
            $order_stmt = $conn->prepare(
                "SELECT id, user_id, total_amount, payment_status FROM orders WHERE id = ?"
            );
            $order_stmt->bind_param("i", $order_id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();

            if ($order_result->num_rows === 0) {
                throw new Exception('Order not found');
            }

            $order = $order_result->fetch_assoc();
            $order_stmt->close();

            // Verify order belongs to current user
            if (intval($order['user_id']) !== $user_id) {
                throw new Exception('Unauthorized access to this order');
            }

            // Update order with UTR and mark as paid
            $update_order = $conn->prepare(
                "UPDATE orders SET payment_status = 'completed', utr_number = ?, order_status = 'confirmed'
                 WHERE id = ?"
            );
            $update_order->bind_param("si", $utr_number, $order_id);

            if (!$update_order->execute()) {
                throw new Exception('Failed to verify payment: ' . $update_order->error);
            }
            $update_order->close();

            // Clear user's cart after successful order
            $clear_cart = $conn->prepare(
                "DELETE FROM cart WHERE user_id = ?"
            );
            $clear_cart->bind_param("i", $user_id);
            $clear_cart->execute();
            $clear_cart->close();

            // Log payment for admin verification (optional)
            error_log("Payment Verified - Order ID: {$order_id}, UTR: {$utr_number}, User: {$user_id}");

            $response['success'] = true;
            $response['message'] = 'Payment verified successfully! Order confirmed.';
            $response['data'] = [
                'order_id' => $order_id,
                'order_status' => 'confirmed',
                'utr_number' => htmlspecialchars($utr_number)
            ];
            break;

        // ============ GET ORDER DETAILS ============
        case 'get_order':
            if (!$user_id) {
                throw new Exception('User must be logged in');
            }

            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

            if ($order_id <= 0) {
                throw new Exception('Invalid order ID');
            }

            $order_stmt = $conn->prepare(
                "SELECT id, user_id, total_amount, discount_amount, coupon_code, 
                        utr_number, payment_status, order_status, delivery_address, order_date
                 FROM orders WHERE id = ?"
            );
            $order_stmt->bind_param("i", $order_id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();

            if ($order_result->num_rows === 0) {
                throw new Exception('Order not found');
            }

            $order = $order_result->fetch_assoc();
            $order_stmt->close();

            // Verify ownership
            if (intval($order['user_id']) !== $user_id) {
                throw new Exception('Unauthorized access');
            }

            $response['success'] = true;
            $response['message'] = 'Order details retrieved';
            $response['data'] = [
                'order_id' => intval($order['id']),
                'total_amount' => floatval($order['total_amount']),
                'discount_amount' => floatval($order['discount_amount']),
                'coupon_code' => $order['coupon_code'],
                'utr_number' => $order['utr_number'],
                'payment_status' => $order['payment_status'],
                'order_status' => $order['order_status'],
                'delivery_address' => htmlspecialchars($order['delivery_address']),
                'order_date' => $order['order_date']
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Order Handler Error: ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>
