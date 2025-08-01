<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch orders for the current seller with customer details
$orders_query = "SELECT o.*, u.full_name as customer_name 
                 FROM orders o
                 JOIN users u ON o.customer_id = u.user_id
                 WHERE o.seller_id = {$_SESSION['user_id']}
                 ORDER BY o.order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Seller Dashboard</title>
    <link rel="stylesheet" href="Seller_Styles.css">
    <style>
        .orders-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .orders-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .orders-table tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .action-links a {
            display: inline-block;
            margin-right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .action-links a:hover {
            background-color: #FFD700;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-pending {
            background-color: var(--neutral-color);
            color: var(--white);
        }

        .status-completed {
            background-color: var(--success-color);
            color: var(--white);
        }

        .status-cancelled {
            background-color: var(--danger-color);
            color: var(--white);
        }
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Order History</h1>
            <p class="dashboard-welcome">Welcome, Seller</p>
        </div>

        <div class="orders-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                    <tr>
                        <td><?php echo $order['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>$<?php echo number_format($order['discount'], 2); ?></td>
                        <td>$<?php echo number_format($order['tax'], 2); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
                        <td>
                            <span class="status-badge <?php 
                                switch($order['status']) {
                                    case 'completed':
                                        echo 'status-completed';
                                        break;
                                    case 'cancelled':
                                        echo 'status-cancelled';
                                        break;
                                    default:
                                        echo 'status-pending';
                                } ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <a href="order_details_seller.php?id=<?php echo $order['order_id']; ?>">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <br>
            <div class="navigation-section">
                <a href="seller_dashboard.php" class="logout-link">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
