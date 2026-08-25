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
    die("<h3 style='color:#f43f5e; text-align:center;'>Database Connection Failed!</h3>");
}

$msg = "";
$msg_type = "";

// 3. Update Product Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $p_id = intval($_POST['product_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $image_path_custom = mysqli_real_escape_string($conn, trim($_POST['image']));

    $update_query = "UPDATE products SET name='$name', price='$price', category='$category', image='$image_path_custom' WHERE id='$p_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $msg = "Product successfully update ho gaya!";
        $msg_type = "success";
    } else {
        $msg = "Update Error: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// 4. Product Selection
$selected_id = 0;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $selected_id = intval($_GET['id']);
} elseif (isset($_POST['product_select']) && !empty($_POST['product_select'])) {
    $selected_id = intval($_POST['product_select']);
}

// Fetch all products for selection dropdown
$all_prods = mysqli_query($conn, "SELECT id, name FROM products ORDER BY id DESC");

// Fetch selected product details
$product = null;
if ($selected_id > 0) {
    $fetch_res = mysqli_query($conn, "SELECT * FROM products WHERE id='$selected_id' LIMIT 1");
    if ($fetch_res && mysqli_num_rows($fetch_res) > 0) {
        $product = mysqli_fetch_assoc($fetch_res);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | EPRO Admin</title>
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* Ambient Glow Gradient Background */
        body {
            background-color: #0b0f19;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(129, 140, 248, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 50% 90%, rgba(236, 72, 153, 0.14) 0%, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #f8fafc;
            padding: 35px 20px;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
        }

        /* Frosted Glass Header */
        .header-bar {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .title-area h2 {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-dash {
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-dash:hover {
            background: rgba(255, 255, 255, 0.16);
            color: white;
            transform: translateY(-2px);
        }

        /* Alert Notifications */
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

        /* Main Form Card */
        .form-card {
            background: rgba(30, 41, 59, 0.55);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
        }

        /* Dropdown Product Selection Area */
        .select-group {
            background: rgba(15, 23, 42, 0.75);
            padding: 18px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 25px;
        }

        .select-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .dropdown-flex {
            display: flex;
            gap: 10px;
        }

        select {
            flex: 1;
            padding: 11px 14px;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
        }

        select option {
            background: #1e293b;
            color: #f8fafc;
        }

        .btn-load {
            padding: 11px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-load:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* Input Controls */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus, select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.25);
        }

        .image-preview-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            background: rgba(15, 23, 42, 0.5);
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .preview-box {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            overflow: hidden;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-text {
            font-size: 12px;
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45);
        }

        .footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 22px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .footer-links a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header-bar">
        <div class="title-area">
            <h2><i class="fa-solid fa-pen-to-square"></i> Edit Product</h2>
        </div>
        <a href="view_products.php" class="btn-dash">
            <i class="fa-solid fa-list-check"></i> Products List
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($msg)): ?>
        <div class="alert-box <?php echo ($msg_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
            <i class="fa-solid <?php echo ($msg_type === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <!-- Choose Product Dropdown -->
        <div class="select-group">
            <form method="GET" action="edit_product.php">
                <label><i class="fa-solid fa-hand-pointer"></i> Choose Product to Edit:</label>
                <div class="dropdown-flex">
                    <select name="id" required>
                        <option value="">-- Select a Product --</option>
                        <?php while ($p = mysqli_fetch_assoc($all_prods)): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($selected_id == $p['id']) ? 'selected' : ''; ?>>
                                #<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-load">Load</button>
                </div>
            </form>
        </div>

        <!-- Edit Form -->
        <?php if ($product): 
            $img_file = $product['image'];
            if (strpos($img_file, '/') !== false) {
                $initial_img = "../assets/images/" . $img_file;
            } else {
                $folder_name = pathinfo($img_file, PATHINFO_FILENAME);
                $initial_img = "../assets/images/" . $folder_name . "/" . $img_file;
                if (!file_exists($initial_img)) {
                    $initial_img = "../assets/images/products/" . $img_file;
                }
            }
        ?>
            <form method="POST" action="edit_product.php">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>" placeholder="e.g. clothing, shoes, electronics" required>
                </div>

                <div class="form-group">
                    <label>Image File Path (under assets/images/)</label>
                    <input type="text" id="imgInput" name="image" value="<?php echo htmlspecialchars($product['image']); ?>" placeholder="e.g. product4/grey.png" required>
                    
                    <div class="image-preview-wrapper">
                        <div class="preview-box">
                            <img id="imgPreview" src="<?php echo $initial_img; ?>" alt="Product Preview" onerror="this.src='https://placehold.co/50x50?text=No+Img';">
                        </div>
                        <div class="preview-text">Live Thumbnail Preview</div>
                    </div>
                </div>

                <button type="submit" name="update_product" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; color: #94a3b8; padding: 25px 0;">
                <i class="fa-solid fa-arrow-up" style="font-size: 24px; margin-bottom: 10px; color: #38bdf8;"></i>
                <p>Upar dropdown menu se product select karke <b>Load</b> par click karein.</p>
            </div>
        <?php endif; ?>

        <div class="footer-links">
            <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            <a href="add_product.php">+ Add New Product</a>
        </div>
    </div>
</div>

<script>
    const imgInput = document.getElementById('imgInput');
    const imgPreview = document.getElementById('imgPreview');
    if(imgInput && imgPreview) {
        imgInput.addEventListener('input', function() {
            let val = this.value.trim();
            if(val) {
                if(val.includes('/')) {
                    imgPreview.src = '../assets/images/' + val;
                } else {
                    imgPreview.src = '../assets/images/products/' + val;
                }
            }
        });
    }
</script>

</body>
</html>