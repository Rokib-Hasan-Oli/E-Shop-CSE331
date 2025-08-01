<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle creating a new promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_promotion'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $discount_type = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    $createPromotionQuery = "
        INSERT INTO promotions (
            name, 
            description, 
            discount_type, 
            discount_value, 
            start_date, 
            end_date
        ) VALUES ('$name', '$description', '$discount_type', $discount_value, '$start_date', '$end_date')
    ";

    if (mysqli_query($conn, $createPromotionQuery)) {
        $success_message = "Promotion created successfully!";
    } else {
        $error_message = "Error creating promotion: " . mysqli_error($conn);
    }
}

// Handle deleting a promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_promotion'])) {
    $promotion_id = intval($_POST['promotion_id']);

    $deletePromotionQuery = "DELETE FROM promotions WHERE promotion_id = $promotion_id";

    if (mysqli_query($conn, $deletePromotionQuery)) {
        $success_message = "Promotion deleted successfully!";
    } else {
        $error_message = "Error deleting promotion: " . mysqli_error($conn);
    }
}

// Retrieve existing promotions
$promotionsQuery = "
    SELECT 
        promotion_id, 
        name, 
        description, 
        discount_type, 
        discount_value, 
        start_date, 
        end_date,
        CASE 
            WHEN CURRENT_DATE BETWEEN start_date AND end_date THEN 'active'
            WHEN CURRENT_DATE < start_date THEN 'upcoming'
            ELSE 'expired'
        END AS status
    FROM 
        promotions
    ORDER BY 
        start_date DESC
";
$promotionsResult = mysqli_query($conn, $promotionsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Campaigns Management - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .discount-management-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .promotion-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .promotion-list-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .promotion-list-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .promotion-form {
            background-color: var(--background-color);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .promotion-form label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .promotion-form input,
        .promotion-form select,
        .promotion-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-badge-active {
            background-color: var(--success-color);
            color: var(--white);
        }

        .status-badge-upcoming {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .status-badge-expired {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: var(--white);
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .delete-btn:hover {
            background-color: #D32F2F;
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
            <h1 class="dashboard-title">Discount Campaigns Management</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="discount-management-container">
            <?php 
            // Display validation errors, success, or error messages
            if (isset($error_message)) {
                echo "<div class='error-message'>" . $error_message . "</div>";
            }
            if (isset($success_message)) {
                echo "<div class='success-message'>" . $success_message . "</div>";
            }
            ?>

            <div class="promotion-form">
                <h3>Create New Promotion</h3>
                <form method="post">
                    <label for="name">Promotion Name:</label>
                    <input type="text" name="name" id="name" required>

                    <label for="description">Description:</label>
                    <textarea name="description" id="description" required></textarea>

                    <label for="discount_type">Discount Type:</label>
                    <select name="discount_type" id="discount_type" required>
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>

                    <label for="discount_value">Discount Value:</label>
                    <input type="number" name="discount_value" id="discount_value" step="0.01" min="0" required>

                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" required>

                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" required>

                    <input type="submit" name="create_promotion" value="Create Promotion" 
                           style="
                               display: block;
                               width: 100%;
                               padding: 10px;
                               background-color: var(--accent-color);
                               color: var(--primary-color);
                               border: none;
                               border-radius: 5px;
                               font-weight: bold;
                               cursor: pointer;
                               transition: background-color 0.3s ease;
                           "
                           onmouseover="this.style.backgroundColor='#FFD700'"
                           onmouseout="this.style.backgroundColor='var(--accent-color)'"
                    >
                </form>
            </div>

            <h3>Existing Promotions</h3>
            <table class="promotion-list-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Discount Type</th>
                        <th>Discount Value</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($promotion = mysqli_fetch_assoc($promotionsResult)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($promotion['name']); ?></td>
                            <td><?php echo htmlspecialchars($promotion['description']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($promotion['discount_type'])); ?></td>
                            <td>
                                <?php 
                                echo $promotion['discount_type'] == 'percentage' 
                                    ? htmlspecialchars($promotion['discount_value']) . '%'
                                    : '$' . number_format($promotion['discount_value'], 2); 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($promotion['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($promotion['end_date']); ?></td>
                            <td>
                                <span class="status-badge status-badge-<?php echo htmlspecialchars($promotion['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($promotion['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this promotion?');">
                                    <input type="hidden" name="promotion_id" value="<?php echo $promotion['promotion_id']; ?>">
                                    <input type="submit" name="delete_promotion" value="Delete" class="delete-btn">
                                </form>
                            </td>
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
