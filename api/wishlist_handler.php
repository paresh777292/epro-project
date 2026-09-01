<?php
/**
 * Wishlist AJAX Handler
 * Secure wishlist/reactions operations using MySQLi Prepared Statements
 * 
 * Actions:
 * - add_to_wishlist: Add product to wishlist
 * - remove_from_wishlist: Remove product from wishlist
 * - get_wishlist: Fetch user's wishlist items
 * - toggle_wishlist: Toggle wishlist status for a product
 * - is_in_wishlist: Check if product is in user's wishlist
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

// Get user ID from session or IP for guests
$user_id = $_SESSION['user_id'] ?? null;
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$action = $_REQUEST['action'] ?? 'get_wishlist';

// Response template
$response = [
    'success' => false,
    'message' => 'Unknown action',
    'items' => [],
    'count' => 0
];

try {
    switch ($action) {
        // ============ ADD TO WISHLIST ============
        case 'add_to_wishlist':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }

            // Check if product exists
            $check_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                throw new Exception('Product not found');
            }

            $product = $check_result->fetch_assoc();

            // Check if already in wishlist
            $existing_stmt = $conn->prepare(
                "SELECT id FROM product_reactions 
                 WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND user_ip = ?)) 
                 AND reaction_type = 'wishlist'"
            );
            $existing_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
            $existing_stmt->execute();
            $existing_result = $existing_stmt->get_result();

            if ($existing_result->num_rows > 0) {
                throw new Exception('Product already in wishlist');
            }

            // Insert into wishlist
            $insert_stmt = $conn->prepare(
                "INSERT INTO product_reactions (product_id, user_id, user_ip, reaction_type) 
                 VALUES (?, ?, ?, 'wishlist')"
            );
            $insert_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
            $insert_stmt->execute();

            $response['success'] = true;
            $response['message'] = $product['name'] . ' added to wishlist!';
            $check_stmt->close();
            $existing_stmt->close();
            break;

        // ============ REMOVE FROM WISHLIST ============
        case 'remove_from_wishlist':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }

            // Delete from wishlist
            $delete_stmt = $conn->prepare(
                "DELETE FROM product_reactions 
                 WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND user_ip = ?)) 
                 AND reaction_type = 'wishlist'"
            );
            $delete_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
            $delete_stmt->execute();

            if ($delete_stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Removed from wishlist';
            } else {
                throw new Exception('Item not found in wishlist');
            }
            break;

        // ============ GET WISHLIST ============
        case 'get_wishlist':
            $query = "
                SELECT 
                    p.id,
                    p.name,
                    p.price,
                    p.image,
                    p.category,
                    pr.id as reaction_id
                FROM product_reactions pr
                JOIN products p ON pr.product_id = p.id
                WHERE pr.reaction_type = 'wishlist' 
                AND (pr.user_id = ? OR (pr.user_id IS NULL AND pr.user_ip = ?))
                ORDER BY pr.created_at DESC
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $user_id, $user_ip);
            $stmt->execute();
            $result = $stmt->get_result();

            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }

            $response['success'] = true;
            $response['items'] = $items;
            $response['count'] = count($items);
            $response['message'] = 'Wishlist loaded';
            $stmt->close();
            break;

        // ============ TOGGLE WISHLIST ============
        case 'toggle_wishlist':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }

            // Check if in wishlist
            $check_stmt = $conn->prepare(
                "SELECT id FROM product_reactions 
                 WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND user_ip = ?)) 
                 AND reaction_type = 'wishlist'"
            );
            $check_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            $is_wishlisted = $check_result->num_rows > 0;

            if ($is_wishlisted) {
                // Remove from wishlist
                $delete_stmt = $conn->prepare(
                    "DELETE FROM product_reactions 
                     WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND user_ip = ?)) 
                     AND reaction_type = 'wishlist'"
                );
                $delete_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
                $delete_stmt->execute();
                $response['wishlisted'] = false;
                $response['message'] = 'Removed from wishlist';
            } else {
                // Add to wishlist
                $insert_stmt = $conn->prepare(
                    "INSERT INTO product_reactions (product_id, user_id, user_ip, reaction_type) 
                     VALUES (?, ?, ?, 'wishlist')"
                );
                $insert_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
                $insert_stmt->execute();
                $response['wishlisted'] = true;
                $response['message'] = 'Added to wishlist';
            }

            $response['success'] = true;
            $check_stmt->close();
            break;

        // ============ CHECK IF IN WISHLIST ============
        case 'is_in_wishlist':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }

            $check_stmt = $conn->prepare(
                "SELECT id FROM product_reactions 
                 WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND user_ip = ?)) 
                 AND reaction_type = 'wishlist'"
            );
            $check_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            $response['success'] = true;
            $response['is_wishlisted'] = $check_result->num_rows > 0;
            $check_stmt->close();
            break;

        // ============ MOVE TO CART ============
        case 'move_to_cart':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }

            // Check if product exists
            $check_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                throw new Exception('Product not found');
            }

            $product = $check_result->fetch_assoc();

            // Add to cart
            $cart_check = $conn->prepare(
                "SELECT id, quantity FROM cart WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND ? IS NULL))"
            );
            $cart_check->bind_param("iii", $product_id, $user_id, $user_id);
            $cart_check->execute();
            $cart_result = $cart_check->get_result();

            if ($cart_result->num_rows > 0) {
                $cart_item = $cart_result->fetch_assoc();
                $new_qty = $cart_item['quantity'] + 1;
                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_qty, $cart_item['id']);
                $update_stmt->execute();
            } else {
                $insert_stmt = $conn->prepare(
                    "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)"
                );
                $insert_stmt->bind_param("ii", $user_id, $product_id);
                $insert_stmt->execute();
            }

            // Remove from wishlist
            $delete_stmt = $conn->prepare(
                "DELETE FROM product_reactions 
                 WHERE product_id = ? AND (user_id = ? OR (user_id IS NULL AND user_ip = ?)) 
                 AND reaction_type = 'wishlist'"
            );
            $delete_stmt->bind_param("iis", $product_id, $user_id, $user_ip);
            $delete_stmt->execute();

            $response['success'] = true;
            $response['message'] = $product['name'] . ' moved to cart!';
            $check_stmt->close();
            $cart_check->close();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Wishlist Handler Error: ' . $e->getMessage());
}

// Send JSON response
echo json_encode($response);
exit;
?>
