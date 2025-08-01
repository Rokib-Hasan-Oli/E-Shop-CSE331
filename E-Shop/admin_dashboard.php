<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Function to get quick statistics for each feature section
function getAdminDashboardStats($conn) {
    $stats = [];

    // Users Statistics
    $userQuery = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) as seller_users,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customer_users
    FROM users";
    $userResult = mysqli_query($conn, $userQuery);
    $stats['users'] = mysqli_fetch_assoc($userResult);

    // Products Statistics
    $productQuery = "SELECT 
        COUNT(*) as total_products,
        COUNT(DISTINCT category_id) as total_categories,
        SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_products
    FROM products";
    $productResult = mysqli_query($conn, $productQuery);
    $stats['products'] = mysqli_fetch_assoc($productResult);

    // Suppliers Statistics
    $supplierQuery = "SELECT 
        COUNT(*) as total_suppliers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_suppliers
    FROM suppliers";
    $supplierResult = mysqli_query($conn, $supplierQuery);
    $stats['suppliers'] = mysqli_fetch_assoc($supplierResult);

    // Purchase Orders Statistics
    $purchaseQuery = "SELECT 
        COUNT(*) as total_purchase_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_orders
    FROM purchase_orders";
    $purchaseResult = mysqli_query($conn, $purchaseQuery);
    $stats['purchase_orders'] = mysqli_fetch_assoc($purchaseResult);

    // Sales Reports Statistics
    $salesQuery = "SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        SUM(CASE WHEN MONTH(order_date) = MONTH(CURRENT_DATE()) THEN total_amount ELSE 0 END) as current_month_sales
    FROM orders";
    $salesResult = mysqli_query($conn, $salesQuery);
    $stats['sales'] = mysqli_fetch_assoc($salesResult);

    // Promotions Statistics
    $promotionQuery = "SELECT 
        COUNT(*) as total_promotions,
        SUM(CASE WHEN CURRENT_DATE BETWEEN start_date AND end_date THEN 1 ELSE 0 END) as active_promotions
    FROM promotions";
    $promotionResult = mysqli_query($conn, $promotionQuery);
    $stats['promotions'] = mysqli_fetch_assoc($promotionResult);

    return $stats;
}

$dashboardStats = getAdminDashboardStats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <img src="Logo/logo.png" alt="Company Logo" class="dashboard-logo">
            <h1 class="dashboard-title">Admin Dashboard</h1>
        </div>

        <p class="dashboard-welcome">Welcome, <?php echo $_SESSION['username']; ?>!</p>
        
        <div class="dashboard-sections">
            <div class="dashboard-section">
                <h3>Manage Users</h3>
                <p>Total Users: <?php echo $dashboardStats['users']['total_users']; ?></p>
                <p>Admin Users: <?php echo $dashboardStats['users']['admin_users']; ?></p>
                <p>Seller Users: <?php echo $dashboardStats['users']['seller_users']; ?></p>
                <p>Customer Users: <?php echo $dashboardStats['users']['customer_users']; ?></p>
                <ul>
                    <li><a href="add_edit_users.php">Add/Edit/Delete Users</a></li>
                    <li><a href="manage_user_roles.php">Manage User Roles</a></li>
                </ul>
            </div>

            <div class="dashboard-section">
                <h3>Manage Products</h3>
                <p>Total Products: <?php echo $dashboardStats['products']['total_products']; ?></p>
                <p>Product Categories: <?php echo $dashboardStats['products']['total_categories']; ?></p>
                <p>Low Stock Products: <?php echo $dashboardStats['products']['low_stock_products']; ?></p>
                <ul>
                    <li><a href="add_update_delete_products.php">Add/Update/Delete Products</a></li>
                    <li><a href="track_stock_levels.php">Track Stock Levels</a></li>
                    <li><a href="manage_product_categories.php">Manage Product Categories</a></li>
                </ul>
            </div>

            <div class="dashboard-section">
                <h3>Manage Suppliers</h3>
                <p>Total Suppliers: <?php echo $dashboardStats['suppliers']['total_suppliers']; ?></p>
                <p>Active Suppliers: <?php echo $dashboardStats['suppliers']['active_suppliers']; ?></p>
                <ul>
                    <li><a href="add_update_suppliers.php">Add/Update Supplier Information</a></li>
                    <li><a href="track_product_procurement.php">Track Product Procurement</a></li>
                </ul>
            </div>

            <div class="dashboard-section">
                <h3>Purchase Orders</h3>
                <p>Total Purchase Orders: <?php echo $dashboardStats['purchase_orders']['total_purchase_orders']; ?></p>
                <p>Pending Orders: <?php echo $dashboardStats['purchase_orders']['pending_orders']; ?></p>
                <p>Received Orders: <?php echo $dashboardStats['purchase_orders']['received_orders']; ?></p>
                <ul>
                    <li><a href="create_purchase_orders.php">Create Purchase Orders</a></li>
                    <li><a href="track_order_status.php">Track Order Status</a></li>
                </ul>
            </div>

            <div class="dashboard-section">
                <h3>Reporting & Analytics</h3>
                <p>Total Orders: <?php echo $dashboardStats['sales']['total_orders']; ?></p>
                <p>Total Sales: $<?php echo number_format($dashboardStats['sales']['total_sales'], 2); ?></p>
                <p>Current Month Sales: $<?php echo number_format($dashboardStats['sales']['current_month_sales'], 2); ?></p>
                <ul>
                    <li><a href="daily_sales_report.php">Daily Sales Report</a></li>
                    <li><a href="weekly_sales_report.php">Weekly Sales Report</a></li>
                    <li><a href="monthly_sales_report.php">Monthly Sales Report</a></li>
                    <li><a href="inventory_usage_report.php">Inventory Usage Reports</a></li>
                    <li><a href="profit_loss_analysis.php">Profit and Loss Analysis</a></li>
                </ul>
            </div>

           <!-- <div class="dashboard-section">
                <h3>Promotions</h3>
                <p>Total Promotions: <?php echo $dashboardStats['promotions']['total_promotions']; ?></p>
                <p>Active Promotions: <?php echo $dashboardStats['promotions']['active_promotions']; ?></p>
                <ul>
                    <li><a href="create_loyalty_programs.php">Create Loyalty Programs</a></li>
                    <li><a href="manage_discount_campaigns.php">Manage Discount Campaigns</a></li>
                </ul>
            </div>-->
        </div> 
        
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</body>
</html>
