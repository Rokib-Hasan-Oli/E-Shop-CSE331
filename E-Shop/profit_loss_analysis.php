<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get the date range for the analysis (default to current year)
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : date('Y-01-01');
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : date('Y-12-31');

// Revenue Analysis Query (Fixed ambiguous column issue)
$revenueQuery = "
    SELECT 
        DATE_FORMAT(o.order_date, '%Y-%m') AS month,
        SUM(o.total_amount) AS total_revenue,
        SUM(p.cost_price * oi.quantity) AS total_cost,
        SUM(o.total_amount - (p.cost_price * oi.quantity)) AS gross_profit
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    JOIN 
        products p ON oi.product_id = p.product_id
    WHERE 
        o.order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY 
        month
    ORDER BY 
        month
";

$revenueResult = mysqli_query($conn, $revenueQuery);

// Total Expenses Query (Purchase Orders)
$expensesQuery = "
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') AS month,
        SUM(total_amount) AS total_purchase_expenses
    FROM 
        purchase_orders
    WHERE 
        order_date BETWEEN '$start_date' AND '$end_date'
        AND status = 'received'
    GROUP BY 
        month
    ORDER BY 
        month
";

$expensesResult = mysqli_query($conn, $expensesQuery);

// Prepare expense data for comparison
$expensesData = [];
while($expense = mysqli_fetch_assoc($expensesResult)) {
    $expensesData[$expense['month']] = $expense['total_purchase_expenses'];
}

// Calculate Profit/Loss
$totalRevenueQuery = "
    SELECT 
        SUM(o.total_amount) AS total_revenue,
        SUM(p.cost_price * oi.quantity) AS total_cost,
        SUM(o.total_amount - (p.cost_price * oi.quantity)) AS gross_profit
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    JOIN 
        products p ON oi.product_id = p.product_id
    WHERE 
        o.order_date BETWEEN '$start_date' AND '$end_date'
";

$totalRevenueResult = mysqli_query($conn, $totalRevenueQuery);
$totalRevenue = mysqli_fetch_assoc($totalRevenueResult);

$totalExpensesQuery = "
    SELECT 
        SUM(total_amount) AS total_purchase_expenses
    FROM 
        purchase_orders
    WHERE 
        order_date BETWEEN '$start_date' AND '$end_date'
        AND status = 'received'
";

$totalExpensesResult = mysqli_query($conn, $totalExpensesQuery);
$totalExpenses = mysqli_fetch_assoc($totalExpensesResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit and Loss Analysis</title>
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

        .profit-row {
            color: var(--success-color);
        }

        .loss-row {
            color: var(--danger-color);
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
            <h1 class="dashboard-title">Profit and Loss Analysis</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="inventory-report-container">
            <div class="report-header">
                <h2>Financial Analysis from <?php echo $start_date; ?> to <?php echo $end_date; ?></h2>
                
                <form method="get" class="report-filter-form">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                    
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                    
                    <input type="submit" value="Generate Report">
                </form>
            </div>

            <div class="report-summary">
                <h3>Overall Financial Summary</h3>
                <p>
                    <span class="summary-label">Total Revenue:</span>
                    <span class="summary-value">$<?php echo number_format($totalRevenue['total_revenue'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Cost of Goods Sold:</span>
                    <span class="summary-value">$<?php echo number_format($totalRevenue['total_cost'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Gross Profit:</span>
                    <span class="summary-value">$<?php echo number_format($totalRevenue['gross_profit'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Purchase Expenses:</span>
                    <span class="summary-value">$<?php echo number_format($totalExpenses['total_purchase_expenses'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Net Profit/Loss:</span>
                    <span class="summary-value <?php echo $totalRevenue['gross_profit'] - $totalExpenses['total_purchase_expenses'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        $<?php echo number_format($totalRevenue['gross_profit'] - $totalExpenses['total_purchase_expenses'], 2); ?>
                    </span>
                </p>
            </div>

            <h3>Monthly Breakdown</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Revenue</th>
                        <th>Cost of Goods Sold</th>
                        <th>Gross Profit</th>
                        <th>Purchase Expenses</th>
                        <th>Net Profit/Loss</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($revenueResult, 0); // Reset result pointer
                    while($revenue = mysqli_fetch_assoc($revenueResult)): 
                        $month = $revenue['month'];
                        $monthlyExpenses = isset($expensesData[$month]) ? $expensesData[$month] : 0;
                        $netProfit = $revenue['gross_profit'] - $monthlyExpenses;
                    ?>
                    <tr class="<?php echo $netProfit >= 0 ? 'profit-row' : 'loss-row'; ?>">
                        <td><?php echo $month; ?></td>
                        <td>$<?php echo number_format($revenue['total_revenue'], 2); ?></td>
                        <td>$<?php echo number_format($revenue['total_cost'], 2); ?></td>
                        <td>$<?php echo number_format($revenue['gross_profit'], 2); ?></td>
                        <td>$<?php echo number_format($monthlyExpenses, 2); ?></td>
                        <td>$<?php echo number_format($netProfit, 2); ?></td>
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
