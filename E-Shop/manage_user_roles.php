<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);

    // Prevent changing role of the current admin user
    if ($user_id == $_SESSION['user_id']) {
        $error_message = "You cannot change your own role";
    } else {
        $query = "UPDATE users SET role = '$new_role' WHERE user_id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            $success_message = "User role updated successfully";
        } else {
            $error_message = "Error updating role: " . mysqli_error($conn);
        }
    }
}

// Fetch users with their current roles
$query = "SELECT user_id, username, email, full_name, role FROM users WHERE role != 'admin'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Roles - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        /* Additional specific styles for this page */
        .user-roles-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .user-roles-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-roles-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .user-roles-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .role-change-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .role-change-form select {
            padding: 5px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .role-change-form input[type="submit"] {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .role-change-form input[type="submit"]:hover {
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

        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .role-badge-seller {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .role-badge-customer {
            background-color: var(--neutral-color);
            color: var(--white);
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Manage User Roles</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="user-roles-container">
            <?php 
            // Display success or error messages
            if (isset($success_message)) {
                echo "<p class='success-message'>" . htmlspecialchars($success_message) . "</p>";
            }
            if (isset($error_message)) {
                echo "<p class='error-message'>" . htmlspecialchars($error_message) . "</p>";
            }
            ?>

            <table class="user-roles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Current Role</th>
                        <th>Change Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td>
                            <span class="role-badge <?php 
                                echo $row['role'] == 'seller' ? 'role-badge-seller' : 'role-badge-customer'; 
                            ?>">
                                <?php echo htmlspecialchars($row['role']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" class="role-change-form">
                                <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                <select name="new_role">
                                    <option value="seller" <?php echo $row['role'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                                    <option value="customer" <?php echo $row['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                </select>
                                <input type="submit" name="change_role" value="Change Role">
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <p class="text-center">
            <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
        </p>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
