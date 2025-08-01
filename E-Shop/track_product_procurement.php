<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle form submissions for updating purchase order status
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $po_id = intval($_POST['po_id']);
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);

        $update_query = "UPDATE purchase_orders SET status = '$new_status' WHERE po_id = $po_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Purchase order status updated successfully";
        } else {
            $error_message = "Error updating purchase order status: " . mysqli_error($conn);
        }
    }
}

// Fetch procurement details
$query = "SELECT 
    po.po_id,
    po.order_date,
    po.total_amount,
    po.status,
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

// Fetch status options for dropdown
$status_options = ['pending', 'received', 'cancelled', 'processing'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Procurement Tracking - Admin Dashboard</title>
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

        .status-dropdown {
            width: 100%;
            padding: 5px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
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

        .status-badge-processing {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
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
            <h1 class="dashboard-title">Product Procurement Tracking</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="procurement-management-container">
            <?php 
            // Display validation errors, success, or error messages
            if (isset($error_message)) {
                echo "<div class='error-message'>" . $error_message . "</div>";
            }
            if (isset($success_message)) {
                echo "<div class='success-message'>" . $success_message . "</div>";
            }
            ?>

            <h3>Purchase Orders</h3>
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
                        <td class="action-buttons">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="po_id" value="<?php echo $row['po_id']; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" class="status-dropdown" onchange="this.form.submit()">
                                    <?php foreach ($status_options as $status): ?>
                                        <option value="<?php echo $status; ?>" 
                                            <?php echo $status == $row['status'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <a href="view_purchase_order_details.php?po_id=<?php echo $row['po_id']; ?>" 
                               class="edit-btn" style="
                                   display: inline-block;
                                   padding: 5px 10px;
                                   background-color: var(--accent-color);
                                   color: var(--primary-color);
                                   text-decoration: none;
                                   border-radius: 4px;
                               ">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="procurement-status-explanation">
                <h3>Procurement Status Explanation</h3>
                <ul>
                    <li>
                        <span class="status-badge status-badge-pending">Pending</span>: 
                        Purchase order created but not yet processed
                    </li>
                    <li>
                        <span class="status-badge status-badge-processing">Processing</span>: 
                        Purchase order is currently being worked on
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
</body>
</html>
<?php mysqli_close($conn); ?>
