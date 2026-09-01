<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } else {
            // Use prepared statement for secure query (SECURE)
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
            
            if (!$stmt) {
                $error = "Database error. Please try again.";
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res && $res->num_rows > 0) {
                    $user = $res->fetch_assoc();
                    if ($password === $user['password']) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        header("Location: products.php");
                        exit();
                    } else {
                        $error = "wrong Password!";
                    }
                } else {
                    $error = "this is email account is not registered!";
                }
                $stmt->close();
            }
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | E-PRO</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body {
            background-color: #0b0f19;
            background-image: radial-gradient(circle at 80% 20%, rgba(56, 189, 248, 0.18) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f8fafc;
        }
        .card {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 35px 30px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        h2 { text-align: center; margin-bottom: 20px; color: #38bdf8; }
        .input-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; color: #94a3b8; }
        input {
            width: 100%;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            outline: none;
        }
        input:focus { border-color: #38bdf8; }
        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
        }
        .error { background: rgba(244, 63, 94, 0.2); color: #f43f5e; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .link { text-align: center; margin-top: 15px; font-size: 13px; color: #94a3b8; }
        .link a { color: #38bdf8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <h2>User Login</h2>
    <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" action="login.php">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="abc@gmail.com" required>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" name="login" class="btn">Login</button>
    </form>
    <div class="link">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </div>
</div>

</body>
</html>