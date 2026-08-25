<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Login Check
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
    die("<h3 style='color:#f43f5e; text-align:center;'>Database Connection Failed!</h3>");
}

// 3. Delete User Action
$msg = "";
$msg_type = "";
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    if ($del_id > 0) {
        $del_query = "DELETE FROM users WHERE id = '$del_id'";
        if (mysqli_query($conn, $del_query)) {
            $msg = "User record successfully delete ho gaya!";
            $msg_type = "success";
        } else {
            $msg = "Delete Error: " . mysqli_error($conn);
            $msg_type = "error";
        }
    }
}

// 4. Fetch All Users
$query = "SELECT * FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$total_users = ($result) ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | EPRO Admin</title>
    <!-- Google Font & Font Awesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: #f8fafc;
            padding: 35px 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* Glassmorphism Header */
        .header-bar {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .title-area h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .title-area p {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 2px;
        }

        .btn-dash {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-dash:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateY(-2px);
        }

        /* Alerts */
        .alert-box {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }
        .alert-error {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #f43f5e;
        }

        /* Glassmorphism Table Card */
        .table-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: rgba(15, 23, 42, 0.7);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            padding: 16px 20px;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .user-id {
            color: #64748b;
            font-weight: 600;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .user-name {
            font-weight: 600;
            color: #f1f5f9;
        }

        .user-email {
            color: #cbd5e1;
        }

        .user-mobile {
            color: #94a3b8;
        }

        .btn-delete {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(244, 63, 94, 0.15);
            color: #f43f5e;
            border: 1px solid rgba(244, 63, 94, 0.3);
            transition: all 0.2s ease;
        }
        .btn-delete:hover {
            background: #f43f5e;
            color: white;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header-bar">
        <div class="title-area">
            <h2><i class="fa-solid fa-users-gear"></i> Users Management</h2>
            <p>Total Registered Customers: <b><?php echo $total_users; ?></b></p>
        </div>
        <a href="dashboard.php" class="btn-dash">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($msg)): ?>
        <div class="alert-box <?php echo ($msg_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
            <i class="fa-solid <?php echo ($msg_type === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <!-- Table Container -->
    <div class="table-card">
        <?php if ($result && $total_users > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>User Details</th>
                        <th>Email Address</th>
                        <th>Contact No.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        $u_name = $row['name'] ?? $row['username'] ?? $row['full_name'] ?? 'User';
                        $u_email = $row['email'] ?? 'N/A';
                        $u_phone = $row['mobile'] ?? $row['phone'] ?? $row['contact'] ?? '-';
                        $first_letter = strtoupper(substr($u_name, 0, 1));
                    ?>
                    <tr>
                        <td class="user-id">#<?php echo $row['id']; ?></td>
                        <td>
                            <div class="user-meta">
                                <div class="user-avatar"><?php echo $first_letter; ?></div>
                                <div class="user-name"><?php echo htmlspecialchars($u_name); ?></div>
                            </div>
                        </td>
                        <td class="user-email"><?php echo htmlspecialchars($u_email); ?></td>
                        <td class="user-mobile"><?php echo htmlspecialchars($u_phone); ?></td>
                        <td>
                            <a href="view_users.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Kya aap sach me is user account ko remove karna chahte hain?');">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-user-slash"></i>
                <h3>No Registered Users</h3>
                <p>Abhi tak store par kisi user ne register nahi kiya hai.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>