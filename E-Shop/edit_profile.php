<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch current user details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_details = mysqli_fetch_assoc($user_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Update profile query
    $update_sql = "UPDATE users SET 
                   full_name = '$full_name', 
                   phone = '$phone', 
                   address = '$address' 
                   WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $success_message = "Profile updated successfully!";
        // Refresh user details
        $user_result = mysqli_query($conn, $user_sql);
        $user_details = mysqli_fetch_assoc($user_result);
    } else {
        $error_message = "Error updating profile: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="Customer_Styles.css">
</head>
<body>
    <div class="customer-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h1 class="dashboard-title">Edit Profile</h1>
        </div>

        <div id="account-info">
            <?php if (isset($error_message)): ?>
                <p class="text-danger"><?php echo $error_message; ?></p>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <p class="text-success"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <form method="post" action="">
                <div>
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user_details['full_name']); ?>" required>
                </div>
                <div>
                    <label for="email">Email (cannot be changed):</label>
                    <input type="email" id="email" 
                           value="<?php echo htmlspecialchars($user_details['email']); ?>" readonly>
                </div>
                <div>
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user_details['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label for="address">Address:</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($user_details['address'] ?? ''); ?></textarea>
                </div>
                <div>
                    <button type="submit" class="submit-button">Update Profile</button>
                </div>
            </form>
        </div>

        <div id="quick-navigation">
            <h3>Quick Navigation</h3>
            <ul>
                <li><a href="customer_dashboard.php">Back to Dashboard</a></li>
                <li><a href="browse_products.php">Browse Products</a></li>
                <li><a href="order_history.php">Order History</a></li>
            </ul>
        </div>

        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <style>
        /* Additional form-specific styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form div {
            display: flex;
            flex-direction: column;
        }

        form label {
            margin-bottom: 5px;
            color: var(--primary-color);
            font-weight: bold;
        }

        form input, 
        form textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .submit-button {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }

        .submit-button:hover {
            background-color: var(--accent-color);
        }

        input[readonly] {
            background-color: #f4f4f4;
            cursor: not-allowed;
        }
    </style>
</body>
</html>
