<?php
// api/search_suggest.php
header('Content-Type: application/json; charset=utf-8');

// 1. Safe Database Connection Include (Multiple levels check)
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

// Agar DB connect na ho toh clean JSON return karein (Bina 400 throw kiye)
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'results' => []
    ]);
    exit;
}

// 2. Parameters capture karein (q, query, search sabhi ko support karega)
$q = trim($_GET['q'] ?? $_GET['query'] ?? $_GET['search'] ?? '');
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
if ($limit <= 0 || $limit > 20) {
    $limit = 8;
}

// Agar query khali ho toh empty array return karein
if ($q === '') {
    echo json_encode([]);
    exit;
}

// 3. MySQLi Prepared Statement (SQL Injection Safe)
$searchTerm = "%" . $q . "%";

// Products table search (name & category)
$sql = "SELECT id, name, price, category, image 
        FROM products 
        WHERE name LIKE ? OR category LIKE ? 
        LIMIT ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    // Agar query prepare fail ho
    echo json_encode([]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ssi", $searchTerm, $searchTerm, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = [
        'id'       => (int)$row['id'],
        'name'     => $row['name'] ?? 'Unnamed Product',
        'price'    => (float)$row['price'],
        'category' => $row['category'] ?? '',
        'image'    => $row['image'] ?? 'assets/images/placeholder.jpg'
    ];
}

mysqli_stmt_close($stmt);

// 4. Return Final JSON
echo json_encode($products);
exit;