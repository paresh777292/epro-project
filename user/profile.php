<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../db_connect.php");

// Check if user is actually logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch full user details from database using user_id (SECURE - prepared statement)
// Note: phone column doesn't exist yet in users table
$user_id = $_SESSION['user_id'];
$query_stmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
$query_stmt->bind_param("i", $user_id);
$query_stmt->execute();
$query_result = $query_stmt->get_result();

if ($query_result->num_rows === 0) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $query_result->fetch_assoc();
$query_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - E-PRO Store</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .profile-card {
            background: white;
            width: 400px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }

        /* Large Google-style Circle */
        .big-avatar {
            width: 100px;
            height: 100px;
            background-color: #0b57d0;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            margin: 0 auto 20px;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(11, 87, 208, 0.3);
        }

        h2 { margin: 10px 0; color: #333; }
        p { color: #666; margin-bottom: 25px; }

        .info-box {
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info-item {
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .label { font-size: 12px; color: #888; display: block; }
        .value { font-size: 16px; color: #333; font-weight: 500; }

        .btn-group { display: flex; gap: 10px; }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-home { background: #007bff; color: white; }
        .btn-home:hover { background: #0056b3; }

        .btn-logout { background: #fee; color: #d93025; border: 1px solid #fad2cf; }
        .btn-logout:hover { background: #fce8e6; }
    </style>
</head>
<body>

<div class="profile-card">
    <div class="big-avatar">
        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
    </div>

    <h2>Account Details</h2>
    <p>Manage your E-PRO information</p>

    <div class="info-box">
        <div class="info-item">
            <span class="label">FULL NAME</span>
            <span class="value"><?php echo htmlspecialchars($user['name']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">EMAIL ADDRESS</span>
            <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
        </div>
    </div>

    <div class="btn-group">
        <a href="../index.php" class="btn btn-home">Back to Shop</a>
        <a href="logout.php" class="btn btn-logout">Sign Out</a>
    </div>
</div>

</body>
</html>