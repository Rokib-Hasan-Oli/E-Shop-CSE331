<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    header("Location: view_cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);

// Remove item from cart
$delete_sql = "DELETE FROM cart WHERE customer_id = $user_id AND product_id = $product_id";
mysqli_query($conn, $delete_sql);

$_SESSION['success_message'] = "Item removed from cart successfully!";
header("Location: view_cart.php");
exit();
?>