<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Fetch order details
$order_sql = "SELECT o.*, u.full_name as seller_name 
              FROM orders o
              LEFT JOIN users u ON o.seller_id = u.user_id
              WHERE o.order_id = $order_id AND o.customer_id = $user_id";
$order_result = mysqli_query($conn, $order_sql);

if (mysqli_num_rows($order_result) == 0) {
    echo "Order not found.";
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Fetch order items
$items_sql = "SELECT oi.*, p.name as product_name, p.image_url 
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_sql);

// Fetch user details for welcome message
$user_sql = "SELECT full_name FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_details = mysqli_fetch_assoc($user_result);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $order['status'] == 'pending') {
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Update order status and payment method
    $update_sql = "UPDATE orders 
                   SET status = 'completed', 
                       payment_method = '$payment_method'
                   WHERE order_id = $order_id";
    
    if (mysqli_query($conn, $update_sql)) {
        // Redirect to show successful payment
        header("Location: order_details_customer.php?order_id=$order_id&payment_success=1");
        exit();
    } else {
        $payment_error = "Failed to process payment. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="Customer_Styles.css">
    <style>
        .payment-section {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .payment-options {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .payment-option {
            flex: 1;
            margin: 0 10px;
            text-align: center;
            padding: 15px;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-option:hover {
            border-color: var(--primary-color);
            background-color: #f1f1f1;
        }
        .payment-option input[type="radio"] {
            display: none;
        }
        .payment-option.selected {
            border-color: var(--success-color);
            background-color: #e6f3e6;
        }
    </style>
</head>
<body>
    <div class="customer-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h1 class="dashboard-title">Order Details</h1>
        </div>

        <p class="dashboard-welcome">Welcome, <?php echo htmlspecialchars($user_details['full_name']); ?>!</p>
        
        <?php if (isset($_GET['payment_success'])): ?>
        <div class="alert text-success" style="background-color: #dff0d8; color: #3c763d; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            Payment successful! Your order has been completed.
        </div>
        <?php endif; ?>

        <?php if (isset($payment_error)): ?>
        <div class="alert text-danger" style="background-color: #f2dede; color: #a94442; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $payment_error; ?>
        </div>
        <?php endif; ?>
        
        <div id="order-summary">
            <h3>Order Information</h3>
            <div class="order-info-grid">
                <div class="order-detail">
                    <strong>Order Number:</strong>
                    <span>#<?php echo $order['order_id']; ?></span>
                </div>
                <div class="order-detail">
                    <strong>Order Date:</strong>
                    <span><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="order-detail">
                    <strong>Order Status:</strong>
                    <span class="<?php 
                        echo ($order['status'] == 'completed') ? 'text-success' : 
                             (($order['status'] == 'cancelled') ? 'text-danger' : 'text-neutral');
                    ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div class="order-detail">
                    <strong>Seller:</strong>
                    <span><?php echo htmlspecialchars($order['seller_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <div id="order-items">
            <h3>Order Items</h3>
            <table>
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
                    $reset_items_result = mysqli_data_seek($items_result, 0);
                    while($item = mysqli_fetch_assoc($items_result)): 
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
                        <td colspan="3">Subtotal</td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                    <?php if ($order['discount'] > 0): ?>
                    <tr>
                        <td colspan="3">Discount</td>
                        <td class="text-success">-$<?php echo number_format($order['discount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($order['tax'] > 0): ?>
                    <tr>
                        <td colspan="3">Tax</td>
                        <td>$<?php echo number_format($order['tax'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3"><strong>Total Amount</strong></td>
                        <td><strong>$<?php echo number_format($order['total_amount'] - ($order['discount'] ?? 0) + ($order['tax'] ?? 0), 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($order['status'] == 'pending'): ?>
        <div class="payment-section">
            <h3>Complete Payment</h3>
            <form method="post" action="">
                <p>Please select a payment method to complete your order:</p>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="credit_card" required>
                        <h4>Credit Card</h4>
                        <p>Pay securely with your credit card</p>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="debit_card" required>
                        <h4>Debit Card</h4>
                        <p>Pay directly from your bank account</p>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="online" required>
                        <h4>Online Payment</h4>
                        <p>Pay through online banking</p>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cash" required>
                        <h4>Cash on Delivery</h4>
                        <p>Pay when you receive the order</p>
                    </label>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="logout-link" style="display: inline-block; background-color: var(--success-color);">
                        Complete Payment
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div id="quick-navigation">
            <h3>Quick Navigation</h3>
            <ul>
                <li><a href="order_history.php">Back to Order History</a></li>
                <li><a href="customer_dashboard.php">Return to Dashboard</a></li>
                <li><a href="browse_products.php">Continue Shopping</a></li>
            </ul>
        </div>
        
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <script>
        // Add interactivity to payment options (optional, since you requested no JavaScript)
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>
