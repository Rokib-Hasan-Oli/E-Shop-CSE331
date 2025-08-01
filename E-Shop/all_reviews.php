<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch all reviews by the customer
$reviews_sql = "SELECT r.rating, r.comment, r.created_at, 
                p.name as product_name, p.image_url
                FROM reviews r
                JOIN products p ON r.product_id = p.product_id
                WHERE r.customer_id = $user_id
                ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Reviews</title>
</head>
<body>
    <h2>My Reviews</h2>
    
    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
        <?php while($review = mysqli_fetch_assoc($reviews_result)): ?>
            <div>
                <h3><?php echo htmlspecialchars($review['product_name']); ?></h3>
                <p>Rating: <?php echo $review['rating']; ?>/5</p>
                <p>Comment: <?php echo htmlspecialchars($review['comment']); ?></p>
                <p>Date: <?php echo date('d M Y', strtotime($review['created_at'])); ?></p>
            </div>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No reviews submitted yet.</p>
    <?php endif; ?>

    <p><a href="customer_dashboard.php">Back to Dashboard</a></p>
</body>
</html>