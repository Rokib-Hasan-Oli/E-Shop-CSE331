<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Check if product_id and quantity are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    header("Location: view_cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// Validate quantity
if ($quantity < 1) {
    $_SESSION['error_message'] = "Invalid quantity.";
    header("Location: view_cart.php");
    exit();
}

// Check product availability
$product_sql = "SELECT quantity FROM products WHERE product_id = $product_id AND status = 'active'";
$product_result = mysqli_query($conn, $product_sql);
$product = mysqli_fetch_assoc($product_result);

if (!$product || $product['quantity'] < $quantity) {
    $_SESSION['error_message'] = "Requested quantity exceeds available stock.";
    header("Location: view_cart.php");
    exit();
}

// Update cart item
$update_sql = "UPDATE cart SET quantity = $quantity, updated_at = CURRENT_TIMESTAMP 
               WHERE customer_id = $user_id AND product_id = $product_id";
mysqli_query($conn, $update_sql);

$_SESSION['success_message'] = "Cart updated successfully!";
header("Location: view_cart.php");
exit();
?>