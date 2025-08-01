<?php
session_start();
include 'db_connect.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email_submit'])) {
        // Step 1: Email verification
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Check if email exists
        $sql = "SELECT user_id, username, email FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $user_id = $row['user_id'];
            
            // Generate a reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token in session (in a real application, you would store this in a database)
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_expiry'] = $expiry;
            $_SESSION['user_id'] = $user_id;
            
            // For this demo, show token directly (in a real app, you would email it)
            $success_message = "A password reset link has been sent to your email. For demonstration purposes, your token is: " . $token;
        } else {
            $error_message = "Email not found";
        }
    } else if (isset($_POST['reset_submit'])) {
        // Step 2: Reset password
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify token (in a real app, you would check against database)
        if (
            isset($_SESSION['reset_token']) && 
            $token === $_SESSION['reset_token'] && 
            isset($_SESSION['reset_expiry']) && 
            strtotime($_SESSION['reset_expiry']) > time()
        ) {
            if ($new_password === $confirm_password) {
                $user_id = $_SESSION['user_id'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_sql = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
                
                if (mysqli_query($conn, $update_sql)) {
                    // Clear reset session variables
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_expiry']);
                    unset($_SESSION['user_id']);
                    
                    $success_message = "Password has been reset successfully. You can now <a href='login.php'>login</a>.";
                } else {
                    $error_message = "Error updating password: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Passwords do not match";
            }
        } else {
            $error_message = "Invalid or expired token";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shop - Forgot Password</title>
    <link rel="stylesheet" href="LSF_Styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="Logo/logo.png" alt="E-Shop Logo">
            <h2>Reset Your Password</h2>
        </div>
        
        <?php if (!empty($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>
        
        <?php if (!empty($success_message)) { ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php } ?>
        
        <?php if (empty($success_message) || strpos($success_message, "has been sent to your email") !== false) { ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <input type="submit" name="email_submit" value="Send Reset Link">
                </div>
            </form>
        <?php } ?>
        
        <?php if (!empty($_SESSION['reset_token'])) { ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="token">Reset Token:</label>
                    <input type="text" id="token" name="token" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <input type="submit" name="reset_submit" value="Reset Password">
                </div>
            </form>
        <?php } ?>
        
        <div class="links">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
