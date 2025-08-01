<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch suppliers for dropdown
$supplier_query = "SELECT supplier_id, name FROM suppliers WHERE status = 'active'";
$supplier_result = mysqli_query($conn, $supplier_query);

// Fetch low stock products
$product_query = "SELECT 
    product_id, 
    name, 
    quantity, 
    min_stock_level, 
    cost_price, 
    category_id, 
    supplier_id 
FROM products 
WHERE quantity <= min_stock_level 
ORDER BY quantity ASC";
$product_result = mysqli_query($conn, $product_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert main purchase order
        $supplier_id = intval($_POST['supplier_id']);
        $admin_id = intval($_SESSION['user_id']);
        $total_amount = 0;
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

        // Prepare purchase order insert
        $po_query = "INSERT INTO purchase_orders (supplier_id, admin_id, total_amount, status, notes) 
                     VALUES ($supplier_id, $admin_id, 0, 'pending', '$notes')";
        mysqli_query($conn, $po_query);
        $po_id = mysqli_insert_id($conn);

        // Process purchase order items
        $products = $_POST['products'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $subtotals = [];

        foreach ($products as $index => $product_id) {
            $quantity = intval($quantities[$index]);
            if ($quantity <= 0) continue; // Skip zero or negative quantities

            // Get product cost
            $product_query = "SELECT cost_price FROM products WHERE product_id = " . intval($product_id);
            $product_result = mysqli_query($conn, $product_query);
            $product_row = mysqli_fetch_assoc($product_result);
            $unit_cost = floatval($product_row['cost_price']);

            $subtotal = $quantity * $unit_cost;
            $total_amount += $subtotal;

            // Insert purchase order item
            $item_query = "INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_cost, subtotal) 
                           VALUES ($po_id, " . intval($product_id) . ", $quantity, $unit_cost, $subtotal)";
            mysqli_query($conn, $item_query);
        }

        // Update total amount in purchase order
        $update_total_query = "UPDATE purchase_orders SET total_amount = $total_amount WHERE po_id = $po_id";
        mysqli_query($conn, $update_total_query);

        // Commit transaction
        mysqli_commit($conn);

        $success_message = "Purchase Order created successfully. Order ID: $po_id";
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $error_message = "Error creating purchase order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Purchase Order - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .procurement-management-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .purchase-order-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .purchase-order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .purchase-order-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .purchase-order-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .error-message {
            color: var(--danger-color);
            margin-bottom: 15px;
        }

        .success-message {
            color: var(--success-color);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Create Purchase Order</h1>
            <p class="dashboard-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
        </div>

        <div class="procurement-management-container">
            <?php 
            // Display validation errors, success, or error messages
            if (isset($error_message)) {
                echo "<div class='error-message'>" . htmlspecialchars($error_message) . "</div>";
            }
            if (isset($success_message)) {
                echo "<div class='success-message'>" . htmlspecialchars($success_message) . "</div>";
            }
            ?>

            <form method="post" class="purchase-order-form">
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php while ($supplier = mysqli_fetch_assoc($supplier_result)): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" placeholder="Optional purchase order notes"></textarea>
                </div>

                <h3>Purchase Order Items</h3>
                <table class="purchase-order-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Min Stock Level</th>
                            <th>Unit Cost</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $index = 0;
                        mysqli_data_seek($product_result, 0);
                        while ($product = mysqli_fetch_assoc($product_result)): 
                        ?>
                        <tr>
                            <td>
                                <input type="hidden" name="products[]" value="<?php echo $product['product_id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td><?php echo $product['min_stock_level']; ?></td>
                            <td>$<?php echo number_format($product['cost_price'], 2); ?></td>
                            <td>
                                <input type="number" 
                                       name="quantities[]" 
                                       min="0" 
                                       value="<?php echo max(0, $product['min_stock_level'] - $product['quantity']); ?>"
                                       class="form-control">
                            </td>
                            <td>
                                $<?php 
                                $default_quantity = max(0, $product['min_stock_level'] - $product['quantity']);
                                echo number_format($default_quantity * $product['cost_price'], 2); 
                                ?>
                            </td>
                        </tr>
                        <?php 
                        $index++; 
                        endwhile; 
                        ?>
                    </tbody>
                </table>

                <div class="dashboard-actions text-center">
                    <button type="submit" class="usefull-link">Create Purchase Order</button>
                    <a href="track_product_procurement.php" class="usefull-link">View Purchase Orders</a>
                    <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
