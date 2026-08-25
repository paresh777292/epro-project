<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Security Check
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

$msg = "";
$error = "";

// 3. Form Submit Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $price    = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    
    // Custom category input check (agar user ne 'other' choose karke custom likha ho)
    if ($category === 'other' && !empty($_POST['custom_category'])) {
        $category = mysqli_real_escape_string($conn, trim($_POST['custom_category']));
    }

    // Image Upload Handling
    $image_name = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $img_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($img_ext, $allowed)) {
            $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image']['name']);
            $target_dir = "../assets/images/products/";

            // Agar folder na ho toh create karein
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
        } else {
            $error = "Sirf JPG, JPEG, PNG, aur WEBP images allowed hain!";
        }
    } else {
        $error = "Product ki image upload karna zaroori hai!";
    }

    // Database Insert
    if (empty($error)) {
        $insert_query = "INSERT INTO `products` (`name`, `price`, `category`, `image`) 
                         VALUES ('$name', '$price', '$category', '$image_name')";

        if (mysqli_query($conn, $insert_query)) {
            $msg = "Product successfully add ho gaya!";
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Admin Panel</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body {
            background-color: #0b0f19;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(129, 140, 248, 0.12) 0%, transparent 40%);
            min-height: 100vh;
            color: #f8fafc;
            padding: 40px 20px;
        }

        .form-card {
            max-width: 600px;
            margin: auto;
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 15px;
        }

        .header-box h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-btn {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s;
        }
        .back-btn:hover { color: #38bdf8; }

        .toast-msg {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            color: #4ade80;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .error-msg {
            background: rgba(244, 63, 94, 0.2);
            border: 1px solid #f43f5e;
            color: #fda4af;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.25);
        }

        select.form-control option {
            background: #1e293b;
            color: #f8fafc;
        }

        .btn-add {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.3);
            transition: 0.2s;
            margin-top: 10px;
        }
        .btn-add:hover {
            opacity: 0.95;
            box-shadow: 0 8px 25px rgba(56, 189, 248, 0.5);
        }
    </style>
</head>
<body>

<div class="form-card">
    <div class="header-box">
        <h2><i class="fa-solid fa-cart-plus"></i> Add New Product</h2>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="toast-msg"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <!-- Product Name -->
        <div class="form-group">
            <label><i class="fa-solid fa-tag"></i> Product Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Wireless Pro Headphone" required>
        </div>

        <!-- Product Price -->
        <div class="form-group">
            <label><i class="fa-solid fa-indian-rupee-sign"></i> Price (INR)</label>
            <input type="number" step="0.01" name="price" class="form-control" placeholder="e.g. 1999" required>
        </div>

        <!-- Product Category Dropdown -->
        <div class="form-group">
            <label><i class="fa-solid fa-layer-group"></i> Category</label>
            <select name="category" id="catSelect" class="form-control" onchange="toggleCustomCategory(this.value)" required>
                <option value="" disabled selected>Select a Category</option>
                <option value="Audio">Audio & Headphones</option>
                <option value="Clothing">Clothing & Apparel</option>
                <option value="Footwear">Footwear & Shoes</option>
                <option value="Mobiles">Mobiles & Gadgets</option>
                <option value="Wearables">Smart Wearables</option>
                <option value="Grooming">Grooming & Appliances</option>
                <option value="Fragrance">Fragrance & Perfumes</option>
                <option value="other">+ Type Custom Category...</option>
            </select>
        </div>

        <!-- Custom Category Text Input (Hidden by default) -->
        <div class="form-group" id="customCatBox" style="display: none;">
            <label><i class="fa-solid fa-pen"></i> Enter Custom Category</label>
            <input type="text" name="custom_category" id="customCatInput" class="form-control" placeholder="e.g. Gaming Accessories">
        </div>

        <!-- Product Image File -->
        <div class="form-group">
            <label><i class="fa-solid fa-image"></i> Product Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
        </div>

        <button type="submit" name="add_product" class="btn-add">
            <i class="fa-solid fa-plus"></i> Save Product
        </button>
    </form>
</div>

<script>
function toggleCustomCategory(value) {
    const customBox = document.getElementById('customCatBox');
    const customInput = document.getElementById('customCatInput');
    if (value === 'other') {
        customBox.style.display = 'block';
        customInput.required = true;
    } else {
        customBox.style.display = 'none';
        customInput.required = false;
    }
}
</script>

</body>
</html>