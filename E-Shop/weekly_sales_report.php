<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get the week for the report (default to current week if not specified)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('this week'));
$end_date = date('Y-m-d', strtotime($start_date . ' + 6 days'));

// Weekly Sales by Day Query
$weeklySalesQuery = "
    SELECT 
        DATE(order_date) AS sales_date,
        COUNT(*) AS total_orders,
        SUM(total_amount) AS total_sales,
        SUM(discount) AS total_discounts,
        SUM(tax) AS total_tax
    FROM 
        orders
    WHERE 
        DATE(order_date) BETWEEN '$start_date' AND '$end_date'
    GROUP BY 
        DATE(order_date)
    ORDER BY 
        sales_date
";

$weeklySalesResult = mysqli_query($conn, $weeklySalesQuery);

// Total Weekly Sales Summary
$totalWeeklySalesQuery = "
    SELECT 
        COUNT(*) AS total_orders,
        SUM(total_amount) AS total_sales,
        SUM(discount) AS total_discounts,
        SUM(tax) AS total_tax
    FROM 
        orders
    WHERE 
        DATE(order_date) BETWEEN '$start_date' AND '$end_date'
";

$totalWeeklySalesResult = mysqli_query($conn, $totalWeeklySalesQuery);
$weeklySalesSummary = mysqli_fetch_assoc($totalWeeklySalesResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Sales Report - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .sales-management-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .sales-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sales-list-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .sales-list-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .sales-report-filter {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            background-color: var(--background-color);
            padding: 15px;
            border-radius: 6px;
        }

        .sales-report-filter form {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sales-report-filter input[type="date"] {
            padding: 8px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .sales-report-filter input[type="submit"] {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sales-report-filter input[type="submit"]:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .report-summary {
            background-color: var(--background-color);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .report-summary h3 {
            color: rgb(0, 0, 0);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-top: 0;
        }

        .report-summary p {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }

        .summary-label {
            font-weight: bold;
            color:rgb(0, 0, 0);
        }

        .summary-value {
            color: #ffc107;
        }

        .total-highlight {
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Weekly Sales Report</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="sales-management-container">
            <div class="sales-report-filter">
                <form method="get">
                    <label for="start_date">Select Week Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                    <input type="submit" value="Generate Report">
                </form>
            </div>

            <div class="report-summary">
                <h3>Weekly Sales Summary (<?php echo $start_date . ' to ' . $end_date; ?>)</h3>
                <p>
                    <span class="summary-label">Total Orders:</span>
                    <span class="summary-value total-highlight"><?php echo $weeklySalesSummary['total_orders']; ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Sales:</span>
                    <span class="summary-value total-highlight">$<?php echo number_format($weeklySalesSummary['total_sales'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Discounts:</span>
                    <span class="summary-value">$<?php echo number_format($weeklySalesSummary['total_discounts'], 2); ?></span>
                </p>
                <p>
                    <span class="summary-label">Total Tax:</span>
                    <span class="summary-value">$<?php echo number_format($weeklySalesSummary['total_tax'], 2); ?></span>
                </p>
            </div>

            <h3>Daily Sales Breakdown</h3>
            <table class="sales-list-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Orders</th>
                        <th>Total Sales</th>
                        <th>Total Discounts</th>
                        <th>Total Tax</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Reset the result pointer
                    mysqli_data_seek($weeklySalesResult, 0);
                    while($dailySales = mysqli_fetch_assoc($weeklySalesResult)): 
                    ?>
                    <tr>
                        <td><?php echo $dailySales['sales_date']; ?></td>
                        <td><?php echo $dailySales['total_orders']; ?></td>
                        <td>$<?php echo number_format($dailySales['total_sales'], 2); ?></td>
                        <td>$<?php echo number_format($dailySales['total_discounts'], 2); ?></td>
                        <td>$<?php echo number_format($dailySales['total_tax'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="dashboard-actions text-center">
            <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
