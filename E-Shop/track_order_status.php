<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $po_id = intval($_POST['po_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

    // If status is 'received', update product inventory
    mysqli_begin_transaction($conn);

    try {
        // Update purchase order status
        $status_query = "UPDATE purchase_orders SET status = '$new_status' WHERE po_id = $po_id";
        mysqli_query($conn, $status_query);

        // If status is 'received', update product inventory
        if ($new_status == 'received') {
            // Fetch purchase order items
            $items_query = "SELECT product_id, quantity FROM purchase_order_items WHERE po_id = $po_id";
            $items_result = mysqli_query($conn, $items_query);

            while ($item = mysqli_fetch_assoc($items_result)) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);

                // Update product quantity
                $update_inventory_query = "UPDATE products SET quantity = quantity + $quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_inventory_query);
            }
        }

        mysqli_commit($conn);
        $success_message = "Purchase Order status updated successfully";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Fetch purchase orders with details
$query = "SELECT 
    po.po_id,
    po.order_date,
    po.total_amount,
    po.status,
    po.notes,
    s.name as supplier_name,
    u.username as admin_name,
    COUNT(poi.po_item_id) as item_count,
    SUM(poi.quantity) as total_quantity
FROM purchase_orders po
JOIN suppliers s ON po.supplier_id = s.supplier_id
JOIN users u ON po.admin_id = u.user_id
LEFT JOIN purchase_order_items poi ON po.po_id = poi.po_id
GROUP BY po.po_id
ORDER BY po.order_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Purchase Order Status - Admin Dashboard</title>
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

        .procurement-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .procurement-list-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .procurement-list-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-badge-pending {
            background-color: var(--neutral-color);
            color: var(--white);
        }

        .status-badge-received {
            background-color: var(--success-color);
            color: var(--white);
        }

        .status-badge-cancelled {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .status-explanation {
            margin-top: 20px;
            background-color: var(--background-color);
            padding: 15px;
            border-radius: 6px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .modal-content select,
        .modal-content input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .modal-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
    </style>
    <script>
    function showStatusUpdateModal(poId) {
        const modal = document.getElementById('statusUpdateModal');
        const poIdInput = document.getElementById('update_po_id');
        poIdInput.value = poId;
        modal.style.display = 'block';
    }

    function closeModal() {
        const modal = document.getElementById('statusUpdateModal');
        modal.style.display = 'none';
    }
    </script>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Purchase Order Status Tracking</h1>
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

            <table class="procurement-list-table">
                <thead>
                    <tr>
                        <th>PO ID</th>
                        <th>Order Date</th>
                        <th>Supplier</th>
                        <th>Admin</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Item Count</th>
                        <th>Total Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['po_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['admin_name']); ?></td>
                        <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-badge-<?php echo htmlspecialchars($row['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['item_count']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_quantity']); ?></td>
                        <td>
                            <button 
                                onclick="showStatusUpdateModal(<?php echo $row['po_id']; ?>)" 
                                class="usefull-link"
                                style="width: 100%; padding: 5px; margin: 0;"
                            >
                                Update Status
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="status-explanation">
                <h3>Status Explanation</h3>
                <ul>
                    <li>
                        <span class="status-badge status-badge-pending">Pending</span>: 
                        Purchase order created but not yet received
                    </li>
                    <li>
                        <span class="status-badge status-badge-received">Received</span>: 
                        Purchase order completed and items added to inventory
                    </li>
                    <li>
                        <span class="status-badge status-badge-cancelled">Cancelled</span>: 
                        Purchase order was cancelled
                    </li>
                </ul>
            </div>
        </div>

        <div class="dashboard-actions text-center">
            <a href="create_purchase_orders.php" class="usefull-link">Create New Purchase Order</a>
            <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusUpdateModal" class="modal">
        <div class="modal-content">
            <h3>Update Purchase Order Status</h3>
            <form method="post">
                <input type="hidden" name="po_id" id="update_po_id">
                <input type="hidden" name="update_status" value="1">
                
                <label for="new_status">New Status:</label>
                <select name="new_status" id="new_status" required>
                    <option value="pending">Pending</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                
                <div class="modal-actions">
                    <input type="submit" value="Update Status" class="usefull-link">
                    <button type="button" onclick="closeModal()" class="logout-link">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
