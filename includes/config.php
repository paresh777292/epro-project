<?php
$conn = mysqli_connect("localhost", "root", "", "epro");
if(!$conn){
    die("Database Connection Failed");
}
session_start();
?>
