<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Fetch cart items with product details
    $cart_sql = "SELECT c.product_id, c.quantity, p.selling_price, p.quantity as stock_quantity 
                 FROM cart c
                 JOIN products p ON c.product_id = p.product_id
                 WHERE c.customer_id = $user_id";
    $cart_result = mysqli_query($conn, $cart_sql);

    // Check if cart is empty
    if (mysqli_num_rows($cart_result) == 0) {
        throw new Exception("Your cart is empty.");
    }

    // Calculate total and validate stock
    $total_amount = 0;
    $order_items = [];

    while ($item = mysqli_fetch_assoc($cart_result)) {
        // Check stock availability
        if ($item['quantity'] > $item['stock_quantity']) {
            throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
        }

        $subtotal = $item['selling_price'] * $item['quantity'];
        $total_amount += $subtotal;

        $order_items[] = [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['selling_price'],
            'subtotal' => $subtotal
        ];
    }

    // Insert order
    $insert_order_sql = "INSERT INTO orders (customer_id, seller_id, total_amount, status) 
                         VALUES ($user_id, NULL, $total_amount, 'pending')";
    mysqli_query($conn, $insert_order_sql);
    $order_id = mysqli_insert_id($conn);

    // Insert order items and update product stock
    foreach ($order_items as $item) {
        // Insert order item
        $insert_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                            VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, 
                                    {$item['unit_price']}, {$item['subtotal']})";
        mysqli_query($conn, $insert_item_sql);

        // Update product stock
        $update_stock_sql = "UPDATE products SET quantity = quantity - {$item['quantity']} 
                             WHERE product_id = {$item['product_id']}";
        mysqli_query($conn, $update_stock_sql);
    }

    // Clear customer's cart
    $clear_cart_sql = "DELETE FROM cart WHERE customer_id = $user_id";
    mysqli_query($conn, $clear_cart_sql);

    // Award loyalty points (1 point per $10 spent)
    $loyalty_points = floor($total_amount / 10);
    $loyalty_sql = "INSERT INTO loyalty_points (customer_id, points) 
                    VALUES ($user_id, $loyalty_points)
                    ON DUPLICATE KEY UPDATE points = points + $loyalty_points";
    mysqli_query($conn, $loyalty_sql);

    // Commit transaction
    mysqli_commit($conn);

    // Set success message
    $_SESSION['success_message'] = "Order placed successfully! Order ID: $order_id";
    header("Location: order_details.php?order_id=$order_id");
    exit();

} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);

    // Set error message
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: view_cart.php");
    exit();
}
?>