<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get customer's personal information
$user_id = $_SESSION['user_id'];

// Fetch user details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_details = mysqli_fetch_assoc($user_result);

// Get customer's loyalty points
$loyalty_sql = "SELECT points FROM loyalty_points WHERE customer_id = $user_id";
$loyalty_result = mysqli_query($conn, $loyalty_sql);
$loyalty_points = 0;

if (mysqli_num_rows($loyalty_result) > 0) {
    $row = mysqli_fetch_assoc($loyalty_result);
    $loyalty_points = $row['points'];
}

// Fetch recent orders
$orders_sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status, 
               COUNT(oi.order_item_id) as total_items
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               WHERE o.customer_id = $user_id
               GROUP BY o.order_id
               ORDER BY o.order_date DESC
               LIMIT 5";
$orders_result = mysqli_query($conn, $orders_sql);

// Fetch recent reviews
$reviews_sql = "SELECT r.rating, r.comment, r.created_at, p.name as product_name
                FROM reviews r
                JOIN products p ON r.product_id = p.product_id
                WHERE r.customer_id = $user_id
                ORDER BY r.created_at DESC
                LIMIT 3";
$reviews_result = mysqli_query($conn, $reviews_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="Customer_Styles.css">
</head>
<body>
    <div class="customer-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h1 class="dashboard-title">Customer Dashboard</h1>
        </div>

        <p class="dashboard-welcome">Welcome, <?php echo htmlspecialchars($user_details['full_name']); ?>!</p>
        
        <div id="account-info">
            <h3>Account Information</h3>
            <p>Name: <?php echo htmlspecialchars($user_details['full_name']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user_details['email']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($user_details['phone'] ?? 'Not provided'); ?></p>
            <p>Address: <?php echo htmlspecialchars($user_details['address'] ?? 'Not provided'); ?></p>
        </div>

       <!-- <div id="loyalty-program">
            <h3>Loyalty Program</h3>
            <p>Your Current Loyalty Points: <php echo $loyalty_points; ?></p>
            <p>Earn points with every purchase and redeem them for discounts!</p>
        </div> -->

        <div id="recent-orders">
            <h3>Recent Orders</h3>
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
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td><a href="order_details_customer.php?order_id=<?php echo $order['order_id']; ?>">View Details</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="order_history.php">View All Orders</a>
            <?php else: ?>
                <p>No recent orders found.</p>
            <?php endif; ?>
        </div>

    <!--<div id="recent-reviews">
            <h3>Your Recent Reviews</h3>
            <php if (mysqli_num_rows($reviews_result) > 0): ?>
                <php while($review = mysqli_fetch_assoc($reviews_result)): ?>
                    <div class="review-item">
                        <p>Product: <php echo htmlspecialchars($review['product_name']); ?></p>
                        <p>Rating: <php echo $review['rating']; ?>/5</p>
                        <p>Comment: <php echo htmlspecialchars($review['comment']); ?></p>
                        <p>Date: <php echo date('d M Y', strtotime($review['created_at'])); ?></p>
                    </div>
                <php endwhile; ?>
                <a href="all_reviews.php">View All Reviews</a>
            <php else: ?>
                <p>No reviews yet. Start shopping and share your experience!</p>
            <php endif; ?>
        </div>-->

        <div id="quick-navigation">
            <h3>Quick Navigation</h3>
            <ul>
                <li><a href="browse_products.php">Browse Products</a></li>
                <li><a href="view_cart.php">View Cart</a></li>
                <li><a href="customer_order_history.php">Order History</a></li>
                <li><a href="edit_profile.php">Edit Profile</a></li>
            </ul>
        </div>
        
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</body>
</html>
