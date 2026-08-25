<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Auth Check
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// 2. Database Connection (Live Stats ke liye)
$total_products = 0;
$total_users = 0;

if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
}

if (isset($conn) && $conn) {
    $p_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
    if ($p_res) {
        $total_products = mysqli_fetch_assoc($p_res)['total'];
    }
    $u_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
    if ($u_res) {
        $total_users = mysqli_fetch_assoc($u_res)['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EPRO</title>
    <!-- Google Font & Icons -->
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
            padding: 30px 20px;
        }

        .dashboard-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* Top Header */
        .top-nav {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .brand h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-badge {
            background: #3b82f6;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Stats Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-info h3 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-info p {
            color: #94a3b8;
            font-size: 13px;
        }

        .stat-icon {
            font-size: 28px;
            padding: 14px;
            border-radius: 12px;
        }

        .icon-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .icon-green { background: rgba(52, 211, 153, 0.15); color: #34d399; }
        .icon-purple { background: rgba(167, 139, 250, 0.15); color: #a78bfa; }

        /* Action Buttons / Navigation Grid */
        .section-title {
            font-size: 17px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
        }

        .menu-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 22px 18px;
            text-decoration: none;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-6px);
            background: rgba(51, 65, 85, 0.8);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.35);
        }

        .menu-card i {
            font-size: 28px;
            margin-bottom: 12px;
            transition: transform 0.3s ease;
        }

        .menu-card:hover i {
            transform: scale(1.15);
        }

        .menu-card span {
            font-size: 14px;
            font-weight: 600;
        }

        /* Distinct Card Colors */
        .card-add i { color: #38bdf8; }
        .card-view i { color: #34d399; }
        .card-edit i { color: #fbbf24; }
        .card-users i { color: #a78bfa; }
        .card-graph i { color: #f472b6; }
        .card-store i { color: #60a5fa; }
        
        .card-logout {
            background: rgba(225, 29, 72, 0.12);
            border-color: rgba(225, 29, 72, 0.3);
        }
        .card-logout i { color: #f43f5e; }
        .card-logout:hover {
            background: rgba(225, 29, 72, 0.25);
            border-color: #f43f5e;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Top Bar -->
    <div class="top-nav">
        <div class="brand">
            <h2><i class="fa-solid fa-bolt-lightning"></i> EPRO Admin Panel</h2>
        </div>
        <div class="admin-profile">
            <div class="avatar-badge">
                <?php echo strtoupper(substr($_SESSION['admin'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars(ucfirst($_SESSION['admin'])); ?></div>
                <div style="font-size: 11px; color: #34d399;"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Active Now</div>
            </div>
        </div>
    </div>

    <!-- Quick Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
            <div class="stat-icon icon-blue">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo $total_users; ?></h3>
                <p>Registered Users</p>
            </div>
            <div class="stat-icon icon-green">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Active</h3>
                <p>System Status</p>
            </div>
            <div class="stat-icon icon-purple">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>

    <!-- Actions & Menu Navigation -->
    <div class="section-title">
        <i class="fa-solid fa-compass"></i> Management Controls
    </div>

    <div class="menu-grid">
        <a href="add_product.php" class="menu-card card-add">
            <i class="fa-solid fa-circle-plus"></i>
            <span>Add Product</span>
        </a>

        <a href="view_products.php" class="menu-card card-view">
            <i class="fa-solid fa-list-check"></i>
            <span>View Products</span>
        </a>

        <a href="edit_product.php" class="menu-card card-edit">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>Edit Product</span>
        </a>

        <a href="view_users.php" class="menu-card card-users">
            <i class="fa-solid fa-users-gear"></i>
            <span>View Users</span>
        </a>

        <a href="sales_graph.php" class="menu-card card-graph">
            <i class="fa-solid fa-chart-line"></i>
            <span>Sales Graph</span>
        </a>

        <a href="../index.php" class="menu-card card-store">
            <i class="fa-solid fa-store"></i>
            <span>Live Store</span>
        </a>

        <a href="admin_login.php" class="menu-card card-logout" onclick="return confirm('Do you really want to logout?');">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

</body>
</html>