<?php
/**
 * Product Reviews AJAX Handler
 * Manage product reviews and ratings using MySQLi Prepared Statements
 * 
 * Actions:
 * - get_reviews: Fetch all reviews for a product
 * - submit_review: Submit a new review
 * - get_average_rating: Get average rating for a product
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'get_reviews';
$product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;

$response = [
    'success' => false,
    'message' => 'Unknown action',
    'data' => null
];

try {
    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    // Verify product exists
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        throw new Exception('Product not found');
    }
    $check_stmt->close();

    switch ($action) {
        // ============ GET REVIEWS ============
        case 'get_reviews':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            if ($limit < 1 || $limit > 100) $limit = 10;
            if ($offset < 0) $offset = 0;

            $query = "
                SELECT 
                    id,
                    user_name,
                    rating,
                    review_text,
                    created_at
                FROM product_reviews
                WHERE product_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $product_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $reviews = [];
            while ($row = $result->fetch_assoc()) {
                $reviews[] = [
                    'id' => intval($row['id']),
                    'user_name' => htmlspecialchars($row['user_name']),
                    'rating' => intval($row['rating']),
                    'review_text' => htmlspecialchars($row['review_text']),
                    'created_at' => $row['created_at']
                ];
            }

            $response['success'] = true;
            $response['data'] = $reviews;
            $response['count'] = count($reviews);
            $stmt->close();
            break;

        // ============ GET AVERAGE RATING ============
        case 'get_average_rating':
            $query = "
                SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating
                FROM product_reviews
                WHERE product_id = ?
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $total_reviews = intval($row['total_reviews']);
            $average_rating = $total_reviews > 0 ? round(floatval($row['average_rating']), 1) : 0;

            $response['success'] = true;
            $response['data'] = [
                'total_reviews' => $total_reviews,
                'average_rating' => $average_rating,
                'product_id' => $product_id
            ];
            $stmt->close();
            break;

        // ============ SUBMIT REVIEW ============
        case 'submit_review':
            $user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

            // Validation
            if (empty($user_name) || strlen($user_name) < 2) {
                throw new Exception('Name must be at least 2 characters');
            }

            if ($rating < 1 || $rating > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }

            if (empty($review_text) || strlen($review_text) < 10) {
                throw new Exception('Review must be at least 10 characters');
            }

            // Email validation (optional)
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Check for duplicate reviews from same user/email (optional rate limiting)
            if (!empty($email)) {
                $dup_check = $conn->prepare(
                    "SELECT id FROM product_reviews 
                     WHERE product_id = ? AND email = ? 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     LIMIT 1"
                );
                $dup_check->bind_param("is", $product_id, $email);
                $dup_check->execute();
                $dup_result = $dup_check->get_result();

                if ($dup_result->num_rows > 0) {
                    throw new Exception('You have already submitted a review in the last 24 hours');
                }
                $dup_check->close();
            }

            // Insert review
            $insert_query = "
                INSERT INTO product_reviews 
                (product_id, user_name, email, rating, review_text)
                VALUES (?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param(
                "issss",
                $product_id,
                $user_name,
                $email,
                $rating,
                $review_text
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to submit review: ' . $stmt->error);
            }

            $response['success'] = true;
            $response['message'] = 'Review submitted successfully!';
            $response['data'] = [
                'review_id' => $stmt->insert_id,
                'user_name' => htmlspecialchars($user_name),
                'rating' => $rating,
                'review_text' => htmlspecialchars($review_text)
            ];
            $stmt->close();
            break;

        // ============ GET RATING DISTRIBUTION ============
        case 'get_rating_distribution':
            $query = "
                SELECT 
                    rating,
                    COUNT(*) as count
                FROM product_reviews
                WHERE product_id = ?
                GROUP BY rating
                ORDER BY rating DESC
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            $total_reviews = 0;

            while ($row = $result->fetch_assoc()) {
                $rating = intval($row['rating']);
                $count = intval($row['count']);
                $distribution[$rating] = $count;
                $total_reviews += $count;
            }

            $response['success'] = true;
            $response['data'] = [
                'distribution' => $distribution,
                'total_reviews' => $total_reviews
            ];
            $stmt->close();
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Reviews Handler Error: ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>
