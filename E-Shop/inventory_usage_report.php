<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get the date range for the report (default to last 30 days)
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : date('Y-m-d');

// Inventory Usage Query
$inventoryUsageQuery = "
    SELECT 
        p.product_id,
        p.name AS product_name,
        c.name AS category_name,
        p.quantity AS current_stock,
        p.min_stock_level,
        SUM(oi.quantity) AS total_items_sold,
        (SUM(oi.quantity) / p.quantity) * 100 AS usage_percentage
    FROM 
        products p
    LEFT JOIN 
        order_items oi ON p.product_id = oi.product_id
    LEFT JOIN 
        orders o ON oi.order_id = o.order_id
    LEFT JOIN 
        categories c ON p.category_id = c.category_id
    WHERE 
        o.order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY 
        p.product_id, p.name, c.name, p.quantity, p.min_stock_level
    ORDER BY 
        total_items_sold DESC
";

$inventoryUsageResult = mysqli_query($conn, $inventoryUsageQuery);

// Low Stock Products Query
$lowStockQuery = "
    SELECT 
        p.product_id,
        p.name AS product_name,
        c.name AS category_name,
        p.quantity AS current_stock,
        p.min_stock_level
    FROM 
        products p
    JOIN 
        categories c ON p.category_id = c.category_id
    WHERE 
        p.quantity <= p.min_stock_level
    ORDER BY 
        current_stock ASC
";

$lowStockResult = mysqli_query($conn, $lowStockQuery);

// Summary of Inventory
$inventorySummaryQuery = "
    SELECT 
        COUNT(*) AS total_products,
        SUM(quantity) AS total_stock,
        SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END) AS low_stock_products
    FROM 
        products
";
$inventorySummaryResult = mysqli_query($conn, $inventorySummaryQuery);
$inventorySummary = mysqli_fetch_assoc($inventorySummaryResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Usage Report</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .inventory-report-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .report-filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-filter-form label {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .report-filter-form input[type="date"] {
            padding: 8px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .report-filter-form input[type="submit"] {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .report-filter-form input[type="submit"]:hover {
            background-color: #FFD700;
        }

        .report-summary {
            background-color: var(--background-color);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .report-summary h3 {
            color: rgb(0, 0, 0);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-top: 0;
        }

        .report-summary p {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            color: var(--text-color);
        }

        .report-summary p span:first-child {
            font-weight: bold;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .report-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .report-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .report-table tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .low-stock-row {
            background-color: #ffcccc;
        }

        .summary-label {
            font-weight: bold;
            color: rgb(0, 0, 0);
        }

        .summary-value {
            color: #ffc107;
        }

        .back-to-dashboard {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Inventory Usage Report</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="inventory-report-container">
            <div class="report-header">
                <h2>Inventory Report from <?php echo $start_date; ?> to <?php echo $end_date; ?></h2>
                
                <form method="get" class="report-filter-form">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                    
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                    
                    <input type="submit" value="Generate Report">
                </form>
            </div>

            <div class="report-summary">
                <h3>Inventory Summary</h3>
                <p>
                    <span class="summary-label">Total Products:</span>
                    <span class="summary-value"><?php echo $inventorySummary['total_products']; ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Stock:</span>
                    <span class="summary-value"><?php echo $inventorySummary['total_stock']; ?></span>
                </p>
                <p>
                    <span class="summary-label">Low Stock Products:</span>
                    <span class="summary-value"><?php echo $inventorySummary['low_stock_products']; ?></span>
                </p>
            </div>

            <h3>Inventory Usage Details</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Stock Level</th>
                        <th>Total Items Sold</th>
                        <th>Usage Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($inventoryUsage = mysqli_fetch_assoc($inventoryUsageResult)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inventoryUsage['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($inventoryUsage['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($inventoryUsage['current_stock']); ?></td>
                        <td><?php echo htmlspecialchars($inventoryUsage['min_stock_level']); ?></td>
                        <td><?php echo htmlspecialchars($inventoryUsage['total_items_sold'] ?? '0'); ?></td>
                        <td><?php echo number_format($inventoryUsage['usage_percentage'] ?? 0, 2); ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <h3>Low Stock Products</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Stock Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($lowStock = mysqli_fetch_assoc($lowStockResult)): ?>
                    <tr class="low-stock-row">
                        <td><?php echo htmlspecialchars($lowStock['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($lowStock['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($lowStock['current_stock']); ?></td>
                        <td><?php echo htmlspecialchars($lowStock['min_stock_level']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="dashboard-actions text-center">
                <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
