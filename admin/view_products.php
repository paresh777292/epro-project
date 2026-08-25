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
    die("<h3 style='color:red; text-align:center;'>Database Connection Failed!</h3>");
}

// 3. Fetch All Products
$query = "SELECT * FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$total_count = ($result) ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management | EPRO Admin</title>
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
            max-width: 1150px;
            margin: 0 auto;
        }

        /* Glassmorphism Header Bar */
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

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn-header {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-add {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45);
        }

        .btn-dash {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-dash:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* Glassmorphism Table Container */
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

        /* Product Elements */
        .prod-id {
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
        }

        .prod-img-box {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .prod-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .prod-name {
            font-weight: 600;
            color: #f1f5f9;
        }

        .category-badge {
            display: inline-block;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .prod-price {
            font-weight: 700;
            color: #34d399;
            font-size: 15px;
        }

        /* Action Buttons */
        .actions-group {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-edit-action {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        .btn-edit-action:hover {
            background: #fbbf24;
            color: #0f172a;
            transform: translateY(-2px);
        }

        .btn-del-action {
            background: rgba(244, 63, 94, 0.15);
            color: #f43f5e;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }
        .btn-del-action:hover {
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
    <!-- Header Controls -->
    <div class="header-bar">
        <div class="title-area">
            <h2><i class="fa-solid fa-boxes-stacked"></i> Products Management</h2>
            <p>Total Products in Inventory: <b><?php echo $total_count; ?></b></p>
        </div>
        <div class="header-actions">
            <a href="add_product.php" class="btn-header btn-add">
                <i class="fa-solid fa-plus"></i> Add Product
            </a>
            <a href="dashboard.php" class="btn-header btn-dash">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Products Table -->
    <div class="table-card">
        <?php if ($result && $total_count > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        // Dynamic Image Path Logic
                        $img_file = $row['image'];
                        if (strpos($img_file, '/') !== false) {
                            $img_path = "../assets/images/" . $img_file;
                        } else {
                            $folder_name = pathinfo($img_file, PATHINFO_FILENAME);
                            $img_path = "../assets/images/" . $folder_name . "/" . $img_file;
                            if (!file_exists($img_path)) {
                                $img_path = "../assets/images/products/" . $img_file;
                            }
                        }
                    ?>
                    <tr>
                        <td class="prod-id">#<?php echo $row['id']; ?></td>
                        <td>
                            <div class="prod-img-box">
                                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=No+Image';">
                            </div>
                        </td>
                        <td>
                            <div class="prod-name"><?php echo htmlspecialchars($row['name']); ?></div>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($row['category'] ?? 'General'); ?>
                            </span>
                        </td>
                        <td class="prod-price">₹<?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <div class="actions-group">
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="action-btn btn-edit-action">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="action-btn btn-del-action" onclick="return confirm('Kya aap sach me ye product delete karna chahte hain?');">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3>No Products Found</h3>
                <p>Aapke store mein abhi tak koi product add nahi kiya gaya hai.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>