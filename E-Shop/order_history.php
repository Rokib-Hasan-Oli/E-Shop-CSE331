<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch user details for welcome message
$user_sql = "SELECT full_name FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_details = mysqli_fetch_assoc($user_result);

// Fetch all orders for the customer
$orders_sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status, 
               COUNT(oi.order_item_id) as total_items
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               WHERE o.customer_id = $user_id
               GROUP BY o.order_id
               ORDER BY o.order_date DESC";
$orders_result = mysqli_query($conn, $orders_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="Customer_Styles.css">
</head>
<body>
    <div class="customer-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h1 class="dashboard-title">Order History</h1>
        </div>

        <p class="dashboard-welcome">Welcome, <?php echo htmlspecialchars($user_details['full_name']); ?>!</p>
        
        <div id="recent-orders">
            <h3>Your Complete Order History</h3>
            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['total_items']; ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="
                                    <?php 
                                    switch($order['status']) {
                                        case 'completed':
                                            echo 'text-success';
                                            break;
                                        case 'cancelled':
                                            echo 'text-danger';
                                            break;
                                        default:
                                            echo 'text-neutral';
                                    }
                                    ?>
                                ">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                            <a href="order_details_customer.php?order_id=<?php echo $order['order_id']; ?>">View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found. Start shopping to place your first order!</p>
            <?php endif; ?>
        </div>

        <div id="quick-navigation">
            <h3>Quick Navigation</h3>
            <ul>
                <li><a href="browse_products.php">Browse Products</a></li>
                <li><a href="view_cart.php">View Cart</a></li>
                <li><a href="customer_dashboard.php">Back to Dashboard</a></li>
            </ul>
        </div>
        
    </div>
</body>
</html>
