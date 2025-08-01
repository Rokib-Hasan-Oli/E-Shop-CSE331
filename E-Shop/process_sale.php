<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch available products
$products_query = "SELECT product_id, name, selling_price, quantity FROM products WHERE status = 'active' AND quantity > 0";
$products_result = mysqli_query($conn, $products_query);

// Fetch customers
$customers_query = "SELECT user_id, full_name, email FROM users WHERE role = 'customer'";
$customers_result = mysqli_query($conn, $customers_query);

// Handle sale processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // Collect sale data
        $customer_id = intval($_POST['customer_id']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $total_amount = 0;
        $discount = floatval($_POST['discount'] ?? 0);
        $tax = floatval($_POST['tax'] ?? 0);

        // Insert order
        $order_query = "INSERT INTO orders 
                        (customer_id, seller_id, total_amount, discount, tax, payment_method) 
                        VALUES 
                        ($customer_id, {$_SESSION['user_id']}, 0, $discount, $tax, '$payment_method')";
        
        if (!mysqli_query($conn, $order_query)) {
            throw new Exception("Error creating order: " . mysqli_error($conn));
        }

        // Get the last inserted order ID
        $order_id = mysqli_insert_id($conn);

        // Process order items
        if (isset($_POST['products']) && is_array($_POST['products'])) {
            foreach ($_POST['products'] as $index => $product_id) {
                $quantity = intval($_POST['quantities'][$index]);
                $unit_price = floatval($_POST['prices'][$index]);
                $subtotal = $quantity * $unit_price;
                $total_amount += $subtotal;

                // Insert order item
                $item_query = "INSERT INTO order_items 
                               (order_id, product_id, quantity, unit_price, subtotal) 
                               VALUES 
                               ($order_id, $product_id, $quantity, $unit_price, $subtotal)";
                
                if (!mysqli_query($conn, $item_query)) {
                    throw new Exception("Error adding order item: " . mysqli_error($conn));
                }

                // Update product quantity
                $update_query = "UPDATE products SET quantity = quantity - $quantity WHERE product_id = $product_id";
                if (!mysqli_query($conn, $update_query)) {
                    throw new Exception("Error updating product quantity: " . mysqli_error($conn));
                }
            }
        }

        // Update total amount in the order
        $update_order_query = "UPDATE orders SET total_amount = $total_amount WHERE order_id = $order_id";
        if (!mysqli_query($conn, $update_order_query)) {
            throw new Exception("Error updating order total: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);
        $success_message = "Sale processed successfully! Order ID: $order_id";
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Sale - Seller Dashboard</title>
    <link rel="stylesheet" href="Seller_Styles.css">
    <style>
        .sale-processing-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .form-section {
            background-color: var(--background-color);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .form-section h3 {
            color: rgb(0, 0, 0);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .form-group select,
        .form-group input[type="number"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .product-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .product-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .product-table tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .action-button {
            display: inline-block;
            text-align: center;
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .action-button:hover {
            background-color: #FFD700;
        }

        .remove-button {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .remove-button:hover {
            background-color: #D32F2F;
        }

        .message-success {
            color: var(--success-color);
            margin-bottom: 15px;
            font-weight: bold;
        }

        .message-error {
            color: var(--danger-color);
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Process Sale</h1>
            <p class="dashboard-welcome">Welcome, Seller</p>
        </div>

        <div class="sale-processing-container">
            <?php 
            if (isset($success_message)) {
                echo "<p class='message-success'>$success_message</p>";
            }
            if (isset($error_message)) {
                echo "<p class='message-error'>$error_message</p>";
            }
            ?>

            <form method="POST" action="">
                <div class="form-section">
                    <h3>Customer Information</h3>
                    <div class="form-group">
                        <label for="customer_id">Select Customer</label>
                        <select name="customer_id" id="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                <option value="<?php echo $customer['user_id']; ?>">
                                    <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['email'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Add Products</h3>
                    <div class="form-group">
                        <label for="product-select">Select Product</label>
                        <select id="product-select" name="new_product">
                            <option value="">Select Product</option>
                            <?php 
                            mysqli_data_seek($products_result, 0);
                            while ($product = mysqli_fetch_assoc($products_result)): 
                            ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                        data-price="<?php echo $product['selling_price']; ?>"
                                        data-quantity="<?php echo $product['quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name'] . ' ($' . number_format($product['selling_price'], 2) . ' - Stock: ' . $product['quantity'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity-input">Quantity</label>
                        <input type="number" id="quantity-input" name="new_quantity" min="1" placeholder="Enter Quantity">
                    </div>

                    <button type="submit" name="add_product" class="action-button">Add Product</button>

                    <?php
                    // Handle adding product to session
                    if (isset($_POST['add_product']) && $_POST['new_product'] && $_POST['new_quantity']) {
                        $product_id = intval($_POST['new_product']);
                        $quantity = intval($_POST['new_quantity']);

                        // Fetch product details
                        $product_check_query = "SELECT name, selling_price, quantity FROM products WHERE product_id = $product_id";
                        $product_check_result = mysqli_query($conn, $product_check_query);
                        $product = mysqli_fetch_assoc($product_check_result);

                        if ($product && $quantity <= $product['quantity']) {
                            if (!isset($_SESSION['sale_products'])) {
                                $_SESSION['sale_products'] = [];
                            }

                            $_SESSION['sale_products'][] = [
                                'product_id' => $product_id,
                                'name' => $product['name'],
                                'quantity' => $quantity,
                                'price' => $product['selling_price']
                            ];
                        } else {
                            echo "<p class='message-error'>Invalid product or quantity exceeds stock!</p>";
                        }
                    }
                    ?>

                    <?php if (isset($_SESSION['sale_products']) && !empty($_SESSION['sale_products'])): ?>
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($_SESSION['sale_products'] as $index => $item): 
                                    $subtotal = $item['quantity'] * $item['price'];
                                    $total += $subtotal;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>$<?php echo number_format($subtotal, 2); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="remove_index" value="<?php echo $index; ?>">
                                                <button type="submit" name="remove_product" class="action-button remove-button">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php
                    // Handle removing product from session
                    if (isset($_POST['remove_product']) && isset($_POST['remove_index'])) {
                        $index = intval($_POST['remove_index']);
                        unset($_SESSION['sale_products'][$index]);
                        $_SESSION['sale_products'] = array_values($_SESSION['sale_products']);
                    }
                    ?>
                </div>

                <div class="form-section">
                    <h3>Payment Details</h3>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="online">Online</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="discount">Discount</label>
                        <input type="number" name="discount" id="discount" step="0.01" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label for="tax">Tax</label>
                        <input type="number" name="tax" id="tax" step="0.01" value="0" min="0">
                    </div>
                    <button type="submit" name="process_sale" class="action-button">Process Sale</button>
                </div>

                <div class="navigation-section">
                    <a href="seller_dashboard.php" class="logout-link">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
// Handle final sale processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_sale']) && isset($_SESSION['sale_products'])) {
    // Reuse the existing sale processing logic from earlier in the script
    // You can modify this to use the $_SESSION['sale_products'] instead
    
    // Clear the sale products after processing
    unset($_SESSION['sale_products']);
}

// Close the database connection
mysqli_close($conn);
?>
