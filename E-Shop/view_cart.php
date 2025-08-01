<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch customer details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_details = mysqli_fetch_assoc($user_result);

// Fetch cart items
$cart_sql = "SELECT c.*, p.name, p.selling_price, p.quantity as stock_quantity, p.image_url, 
             p.description, cat.name as category_name
             FROM cart c
             JOIN products p ON c.product_id = p.product_id
             LEFT JOIN categories cat ON p.category_id = cat.category_id
             WHERE c.customer_id = $user_id";
$cart_result = mysqli_query($conn, $cart_sql);

// Calculate total
$subtotal = 0;
$total_items = 0;
$tax_rate = 0.10; // 10% tax
$tax_amount = 0;
$grand_total = 0;

// Process payment if form is submitted
$payment_success = false;
$payment_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (mysqli_num_rows($cart_result) <= 0) {
        $payment_error = "Your cart is empty. Please add items before proceeding.";
    } else {
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        // Calculate totals
        mysqli_data_seek($cart_result, 0); // Reset result pointer
        while($item = mysqli_fetch_assoc($cart_result)) {
            $subtotal += $item['selling_price'] * $item['quantity'];
        }
        
        $tax_amount = $subtotal * $tax_rate;
        $grand_total = $subtotal + $tax_amount;
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert into orders table
            $order_sql = "INSERT INTO orders (customer_id, seller_id, total_amount, tax, payment_method, notes) 
                          VALUES ($user_id, 1, $grand_total, $tax_amount, '$payment_method', '$notes')";
            
            if (mysqli_query($conn, $order_sql)) {
                $order_id = mysqli_insert_id($conn);
                
                // Insert order items
                mysqli_data_seek($cart_result, 0); // Reset result pointer
                while($item = mysqli_fetch_assoc($cart_result)) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $unit_price = $item['selling_price'];
                    $item_subtotal = $unit_price * $quantity;
                    
                    $order_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                                      VALUES ($order_id, $product_id, $quantity, $unit_price, $item_subtotal)";
                    mysqli_query($conn, $order_item_sql);
                    
                    // Update product stock
                    $update_stock_sql = "UPDATE products SET quantity = quantity - $quantity 
                                        WHERE product_id = $product_id";
                    mysqli_query($conn, $update_stock_sql);
                }
                
                // Clear cart
                $clear_cart_sql = "DELETE FROM cart WHERE customer_id = $user_id";
                mysqli_query($conn, $clear_cart_sql);
                
                // Commit transaction
                mysqli_commit($conn);
                
                $payment_success = true;
            } else {
                throw new Exception("Error creating order");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $payment_error = "Error processing your order: " . $e->getMessage();
        }
    }
    
    // Refresh cart data if payment failed
    if (!$payment_success) {
        $cart_result = mysqli_query($conn, $cart_sql);
    }
}

