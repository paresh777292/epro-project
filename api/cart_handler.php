<?php
/**
 * Cart AJAX Handler
 * Secure cart operations using MySQLi Prepared Statements
 * 
 * Actions:
 * - add_item: Add product to cart
 * - get_cart: Fetch all cart items for user/guest
 * - update_quantity: Increase/decrease item quantity
 * - remove_item: Delete item from cart
 * - clear_cart: Empty entire cart
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

// Get user ID from session or null for guests
$user_id = $_SESSION['user_id'] ?? null;
$action = $_REQUEST['action'] ?? 'get_cart';

// Response template
$response = [
    'success' => false,
    'message' => 'Unknown action',
    'items' => [],
    'subtotal' => 0
];

try {
    switch ($action) {
        // ============ ADD ITEM TO CART ============
        case 'add_item':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product or quantity');
            }

            // Check if product exists
            $check_stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $product_result = $check_stmt->get_result();

            if ($product_result->num_rows === 0) {
                throw new Exception('Product not found');
            }

            $product = $product_result->fetch_assoc();

            // Check if item already in cart
            $cart_check = $conn->prepare(
                "SELECT id, quantity FROM cart WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND ? IS NULL))"
            );
            $cart_check->bind_param("iii", $product_id, $user_id, $user_id);
            $cart_check->execute();
            $cart_result = $cart_check->get_result();

            if ($cart_result->num_rows > 0) {
                // Update existing cart item
                $cart_item = $cart_result->fetch_assoc();
                $new_qty = $cart_item['quantity'] + $quantity;

                $update_stmt = $conn->prepare(
                    "UPDATE cart SET quantity = ? WHERE id = ?"
                );
                $update_stmt->bind_param("ii", $new_qty, $cart_item['id']);
                $update_stmt->execute();

                $response['message'] = 'Product quantity updated';
            } else {
                // Insert new cart item
                $insert_stmt = $conn->prepare(
                    "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)"
                );
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $insert_stmt->execute();

                $response['message'] = $product['name'] . ' added to cart!';
            }

            $response['success'] = true;
            $check_stmt->close();
            $cart_check->close();
            break;

        // ============ GET CART ITEMS ============
        case 'get_cart':
            $query = "
                SELECT 
                    c.id as cart_id, 
                    c.quantity, 
                    p.id as product_id,
                    p.name, 
                    p.price, 
                    p.image 
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id " . ($user_id ? "= ?" : "IS NULL") . "
                ORDER BY c.added_at DESC
            ";

            $stmt = $conn->prepare($query);
            if ($user_id) {
                $stmt->bind_param("i", $user_id);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $items = [];
            $subtotal = 0;

            while ($row = $result->fetch_assoc()) {
                $item_total = $row['price'] * $row['quantity'];
                $subtotal += $item_total;
                $row['item_total'] = $item_total;
                $items[] = $row;
            }

            $response['success'] = true;
            $response['items'] = $items;
            $response['subtotal'] = $subtotal;
            $response['message'] = 'Cart loaded successfully';
            $stmt->close();
            break;

        // ============ UPDATE QUANTITY ============
        case 'update_quantity':
            $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
            $change = isset($_POST['change']) ? intval($_POST['change']) : 0;

            if ($cart_id <= 0 || abs($change) !== 1) {
                throw new Exception('Invalid cart ID or change value');
            }

            // Verify cart item belongs to user
            $verify_stmt = $conn->prepare(
                "SELECT quantity FROM cart WHERE id = ? AND (user_id = ? OR (user_id IS NULL AND ? IS NULL))"
            );
            $verify_stmt->bind_param("iii", $cart_id, $user_id, $user_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();

            if ($verify_result->num_rows === 0) {
                throw new Exception('Cart item not found');
            }

            $cart_item = $verify_result->fetch_assoc();
            $new_qty = $cart_item['quantity'] + $change;

            if ($new_qty < 1) {
                throw new Exception('Quantity cannot be less than 1');
            }

            // Update quantity
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_qty, $cart_id);
            $update_stmt->execute();

            $response['success'] = true;
            $response['message'] = 'Quantity updated successfully';
            $verify_stmt->close();
            break;

        // ============ REMOVE ITEM ============
        case 'remove_item':
            $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;

            if ($cart_id <= 0) {
                throw new Exception('Invalid cart ID');
            }

            // Verify cart item belongs to user
            $verify_stmt = $conn->prepare(
                "SELECT id FROM cart WHERE id = ? AND (user_id = ? OR (user_id IS NULL AND ? IS NULL))"
            );
            $verify_stmt->bind_param("iii", $cart_id, $user_id, $user_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();

            if ($verify_result->num_rows === 0) {
                throw new Exception('Cart item not found');
            }

            // Delete cart item
            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
            $delete_stmt->bind_param("i", $cart_id);
            $delete_stmt->execute();

            $response['success'] = true;
            $response['message'] = 'Item removed successfully';
            $verify_stmt->close();
            break;

        // ============ CLEAR ENTIRE CART ============
        case 'clear_cart':
            $delete_stmt = $conn->prepare(
                "DELETE FROM cart WHERE user_id " . ($user_id ? "= ?" : "IS NULL")
            );
            
            if ($user_id) {
                $delete_stmt->bind_param("i", $user_id);
            }
            $delete_stmt->execute();

            $response['success'] = true;
            $response['message'] = 'Cart cleared successfully';
            $delete_stmt->close();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Cart Handler Error: ' . $e->getMessage());
}

// Send JSON response
echo json_encode($response);
exit;
?>
