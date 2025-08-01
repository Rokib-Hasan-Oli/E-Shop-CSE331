<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get the month for the report (default to current month if not specified)
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Monthly Sales by Category Query
$monthlySalesByCategoryQuery = "
    SELECT 
        c.name AS category_name,
        COUNT(oi.order_item_id) AS total_items_sold,
        SUM(oi.subtotal) AS total_category_sales
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    JOIN 
        products p ON oi.product_id = p.product_id
    JOIN 
        categories c ON p.category_id = c.category_id
    WHERE 
        DATE_FORMAT(o.order_date, '%Y-%m') = '$selected_month'
    GROUP BY 
        c.category_id, c.name
    ORDER BY 
        total_category_sales DESC
";

$monthlySalesByCategoryResult = mysqli_query($conn, $monthlySalesByCategoryQuery);

// Total Monthly Sales Summary
$totalMonthlySalesQuery = "
    SELECT 
        COUNT(*) AS total_orders,
        SUM(total_amount) AS total_sales,
        SUM(COALESCE(discount, 0)) AS total_discounts,
        SUM(COALESCE(tax, 0)) AS total_tax
    FROM 
        orders
    WHERE 
        DATE_FORMAT(order_date, '%Y-%m') = '$selected_month'
";

$totalMonthlySalesResult = mysqli_query($conn, $totalMonthlySalesQuery);
$monthlySalesSummary = mysqli_fetch_assoc($totalMonthlySalesResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Sales Report - <?php echo date('F Y', strtotime($selected_month . '-01')); ?></title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .sales-report-container {
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

        .report-filter-form input[type="month"] {
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

        .summary-label {
            font-weight: bold;
            color: rgb(0, 0, 0);
        }

        .summary-value {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Monthly Sales Report</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="sales-report-container">
            <div class="report-header">
                <h2>Sales Report for <?php echo date('F Y', strtotime($selected_month . '-01')); ?></h2>
                
                <form method="get" class="report-filter-form">
                    <label for="month">Select Month:</label>
                    <input type="month" name="month" id="month" value="<?php echo $selected_month; ?>">
                    <input type="submit" value="Generate Report">
                </form>
            </div>

            <div class="report-summary">
                <h3>Sales Summary</h3>
                <p>
                    <span class="summary-label">Total Orders:</span>
                    <span class="summary-value"><?php echo $monthlySalesSummary['total_orders'] ?? 0; ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Sales:</span>
                    <span class="summary-value">$<?php echo number_format($monthlySalesSummary['total_sales'] ?? 0, 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Discounts:</span>
                    <span class="summary-value">$<?php echo number_format($monthlySalesSummary['total_discounts'] ?? 0, 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Tax:</span>
                    <span class="summary-value">$<?php echo number_format($monthlySalesSummary['total_tax'] ?? 0, 2); ?></span>
                </p>
            </div>

            <h3>Sales by Product Category</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Items Sold</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($monthlySalesByCategoryResult) > 0):
                        while($categorySales = mysqli_fetch_assoc($monthlySalesByCategoryResult)): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($categorySales['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($categorySales['total_items_sold']); ?></td>
                        <td>$<?php echo number_format($categorySales['total_category_sales'], 2); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="3" class="text-center">No sales data available for the selected month</td>
                    </tr>
                    <?php endif; ?>
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
