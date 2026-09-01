<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Authentication Check
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// 2. Database Connection
if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
}

if (!isset($conn) || !$conn) {
    die("Database Connection Failed.");
}

// 3. Validate and Delete Product safely
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// 4. Redirect back to view products
header("Location: view_products.php");
exit();
?>
