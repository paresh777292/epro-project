<?php
include("../config.php");

$id=$_GET['id'];
mysqli_query($conn,"DELETE FROM products WHERE id=$id");

header("Location: view_products.php");
exit();
?>
