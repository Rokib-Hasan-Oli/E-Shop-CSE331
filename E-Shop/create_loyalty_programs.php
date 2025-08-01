<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle adding points to a customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_points'])) {
    $customer_id = $_POST['customer_id'];
    $points_to_add = $_POST['points'];

    // Check if customer exists
    $checkCustomerQuery = "SELECT * FROM users WHERE user_id = ? AND role = 'customer'";
    $stmt = mysqli_prepare($conn, $checkCustomerQuery);
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $customerResult = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($customerResult) > 0) {
        // Check if loyalty points record exists, if not create one
        $checkLoyaltyQuery = "INSERT INTO loyalty_points (customer_id, points) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE points = points + ?";
        $stmt = mysqli_prepare($conn, $checkLoyaltyQuery);
        mysqli_stmt_bind_param($stmt, "iii", $customer_id, $points_to_add, $points_to_add);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Points added successfully!";
        } else {
            $error_message = "Error adding points: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Invalid customer ID";
    }
}

// Retrieve customers for dropdown
$customersQuery = "SELECT user_id, full_name, email FROM users WHERE role = 'customer'";
$customersResult = mysqli_query($conn, $customersQuery);

// Retrieve existing loyalty points
$loyaltyQuery = "
    SELECT 
        u.user_id, 
        u.full_name, 
        u.email, 
        COALESCE(lp.points, 0) as points 
    FROM 
        users u
    LEFT JOIN 
        loyalty_points lp ON u.user_id = lp.customer_id
    WHERE 
        u.role = 'customer'
    ORDER BY 
        points DESC
";
$loyaltyResult = mysqli_query($conn, $loyaltyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Points Management - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .loyalty-management-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .loyalty-points-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .loyalty-points-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .loyalty-points-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .loyalty-form {
            background-color: var(--background-color);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .loyalty-form label {
            display: block;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .loyalty-form select, 
        .loyalty-form input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .loyalty-form input[type="submit"] {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .loyalty-form input[type="submit"]:hover {
            background-color: var(--primary-color);
            color: var(--white);
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
            <h1 class="dashboard-title">Loyalty Points Management</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="loyalty-management-container">
            <?php 
            // Display validation errors, success, or error messages
            if (isset($error_message)) {
                echo "<div class='error-message'>" . $error_message . "</div>";
            }
            if (isset($success_message)) {
                echo "<div class='success-message'>" . $success_message . "</div>";
            }
            ?>

            <div class="loyalty-form">
                <h3>Add Loyalty Points</h3>
                <form method="post">
                    <label for="customer_id">Select Customer:</label>
                    <select name="customer_id" id="customer_id" required>
                        <option value="">Select a Customer</option>
                        <?php 
                        // Reset pointer for mysqli result
                        mysqli_data_seek($customersResult, 0);
                        while($customer = mysqli_fetch_assoc($customersResult)): ?>
                            <option value="<?php echo $customer['user_id']; ?>">
                                <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['email'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="points">Points to Add:</label>
                    <input type="number" name="points" id="points" min="1" required>

                    <input type="submit" name="add_points" value="Add Points">
                </form>
            </div>

            <h3>Loyalty Points Summary</h3>
            <table class="loyalty-points-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Loyalty Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($loyalty = mysqli_fetch_assoc($loyaltyResult)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loyalty['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($loyalty['email']); ?></td>
                            <td><?php echo $loyalty['points']; ?></td>
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
