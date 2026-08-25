<?php
include("../config.php");
$id = $_GET['id']; // URL se ID uthayega

$res = mysqli_query($conn, "SELECT * FROM products WHERE id = '$id'");
$product = mysqli_fetch_assoc($res);

// Folder logic
$folder = ($product['is_special'] == 1) ? "product1" : "products";
?>

<h1><?php echo $product['name']; ?></h1>
<img src="../assets/images/<?php echo $folder; ?>/<?php echo $product['image']; ?>">
<p>Price: ₹<?php echo $product['price']; ?></p>

<a href="cart.php?add=<?php echo $product['id']; ?>">Add to Cart</a>