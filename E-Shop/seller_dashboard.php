<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch seller information
$seller_id = $_SESSION['user_id'];
$seller_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($seller_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller_result = $stmt->get_result();
$seller_info = $seller_result->fetch_assoc();

// Close the statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="Seller_Styles.css">
</head>
<body>
    <div class="seller-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h2 class="dashboard-title">Seller Dashboard</h2>
        </div>
        
        <div class="dashboard-welcome">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        
        <!-- Seller Information Section -->
        <div class="seller-info-section">
            <h3>Seller Profile</h3>
            <div class="seller-details">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($seller_info['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($seller_info['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($seller_info['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($seller_info['address'] ?? 'Not provided'); ?></p>
            </div>
        </div>
        
        <div class="navigation-section">
            <h3>Quick Navigation</h3>
            <ul>
               <!-- <li><a href="process_sale.php">Process Sale</a></li>-->
                <li><a href="add_product.php">Add Products</a></li>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="view_orders.php">View Orders</a></li>
            </ul>
        </div>
        
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</body>
</html>
