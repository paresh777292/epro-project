<?php
session_start();
include("../config.php");

// User ID check (Jo cart.php mein use kiya hai wahi yahan hona chahiye)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

// Query jo sirf current user ka cart khali karegi
$query = "DELETE FROM cart WHERE user_id = '$user_id'";

if(mysqli_query($conn, $query)){
    // Clear hone ke baad wapas cart.php par bhej dein
    header("Location: cart.php?message=success");
    exit();
} else {
    // Agar koi error aata hai toh display karein
    echo "Error clearing cart: " . mysqli_error($conn);
}
?>