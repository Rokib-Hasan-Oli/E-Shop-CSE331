<?php
session_start();
include 'db_connect.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Validate input
    if ($password != $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if username exists
        $check_sql = "SELECT * FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_sql);
        
        // Check if email exists
        $email_check_sql = "SELECT * FROM users WHERE email = '$email'";
        $email_check_result = mysqli_query($conn, $email_check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Username already exists";
        } else if (mysqli_num_rows($email_check_result) > 0) {
            $error_message = "Email already exists";
        } else {
            // Don't allow registration as admin from signup form
            if ($role == 'admin') {
                $error_message = "Cannot register as admin";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_sql = "INSERT INTO users (username, password, email, full_name, role, phone, address) 
                               VALUES ('$username', '$hashed_password', '$email', '$full_name', '$role', '$phone', '$address')";
                
                if (mysqli_query($conn, $insert_sql)) {
                    $success_message = "Registration successful. You can now login.";
                    
                    // If user is a customer, create a loyalty points entry
                    if ($role == 'customer') {
                        $user_id = mysqli_insert_id($conn);
                        $loyalty_sql = "INSERT INTO loyalty_points (customer_id, points) VALUES ($user_id, 0)";
                        mysqli_query($conn, $loyalty_sql);
                    }
                } else {
                    $error_message = "Registration failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shop - Sign Up</title>
    <link rel="stylesheet" href="LSF_Styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="Logo/logo.png" alt="E-Shop Logo">
            <h2>Create Your Account</h2>
        </div>
        
        <?php if (!empty($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>
        
        <?php if (!empty($success_message)) { ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php } ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="customer">Customer</option>
                    <option value="seller">Seller</option>
                </select>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" value="Sign Up">
            </div>
        </form>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
