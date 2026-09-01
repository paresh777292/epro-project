<?php
// api/apply_coupon.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Safe Database Connection Include
$db_paths = [
    __DIR__ . '/../db_connect.php',
    __DIR__ . '/../../db_connect.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../../config.php',
    __DIR__ . '/db_connect.php'
];

$conn = null;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        if (isset($conn) && $conn) break;
    }
}

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// 2. Input Parameters
$code = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
$subtotal = floatval($_POST['subtotal'] ?? $_GET['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a coupon code'
    ]);
    exit;
}

// 3. Fetch Coupon with Prepared Statement
$stmt = mysqli_prepare($conn, "SELECT * FROM coupons WHERE UPPER(TRIM(code)) = ? LIMIT 1");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database query error'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$coupon = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 4. Coupon Check
if (!$coupon) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid coupon code'
    ]);
    exit;
}

// 5. Active Status Validation (Supports 1, '1', 'active', 'ACTIVE')
$isActive = true;
if (isset($coupon['is_active'])) {
    $isActive = ($coupon['is_active'] == 1 || strtolower((string)$coupon['is_active']) === 'active');
} elseif (isset($coupon['status'])) {
    $isActive = ($coupon['status'] == 1 || strtolower((string)$coupon['status']) === 'active');
}

if (!$isActive) {
    echo json_encode([
        'success' => false,
        'message' => 'This coupon is currently inactive'
    ]);
    exit;
}

// 6. Expiry Date Check
if (!empty($coupon['expiry_date']) && $coupon['expiry_date'] !== '0000-00-00') {
    $expiry = strtotime($coupon['expiry_date']);
    if ($expiry && $expiry < strtotime(date('Y-m-d'))) {
        echo json_encode([
            'success' => false,
            'message' => 'This coupon has expired'
        ]);
        exit;
    }
}

// 7. Minimum Order / Cart Value Check
$minCart = floatval($coupon['min_cart_value'] ?? $coupon['min_order'] ?? 0);
if ($subtotal > 0 && $subtotal < $minCart) {
    echo json_encode([
        'success' => false,
        'message' => "Minimum cart value of ₹{$minCart} required for this coupon"
    ]);
    exit;
}

// 8. Discount Calculation
$discountVal = floatval($coupon['discount_value'] ?? $coupon['discount'] ?? 0);
$discountType = strtolower($coupon['discount_type'] ?? 'percentage');
$calculatedDiscount = 0;

if ($discountType === 'percentage' || $discountType === 'percent') {
    $calculatedDiscount = ($subtotal * $discountVal) / 100;
    
    // Max discount cap if present
    if (!empty($coupon['max_discount']) && floatval($coupon['max_discount']) > 0) {
        $maxDiscount = floatval($coupon['max_discount']);
        if ($calculatedDiscount > $maxDiscount) {
            $calculatedDiscount = $maxDiscount;
        }
    }
} else {
    // Fixed amount discount
    $calculatedDiscount = $discountVal;
}

// Discount cart total se zyada na ho
if ($calculatedDiscount > $subtotal && $subtotal > 0) {
    $calculatedDiscount = $subtotal;
}

// 9. Success Response
echo json_encode([
    'success' => true,
    'message' => "Coupon '{$coupon['code']}' applied successfully!",
    'discount' => round($calculatedDiscount, 2),
    'coupon' => [
        'code' => $coupon['code'],
        'discount_type' => $discountType,
        'discount_value' => $discountVal
    ]
]);
exit;