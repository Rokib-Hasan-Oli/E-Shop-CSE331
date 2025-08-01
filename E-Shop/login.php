<?php
session_start();
include 'db_connect.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Check if user exists
    $sql = "SELECT user_id, username, password, role FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Admin has a fixed password in the database: CSE311.3E-Shop
        if ($row['role'] == 'admin' && $password == 'CSE311.3E-Shop') {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            // Update last_login timestamp
            $update_login = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ".$row['user_id'];
            mysqli_query($conn, $update_login);
            
            header("Location: admin_dashboard.php");
            exit();
        } 
        // For other roles, verify password normally
        else if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            // Update last_login timestamp
            $update_login = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ".$row['user_id'];
            mysqli_query($conn, $update_login);
            
            // Redirect based on role
            if ($row['role'] == 'seller') {
                header("Location: seller_dashboard.php");
            } else {
                header("Location: customer_dashboard.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password";
        }
    } else {
        $error_message = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shop - Login</title>
    <link rel="stylesheet" href="LSF_Styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="Logo/logo.png" alt="E-Shop Logo">
            <h2>Login to E-Shop</h2>
        </div>
        
        <?php if (!empty($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" value="Login">
            </div>
        </form>
        
        <div class="links">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
        </div>
    </div>
</body>
</html>
