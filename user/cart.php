<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Database Connection
if (file_exists(__DIR__ . '/../db_connect.php')) {
    include_once __DIR__ . '/../db_connect.php';
}

// 2. User Identification (Logged-in User ya Guest User)
$user_id = null;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = intval($_SESSION['id']);
}

// Agar session me user nahi hai, toh database se pehla valid user id fetch karein
if (!$user_id && isset($conn)) {
    $u_check = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if ($u_check && mysqli_num_rows($u_check) > 0) {
        $u_row = mysqli_fetch_assoc($u_check);
        $user_id = intval($u_row['id']);
    }
}
