<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get the date for the report (default to today if not specified)
$report_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : date('Y-m-d');

// Daily Sales Query
$dailySalesQuery = "
    SELECT 
        o.order_id,
        o.order_date,
        u.full_name AS customer_name,
        o.total_amount,
        o.payment_method,
        o.status,
        COUNT(oi.order_item_id) AS total_items
    FROM 
        orders o
    JOIN 
        users u ON o.customer_id = u.user_id
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    WHERE 
        DATE(o.order_date) = '$report_date'
    GROUP BY 
        o.order_id
    ORDER BY 
        o.order_date
";

$dailySalesResult = mysqli_query($conn, $dailySalesQuery);

// Total Sales Summary
$totalSalesSummaryQuery = "
    SELECT 
        COUNT(*) AS total_orders,
        SUM(total_amount) AS total_sales,
        SUM(COALESCE(discount, 0)) AS total_discounts,
        SUM(COALESCE(tax, 0)) AS total_tax
    FROM 
        orders
    WHERE 
        DATE(order_date) = '$report_date'
";

$totalSalesSummaryResult = mysqli_query($conn, $totalSalesSummaryQuery);
$salesSummary = mysqli_fetch_assoc($totalSalesSummaryResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report - <?php echo $report_date; ?></title>
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

        .order-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .order-status-pending {
            background-color: var(--neutral-color);
            color: var(--white);
        }

        .order-status-completed {
            background-color: var(--success-color);
            color: var(--white);
        }

        .order-status-cancelled {
            background-color: var(--danger-color);
            color: var(--white);
        }


        .summary-label {
            font-weight: bold;
            color:rgb(0, 0, 0);
        }

        .summary-value {
            color: #ffc107;
        }

        .back-to-dashboard {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-dashboard a {
            display: inline-block;
            background-color: var(--danger-color);
            color: var(--white);
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .back-to-dashboard a:hover {
            background-color: #D32F2F;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Daily Sales Report</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="sales-report-container">
            <div class="report-header">
                <h2>Sales Report for <?php echo $report_date; ?></h2>
                
                <form method="get" class="report-filter-form">
                    <label for="date">Select Date:</label>
                    <input type="date" name="date" id="date" value="<?php echo $report_date; ?>">
                    <input type="submit" value="Generate Report">
                </form>
            </div>

            <div class="report-summary">
                <h3>Sales Summary</h3>
                <p>
                    <span class="summary-label">Total Orders:</span>
                    <span class="summary-value"><?php echo $salesSummary['total_orders']; ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Sales:</span>
                    <span class="summary-value">$<?php echo number_format($salesSummary['total_sales'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Discounts:</span>
                    <span class="summary-value">$<?php echo number_format($salesSummary['total_discounts'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Tax:</span>
                    <span class="summary-value">$<?php echo number_format($salesSummary['total_tax'], 2); ?></span>
                </p>
            </div>

            <h3>Order Details</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Time</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Total Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = mysqli_fetch_assoc($dailySalesResult)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo date('H:i', strtotime($order['order_date'])); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td>
                            <span class="order-status order-status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($order['total_items']); ?></td>
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