// Recalculate totals for display
if (!$payment_success) {
    mysqli_data_seek($cart_result, 0); // Reset result pointer
    while($item = mysqli_fetch_assoc($cart_result)) {
        $subtotal += $item['selling_price'] * $item['quantity'];
        $total_items += $item['quantity'];
    }
    
    $tax_amount = $subtotal * $tax_rate;
    $grand_total = $subtotal + $tax_amount;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Payment</title>
    <link rel="stylesheet" href="Customer_Styles.css">
</head>
<body>
    <div class="customer-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h1 class="dashboard-title">Review Order & Payment</h1>
        </div>

        <p class="dashboard-welcome">Welcome, <?php echo htmlspecialchars($user_details['full_name']); ?>!</p>
        
        <?php if ($payment_success): ?>
            <div class="success-message">
                <h2>Payment Successful!</h2>
                <p>Your order has been placed successfully. Thank you for your purchase!</p>
                <div class="order-actions">
                    <a href="customer_dashboard.php" class="btn primary-btn">Back to Dashboard</a>
                    <a href="order_history.php" class="btn secondary-btn">View Orders</a>
                </div>
            </div>
        <?php elseif (!empty($payment_error)): ?>
            <div class="error-message">
                <p><?php echo $payment_error; ?></p>
            </div>
        <?php else: ?>
            <div class="order-review">
                <h3>Order Summary</h3>
                
                <?php if (mysqli_num_rows($cart_result) > 0): ?>
                    <div class="product-list">
                        <?php 
                        mysqli_data_seek($cart_result, 0); // Reset result pointer
                        while($item = mysqli_fetch_assoc($cart_result)): 
                        ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if(!empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="category"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></p>
                                    <p class="price">$<?php echo number_format($item['selling_price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                                    <p class="subtotal">$<?php echo number_format($item['selling_price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $total_items; ?> items):</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (10%):</span>
                            <span>$<?php echo number_format($tax_amount, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                    
                  <div class="payment-section">
                        <h3>Payment Information</h3>
                        <form action="" method="post" id="payment-form">
    <div class="form-group">
        <h4>Select Payment Method:</h4>
        <div class="radio-group">
            <div class="radio-option">
                <input type="radio" id="cash" name="payment_method" value="cash" required>
                <label for="cash">Cash on Delivery</label>
            </div>
            <div class="radio-option">
                <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                <label for="credit_card">Credit Card</label>
            </div>
            <div class="radio-option">
                <input type="radio" id="debit_card" name="payment_method" value="debit_card">
                <label for="debit_card">Debit Card</label>
            </div>
            <div class="radio-option">
                <input type="radio" id="online" name="payment_method" value="online">
                <label for="online">Online Payment</label>
            </div>
        </div>
    </div>
                            
                            
                            <div class="form-group">
                                <label for="notes">Order Notes (Optional):</label>
                                <textarea name="notes" id="notes" rows="3" placeholder="Special instructions for your order..."></textarea>
                            </div>
                            
                            <div class="shipping-address">
                                <h4>Shipping Address</h4>
                                <p><?php echo htmlspecialchars($user_details['full_name']); ?></p>
                                <p><?php echo htmlspecialchars($user_details['phone'] ?? 'No phone provided'); ?></p>
                                <p><?php echo htmlspecialchars($user_details['address'] ?? 'No address provided'); ?></p>
                                <a href="edit_profile.php" class="edit-address-btn">Edit Address</a>
                            </div>
                            
                            <div class="payment-actions">
                                <a href="view_cart.php" class="btn secondary-btn">Back to Cart</a>
                                <button type="submit" class="btn primary-btn">Complete Payment</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-cart">
                        <p>Your cart is empty. Please add items before proceeding to payment.</p>
                        <a href="browse_products.php" class="btn primary-btn">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div id="quick-navigation">
            <h3>Quick Navigation</h3>
            <ul>
                <li><a href="browse_products.php">Browse Products</a></li>
                <li><a href="view_cart.php">View Cart</a></li>
                <li><a href="customer_dashboard.php">Dashboard</a></li>
            </ul>
        </div>
    </div>

    <script>
        // Show/hide card details based on payment method
        document.getElementById('payment_method').addEventListener('change', function() {
            const cardDetails = document.getElementById('card-details');
            if (this.value === 'credit_card' || this.value === 'debit_card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
        
        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            this.value = formattedValue;
        });
        
        // Format expiry date with slash
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 2) {
                this.value = value.substring(0, 2) + '/' + value.substring(2, 4);
            } else {
                this.value = value;
            }
        });
    </script>

    <style>
        /* Payment page specific styles */
        .product-list {
            margin-bottom: 20px;
        }
        
        .product-card {
            display: flex;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            overflow: hidden;
            margin-right: 15px;
            border-radius: 4px;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            color: #999;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-info h4 {
            margin-top: 0;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .category {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .price {
            font-weight: bold;
        }
        
        .subtotal {
            font-weight: bold;
            color: var(--accent-color);
            text-align: right;
        }
        
        .order-summary {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-row.total {
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .payment-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .card-exp-cvv {
            display: flex;
            gap: 15px;
        }
        
        .shipping-address {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .shipping-address h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .shipping-address p {
            margin: 5px 0;
        }
        
        .edit-address-btn {
            display: inline-block;
            margin-top: 10px;
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .payment-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        
        .primary-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .secondary-btn {
            background-color: #f5f5f5;
            color: var(--text-color);
            border: 1px solid #ddd;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .order-actions {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
    </style>
</body>
</html>