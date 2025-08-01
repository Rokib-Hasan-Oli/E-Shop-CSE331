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
    header("Location: browse_products.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// Validate quantity
if ($quantity < 1) {
    $_SESSION['error_message'] = "Invalid quantity.";
    header("Location: browse_products.php");
    exit();
}

// Check product availability
$product_sql = "SELECT quantity, selling_price FROM products WHERE product_id = $product_id AND status = 'active'";
$product_result = mysqli_query($conn, $product_sql);
$product = mysqli_fetch_assoc($product_result);

if (!$product || $product['quantity'] < $quantity) {
    $_SESSION['error_message'] = "Requested quantity exceeds available stock.";
    header("Location: browse_products.php");
    exit();
}

// Check if product is already in cart
$check_cart_sql = "SELECT quantity FROM cart WHERE customer_id = $user_id AND product_id = $product_id";
$check_cart_result = mysqli_query($conn, $check_cart_sql);

if (mysqli_num_rows($check_cart_result) > 0) {
    // Update existing cart item
    $existing_item = mysqli_fetch_assoc($check_cart_result);
    $new_quantity = $existing_item['quantity'] + $quantity;
    
    // Validate total quantity
    if ($new_quantity > $product['quantity']) {
        $_SESSION['error_message'] = "Cannot add more items than available in stock.";
        header("Location: browse_products.php");
        exit();
    }
    
    $update_sql = "UPDATE cart SET quantity = $new_quantity, updated_at = CURRENT_TIMESTAMP 
                   WHERE customer_id = $user_id AND product_id = $product_id";
    mysqli_query($conn, $update_sql);
} else {
    // Insert new cart item
    $insert_sql = "INSERT INTO cart (customer_id, product_id, quantity) 
                   VALUES ($user_id, $product_id, $quantity)";
    mysqli_query($conn, $insert_sql);
}

$_SESSION['success_message'] = "Product added to cart successfully!";
header("Location: browse_products.php");
exit();
?>