<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get customer's information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT full_name FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_details = mysqli_fetch_assoc($user_result);

// Pagination setup
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total orders count for pagination
$count_sql = "SELECT COUNT(DISTINCT o.order_id) as total 
              FROM orders o 
              WHERE o.customer_id = $user_id";
$count_result = mysqli_query($conn, $count_sql);
$total_orders = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Fetch customer's orders with pagination
$orders_sql = "SELECT o.order_id, o.order_date, o.total_amount, o.payment_method, o.status,
               COUNT(oi.order_item_id) as total_items
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               WHERE o.customer_id = $user_id
               GROUP BY o.order_id
               ORDER BY o.order_date DESC
               LIMIT $offset, $items_per_page";
$orders_result = mysqli_query($conn, $orders_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="Customer_Styles.css">
    <style>
        .order-history {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .order-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .order-products {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-products th, .order-products td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .order-products tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 16px;
            text-decoration: none;
            color: #333;
            background-color: #f2f2f2;
            margin: 0 4px;
            border-radius: 4px;
        }
        
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #333;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .status-pending {
            color: #f39c12;
        }
        
        .status-completed {
            color: #2ecc71;
        }
        
        .status-cancelled {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="order-history">
        <a href="customer_dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Order History</h1>
        <p>Welcome, <?php echo htmlspecialchars($user_details['full_name']); ?>. Here is your complete order history.</p>
        
        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3>Order #<?php echo $order['order_id']; ?></h3>
                        <span class="status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                    
                    <div class="order-details">
                        <div>
                            <strong>Date:</strong> <?php echo date('F d, Y', strtotime($order['order_date'])); ?>
                        </div>
                        <div>
                            <strong>Items:</strong> <?php echo $order['total_items']; ?>
                        </div>
                        <div>
                            <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                        </div>
                        <div>
                            <strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                    
                    <?php
                    // Fetch order items
                    $order_id = $order['order_id'];
                    $items_sql = "SELECT oi.quantity, oi.unit_price, oi.subtotal, p.name as product_name
                                 FROM order_items oi
                                 JOIN products p ON oi.product_id = p.product_id
                                 WHERE oi.order_id = $order_id";
                    $items_result = mysqli_query($conn, $items_sql);
                    ?>
                    
                    <h4>Products</h4>
                    <table class="order-products">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endwhile; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <a class="active" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="browse_products.php">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
