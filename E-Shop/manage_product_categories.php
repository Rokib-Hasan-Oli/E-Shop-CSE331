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
    // Add or Update Category
    if (isset($_POST['action']) && ($_POST['action'] == 'add' || $_POST['action'] == 'update')) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        // Check if adding or updating
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
        } else {
            // Update category
            $category_id = intval($_POST['category_id']);
            $query = "UPDATE categories SET 
                      name = '$name', 
                      description = '$description' 
                      WHERE category_id = $category_id";
        }

        if (mysqli_query($conn, $query)) {
            $success_message = ($_POST['action'] == 'add') ? "Category added successfully" : "Category updated successfully";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }

    // Delete Category
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $category_id = intval($_POST['category_id']);
        
        // First, check if any products are using this category
        $check_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = $category_id";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if ($check_row['product_count'] > 0) {
            $error_message = "Cannot delete category. Some products are still using this category.";
        } else {
            $query = "DELETE FROM categories WHERE category_id = $category_id";
            
            if (mysqli_query($conn, $query)) {
                $success_message = "Category deleted successfully";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch categories for display
$query = "SELECT c.*, COUNT(p.product_id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.category_id = p.category_id 
          GROUP BY c.category_id, c.name, c.description, c.created_at
          ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Product Categories | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .categories-table th, .categories-table td {
            border: 1px solid var(--neutral-color);
            padding: 10px;
            text-align: left;
        }
        .categories-table th {
            background-color: var(--primary-color);
            color: var(--white);
        }
        .category-form {
            margin-bottom: 20px;
        }
        .category-form label {
            display: block;
            margin-bottom: 10px;
        }
        .category-form input[type="text"],
        .category-form textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .delete-btn, .edit-btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .delete-btn {
            background-color: var(--danger-color);
            color: var(--white);
        }
        .edit-btn {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        .delete-btn:hover {
            background-color: #D32F2F;
        }
        .edit-btn:hover {
            background-color: #FFA000;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Manage Product Categories</h1>
        </div>

        <?php 
        if (isset($success_message)) {
            echo "<p class='text-success'>" . htmlspecialchars($success_message) . "</p>";
        }
        if (isset($error_message)) {
            echo "<p class='text-danger'>" . htmlspecialchars($error_message) . "</p>";
        }
        ?>

        <div class="dashboard-section category-form">
            <h3>Add/Edit Category</h3>
            <form method="post">
                <input type="hidden" name="category_id" id="category_id">
                <input type="hidden" name="action" id="form_action" value="add">
                
                <label>
                    Category Name:
                    <input type="text" name="name" required>
                </label>
                
                <label>
                    Description:
                    <textarea name="description" rows="4"></textarea>
                </label>
                
                <input type="submit" value="Add Category" class="logout-link">
            </form>
        </div>

        <div class="dashboard-section">
            <h3>Category List</h3>
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Product Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_count']); ?></td>
                        <td class="action-buttons">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="submit" value="Delete" class="delete-btn" 
                                       onclick="return confirm('Are you sure? Delete only if no products are in this category.');">
                            </form>
                            <button onclick="editCategory(
                                '<?php echo $row['category_id']; ?>',
                                '<?php echo htmlspecialchars(addslashes($row['name'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['description'])); ?>'
                            )" class="edit-btn">Edit</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="admin_dashboard.php" class="logout-link">Back to Dashboard</a>
    </div>

    <script>
    function editCategory(id, name, description) {
        document.getElementById('category_id').value = id;
        document.querySelector('input[name="name"]').value = name;
        document.querySelector('textarea[name="description"]').value = description;
        
        document.getElementById('form_action').value = 'update';
        document.querySelector('input[type="submit"]').value = 'Update Category';
    }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
