<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch products with stock information
$query = "SELECT 
    p.product_id, 
    p.name, 
    p.quantity, 
    p.min_stock_level, 
    c.name as category_name,
    s.name as supplier_name,
    CASE 
        WHEN p.quantity <= p.min_stock_level THEN 'Low Stock'
        WHEN p.quantity <= (p.min_stock_level * 1.5) THEN 'Warning'
        ELSE 'Adequate'
    END as stock_status
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
ORDER BY stock_status, p.quantity";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Stock Levels | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .stock-table th, .stock-table td {
            border: 1px solid var(--neutral-color);
            padding: 10px;
            text-align: left;
        }
        .stock-table th {
            background-color: var(--primary-color);
            color: var(--white);
        }
        .stock-status-low {
            background-color: var(--danger-color);
            color: var(--white);
        }
        .stock-status-warning {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        .restock-link {
            display: inline-block;
            background-color: var(--success-color);
            color: var(--white);
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .restock-link:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Stock Levels Tracking</h1>
        </div>

        <div class="dashboard-section">
            <h3>Current Stock Levels</h3>
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Current Quantity</th>
                        <th>Minimum Stock Level</th>
                        <th>Stock Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="<?php 
                        if ($row['stock_status'] == 'Low Stock') echo 'stock-status-low';
                        elseif ($row['stock_status'] == 'Warning') echo 'stock-status-warning';
                    ?>">
                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['min_stock_level']); ?></td>
                        <td><?php echo htmlspecialchars($row['stock_status']); ?></td>
                        <td>
                            <a href="create_purchase_orders.php?product_id=<?php echo urlencode($row['product_id']); ?>" 
                               class="restock-link">
                                Restock
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="dashboard-section">
            <h3>Stock Status Explanation</h3>
            <ul>
                <li class="text-danger">Low Stock: Quantity is at or below minimum stock level</li>
                <li class="text-neutral">Warning: Quantity is between minimum stock level and 1.5x minimum stock level</li>
                <li class="text-success">Adequate: Sufficient stock available</li>
            </ul>
        </div>

        <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
