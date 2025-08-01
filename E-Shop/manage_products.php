<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM products WHERE product_id = $product_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $success_message = "Product deleted successfully!";
    } else {
        $error_message = "Error deleting product: " . mysqli_error($conn);
    }
}

// Handle product status update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    
    $status_query = "UPDATE products SET status = '$status' WHERE product_id = $product_id";
    
    if (mysqli_query($conn, $status_query)) {
        $success_message = "Product status updated successfully!";
    } else {
        $error_message = "Error updating product status: " . mysqli_error($conn);
    }
}

// Fetch products
$products_query = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                   FROM products p
                   JOIN categories c ON p.category_id = c.category_id
                   JOIN suppliers s ON p.supplier_id = s.supplier_id";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Seller Dashboard</title>
    <link rel="stylesheet" href="Seller_Styles.css">
    <style>
        .products-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .products-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .products-table td {
            padding: 10px;
            border-bottom: 1px solid var(--neutral-color);
        }

        .products-table tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .action-links a {
            display: inline-block;
            margin-right: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
        }

        .action-links .edit-link {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .action-links .delete-link {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .action-links .status-link {
            background-color: var(--success-color);
            color: var(--white);
        }

        .action-links .status-link.inactive {
            background-color: var(--neutral-color);
        }


        .message {
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .success-message {
            background-color: #DFF0D8;
            color: #3C763D;
        }

        .error-message {
            background-color: #F2DEDE;
            color: #A94442;
        }
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Manage Products</h1>
            <p class="dashboard-welcome">Welcome, Seller</p>
        </div>

        <div class="products-container">
            <?php 
            if (isset($success_message)) {
                echo "<div class='message success-message'>" . htmlspecialchars($success_message) . "</div>";
            }
            if (isset($error_message)) {
                echo "<div class='message error-message'>" . htmlspecialchars($error_message) . "</div>";
            }
            ?>

            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Cost Price</th>
                        <th>Selling Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                    <tr>
                        <td><?php echo $product['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                        <td>$<?php echo number_format($product['cost_price'], 2); ?></td>
                        <td>$<?php echo number_format($product['selling_price'], 2); ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td><?php echo $product['status']; ?></td>
                        <td class="action-links">
                            <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="edit-link">Edit</a>
                            <a href="?delete=<?php echo $product['product_id']; ?>" onclick="return confirm('Are you sure?')" class="delete-link">Delete</a>
                            <?php if ($product['status'] == 'active'): ?>
                                <a href="?id=<?php echo $product['product_id']; ?>&status=inactive" class="status-link">Deactivate</a>
                            <?php else: ?>
                                <a href="?id=<?php echo $product['product_id']; ?>&status=active" class="status-link inactive">Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <br>
            <div class="navigation-section">
                    <a href="add_product.php" class="usefull-link">Add New Product</a>
                    <a href="seller_dashboard.php" class="logout-link">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
