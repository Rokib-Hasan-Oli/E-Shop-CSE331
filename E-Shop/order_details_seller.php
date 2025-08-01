<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// Fetch order details
$order_query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                FROM orders o
                JOIN users u ON o.customer_id = u.user_id
                WHERE o.order_id = $order_id AND o.seller_id = {$_SESSION['user_id']}";
$order_result = mysqli_query($conn, $order_query);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("Location: view_orders.php");
    exit();
}

// Fetch order items
$items_query = "SELECT oi.*, p.name as product_name 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Seller Dashboard</title>
    <link rel="stylesheet" href="Seller_Styles.css">
    <style>
        .order-details-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .order-section {
            margin-bottom: 20px;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 15px;
        }

        .order-section h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .order-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .order-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .order-table tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .order-summary-item {
            flex: 1;
            margin-right: 10px;
            padding: 10px;
            background-color: var(--background-color);
            border-radius: 5px;
        }

        .order-status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-processing {
            background-color: var(--accent-color);
            color: var(--primary-color);
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
            <h1 class="dashboard-title">Order Details</h1>
            <p class="dashboard-welcome">Welcome, Seller</p>
        </div>

        <div class="order-details-container">
            <div class="order-section">
                <h3>Customer Information</h3>
                <div class="order-summary">
                    <div class="order-summary-item">
                        <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                    </div>
                    <div class="order-summary-item">
                        <strong>Order Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['order_date'])); ?>
                    </div>
                </div>
            </div>

            <div class="order-section">
                <h3>Order Summary</h3>
                <table class="order-table">
                    <tr>
                        <th>Total Amount</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>$<?php echo number_format($order['discount'], 2); ?></td>
                        <td>$<?php echo number_format($order['tax'], 2); ?></td>
                        <td><?php echo ucfirst($order['payment_method']); ?></td>
                        <td>
                            <span class="order-status 
                                <?php 
                                    if ($order['status'] == 'processing') echo 'status-processing';
                                    elseif ($order['status'] == 'completed') echo 'status-completed';
                                    elseif ($order['status'] == 'cancelled') echo 'status-cancelled';
                                ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="order-section">
                <h3>Order Items</h3>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_items = 0;
                        while ($item = mysqli_fetch_assoc($items_result)): 
                            $total_items += $item['quantity'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total Items</th>
                            <td><?php echo $total_items; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="navigation-section">
                <a href="view_orders.php" class="logout-link">Back to Orders</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
