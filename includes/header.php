<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>E-PRO</title>
    <link rel="stylesheet" href="/EPRO/assets/css/style.css">
</head>
<body>

<header>
    <h1>E-PRO Ecommerce</h1>
    <nav>
        <a href="/EPRO/index.php">Home</a>
        <a href="/EPRO/user/products.php">Products</a>

        <?php if(isset($_SESSION['user'])){ ?>
            <a href="/EPRO/logout.php">Logout</a>
        <?php } else { ?>
            <a href="/EPRO/user/login.php">Login</a>
            <a href="/EPRO/user/signup.php">Signup</a>
        <?php } ?>
    </nav>
</header>
<hr>
