<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add or Update User
    if (isset($_POST['action']) && ($_POST['action'] == 'add' || $_POST['action'] == 'update')) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        // Check if adding or updating
        if ($_POST['action'] == 'add') {
            // Hash password
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (username, password, email, full_name, role, phone, address) 
                      VALUES ('$username', '$password', '$email', '$full_name', '$role', '$phone', '$address')";
        } else {
            // Update user
            $user_id = intval($_POST['user_id']);
            
            // Check if password is being updated
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query = "UPDATE users SET 
                          username = '$username', 
                          password = '$password', 
                          email = '$email', 
                          full_name = '$full_name', 
                          role = '$role', 
                          phone = '$phone', 
                          address = '$address' 
                          WHERE user_id = $user_id";
            } else {
                $query = "UPDATE users SET 
                          username = '$username', 
                          email = '$email', 
                          full_name = '$full_name', 
                          role = '$role', 
                          phone = '$phone', 
                          address = '$address' 
                          WHERE user_id = $user_id";
            }
        }

        if (mysqli_query($conn, $query)) {
            $success_message = ($_POST['action'] == 'add') ? "User added successfully" : "User updated successfully";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }

    // Delete User
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $user_id = intval($_POST['user_id']);
        $query = "DELETE FROM users WHERE user_id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            $success_message = "User deleted successfully";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch users for display
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        /* Additional specific styles for this page */
        .user-management-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .user-form {
            background-color: var(--background-color);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .user-form label {
            display: block;
            margin: 10px 0 5px;
            color: var(--primary-color);
        }

        .user-form input, 
        .user-form select, 
        .user-form textarea {
            width: 98%;
            padding: 8px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .user-form input[type="submit"] {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .user-form input[type="submit"]:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .user-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-list-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .user-list-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons button,
        .action-buttons input[type="submit"] {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .edit-btn {
            background-color: var(--accent-color);
            color: var(--primary-color);
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
            <h1 class="dashboard-title">User Management</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="user-management-container">
            <?php 
            // Display validation errors
            if (!empty($errors)) {
                echo "<div class='error-message'>";
                foreach ($errors as $error) {
                    echo "<p>" . htmlspecialchars($error) . "</p>";
                }
                echo "</div>";
            }

            // Display success or error messages
            if (isset($success_message)) {
                echo "<p class='success-message'>" . htmlspecialchars($success_message) . "</p>";
            }
            if (isset($error_message)) {
                echo "<p class='error-message'>" . htmlspecialchars($error_message) . "</p>";
            }
            ?>

            <form method="post" action="" class="user-form">
                <input type="hidden" name="user_id" id="user_id">
                <input type="hidden" name="action" id="form_action" value="add">
                
                <label for="username">Username: <span class="text-danger">*</span></label>
                <input type="text" name="username" id="username" required>
                
                <label for="email">Email: <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" required>
                
                <label for="full_name">Full Name: <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="full_name" required>
                
                <label for="password">Password: <?php echo isset($_POST['action']) && $_POST['action'] == 'update' ? '(Leave blank to keep current password)' : '<span class="text-danger">*</span>'; ?></label>
                <input type="password" name="password" id="password" <?php echo isset($_POST['action']) && $_POST['action'] == 'add' ? 'required' : ''; ?>>
                
                <label for="role">Role: <span class="text-danger">*</span></label>
                <select name="role" id="role" required>
                    <option value="admin">Admin</option>
                    <option value="seller">Seller</option>
                    <option value="customer">Customer</option>
                </select>
                
                <label for="phone">Phone:</label>
                <input type="text" name="phone" id="phone">
                
                <label for="address">Address:</label>
                <textarea name="address" id="address"></textarea>
                
                <input type="submit" value="Add User">
            </form>

            <h3>User List</h3>
            <table class="user-list-table">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td class="action-buttons">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user?');">
                        </form>
                        <button class="edit-btn" onclick="editUser(
                            '<?php echo $row['user_id']; ?>',
                            '<?php echo htmlspecialchars($row['username']); ?>',
                            '<?php echo htmlspecialchars($row['email']); ?>',
                            '<?php echo htmlspecialchars($row['full_name']); ?>',
                            '<?php echo $row['role']; ?>',
                            '<?php echo htmlspecialchars($row['phone']); ?>',
                            '<?php echo htmlspecialchars($row['address']); ?>'
                        )">Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <p class="text-center">
            <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
        </p>
    </div>

    <script>
    function editUser(id, username, email, full_name, role, phone, address) {
        document.getElementById('user_id').value = id;
        document.getElementById('username').value = username;
        document.getElementById('email').value = email;
        document.getElementById('full_name').value = full_name;
        document.getElementById('role').value = role;
        document.getElementById('phone').value = phone;
        document.getElementById('address').value = address;
        
        document.getElementById('form_action').value = 'update';
        document.querySelector('input[type="submit"]').value = 'Update User';
    }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
