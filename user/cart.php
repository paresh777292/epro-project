<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
require_once __DIR__ . '/../db_connect.php';

// User Identification (Guest ya Logged in)
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// Agar user login nahi hai toh DB se default user le lo (bina login maange cart dikhane ke liye)
if (!$user_id && isset($conn)) {
    $u_check = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if ($u_check && mysqli_num_rows($u_check) > 0) {
        $u_row = mysqli_fetch_assoc($u_check);
        $user_id = intval($u_row['id']);
    }
}

// Agar session me user nahi hai, toh database se pehla valid user id fetch karein
if (!$user_id && isset($conn)) {
    $u_check = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if ($u_check && mysqli_num_rows($u_check) > 0) {
        $u_row = mysqli_fetch_assoc($u_check);
        $user_id = intval($u_row['id']);
    }
}
