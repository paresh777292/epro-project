<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Agar Admin pehle se login hai toh Dashboard bhejo
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. Database Connection Include
if (file_exists(__DIR__ . '/../db_connect.php')) {
    include __DIR__ . '/../db_connect.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    include __DIR__ . '/../config.php';
}

$error = "";

// 3. Login Check Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        if (isset($conn) && $conn) {
            $username_clean = mysqli_real_escape_string($conn, $username);
            $query = "SELECT * FROM admin WHERE username = '$username_clean' LIMIT 1";
            $res = mysqli_query($conn, $query);

            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                
                // Plain Text ya Hash Password Match
                if ($password === $row['password'] || password_verify($password, $row['password'])) {
                    $_SESSION['admin'] = $row['username'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Galat Password!";
                }
            } else {
                // Fallback default login
                if ($username === 'admin' && $password === 'admin123') {
                    $_SESSION['admin'] = 'admin';
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Admin Username nahi mila!";
                }
            }
        } else {
            // Database disconnected fallback
            if ($username === 'admin' && $password === 'admin123') {
                $_SESSION['admin'] = 'admin';
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Database connect nahi hai!";
            }
        }
    } else {
        $error = "Kripya Username aur Password dono bharein!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - E-PRO</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-card { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            width: 320px; 
            text-align: center; 
        }
        h2 { margin-bottom: 20px; color: #333; }
        .input-group { margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; font-size: 14px; margin-bottom: 5px; color: #555; }
        .input-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px; 
        }
        .btn-login { 
            width: 100%; 
            padding: 11px; 
            background: #007bff; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 10px; 
        }
        .btn-login:hover { background: #0056b3; }
        .error-msg { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 8px; 
            border-radius: 5px; 
            margin-bottom: 15px; 
            font-size: 13px; 
        }
        .back-link { display: block; margin-top: 15px; text-decoration: none; color: #666; font-size: 13px; }
        .back-link:hover { color: #007bff; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Admin Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Action hamesha current file par rahega -->
    <form method="POST" action="admin_login.php">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>

        <button type="submit" name="login" class="btn-login">Login to Panel</button>
    </form>

    <a href="../index.php" class="back-link">← Back to Store</a>
</div>

</body>
</html>