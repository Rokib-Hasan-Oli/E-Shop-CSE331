<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Validation function
function validateProductInput($data) {
    $errors = [];

    if (empty($data['name'])) {
        $errors[] = "Product name is required";
    }

    if (!is_numeric($data['cost_price']) || $data['cost_price'] < 0) {
        $errors[] = "Invalid cost price";
    }

    if (!is_numeric($data['selling_price']) || $data['selling_price'] < 0) {
        $errors[] = "Invalid selling price";
    }

    if ($data['selling_price'] < $data['cost_price']) {
        $errors[] = "Selling price must be greater than or equal to cost price";
    }

    if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
        $errors[] = "Invalid quantity";
    }

    // Validate image URL if provided
    if (!empty($data['image_url']) && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid image URL";
    }

    return $errors;
}

// Function to download and save image from URL
function saveImageFromUrl($url) {
    $target_dir = "uploads/products/";
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($url, PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    // Attempt to download and save the image
    try {
        $image_content = file_get_contents($url);
        if ($image_content !== false) {
            file_put_contents($target_file, $image_content);
            return $target_file;
        }
    } catch (Exception $e) {
        return '';
    }

    return '';
}

// Fetch categories and suppliers for dropdowns
$category_query = "SELECT category_id, name FROM categories";
$category_result = mysqli_query($conn, $category_query);

$supplier_query = "SELECT supplier_id, name FROM suppliers";
$supplier_result = mysqli_query($conn, $supplier_query);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add or Update Product
    if (isset($_POST['action']) && ($_POST['action'] == 'add' || $_POST['action'] == 'update')) {
        // Validate input
        $validation_errors = validateProductInput($_POST);

        if (empty($validation_errors)) {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category_id = intval($_POST['category_id']);
            $supplier_id = intval($_POST['supplier_id']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $cost_price = floatval($_POST['cost_price']);
            $selling_price = floatval($_POST['selling_price']);
            $quantity = intval($_POST['quantity']);
            $min_stock_level = intval($_POST['min_stock_level']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);

            // Handle image upload
            $image_url = '';
            
            // Check file upload
            if (!empty($_FILES['image']['name'])) {
                $target_dir = "uploads/products/";
                // Create directory if it doesn't exist
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                // Generate unique filename
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $unique_filename = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $unique_filename;

                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                } else {
                    $validation_errors[] = "Failed to upload image file";
                }
            } 
            // Check image URL
            elseif (!empty($_POST['image_url'])) {
                $image_url = saveImageFromUrl($_POST['image_url']);
                if (empty($image_url)) {
                    $validation_errors[] = "Failed to download image from URL";
                }
            }

            // Check if adding or updating
            if ($_POST['action'] == 'add') {
                $query = "INSERT INTO products (
                    name, category_id, supplier_id, description, 
                    cost_price, selling_price, quantity, 
                    min_stock_level, image_url, status
                ) VALUES (
                    '$name', $category_id, $supplier_id, '$description', 
                    $cost_price, $selling_price, $quantity, 
                    $min_stock_level, '$image_url', '$status'
                )";
            } else {
                // Update product
                $product_id = intval($_POST['product_id']);
                
                $query = "UPDATE products SET 
                    name = '$name', 
                    category_id = $category_id, 
                    supplier_id = $supplier_id, 
                    description = '$description', 
                    cost_price = $cost_price, 
                    selling_price = $selling_price, 
                    quantity = $quantity, 
                    min_stock_level = $min_stock_level, 
                    " . (!empty($image_url) ? "image_url = '$image_url', " : "") . "
                    status = '$status' 
                    WHERE product_id = $product_id";
            }

            if (mysqli_query($conn, $query)) {
                $success_message = ($_POST['action'] == 'add') ? "Product added successfully" : "Product updated successfully";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        } else {
            $error_message = implode("<br>", $validation_errors);
        }
    }

    // Delete Product
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $product_id = intval($_POST['product_id']);
        
        // Check if product is in any order
        $check_order_query = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id = $product_id";
        $check_order_result = mysqli_query($conn, $check_order_query);
        $order_count = mysqli_fetch_assoc($check_order_result)['order_count'];

        if ($order_count > 0) {
            $error_message = "Cannot delete product. It is referenced in existing orders.";
        } else {
            $query = "DELETE FROM products WHERE product_id = $product_id";
            
            if (mysqli_query($conn, $query)) {
                $success_message = "Product deleted successfully";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch products for display
$product_query = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id";
$product_result = mysqli_query($conn, $product_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Dashboard</title>
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        /* Additional specific styles for this page */
        .products-management-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }

        .product-form {
            background-color: var(--background-color);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .product-form label {
            display: block;
            margin: 10px 0 5px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .product-form input, 
        .product-form select, 
        .product-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .product-form input[type="submit"] {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: auto;
        }

        .product-form input[type="submit"]:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .product-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .product-list-table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px;
            text-align: left;
        }

        .product-list-table td {
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

        .status-badge-inactive {
            background-color: var(--neutral-color);
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

        .image-upload-options {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .image-upload-options label {
            margin: 0;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Manage Products</h1>
            <p class="dashboard-welcome">Welcome, Admin</p>
        </div>

        <div class="products-management-container">
            <?php 
            // Display validation errors, success, or error messages
            if (isset($error_message)) {
                echo "<div class='error-message'>" . $error_message . "</div>";
            }
            if (isset($success_message)) {
                echo "<div class='success-message'>" . $success_message . "</div>";
            }
            ?>

            <form method="post" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="product_id" id="product_id">
                <input type="hidden" name="action" id="form_action" value="add">
                
                <label for="name">Product Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" required>
                
                <label for="category_id">Category: <span class="text-danger">*</span></label>
                <select name="category_id" id="category_id" required>
                    <?php 
                    mysqli_data_seek($category_result, 0);
                    while ($cat = mysqli_fetch_assoc($category_result)): ?>
                        <option value="<?php echo $cat['category_id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <label for="supplier_id">Supplier: <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplier_id" required>
                    <?php 
                    mysqli_data_seek($supplier_result, 0);
                    while ($sup = mysqli_fetch_assoc($supplier_result)): ?>
                        <option value="<?php echo $sup['supplier_id']; ?>">
                            <?php echo htmlspecialchars($sup['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <label for="description">Description:</label>
                <textarea name="description" id="description"></textarea>
                
                <label for="cost_price">Cost Price: <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="cost_price" id="cost_price" required>
                
                <label for="selling_price">Selling Price: <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="selling_price" id="selling_price" required>
                
                <label for="quantity">Quantity: <span class="text-danger">*</span></label>
                <input type="number" name="quantity" id="quantity" required>
                
                <label for="min_stock_level">Minimum Stock Level:</label>
                <input type="number" name="min_stock_level" id="min_stock_level" value="10">
                
                <label>Product Image:</label>
                <div class="image-upload-options">
                    
                    <input type="radio" name="image_type" id="file_upload" value="file" checked>
                    <label for="file_upload">File Upload</label>
                    
                    <input type="radio" name="image_type" id="url_upload" value="url">
                    <label for="url_upload">URL</label>
                </div>
                
                <div id="file_upload_section">
                    <input type="file" name="image" id="image" accept="image/*">
                </div>
                
                <div id="url_upload_section" style="display:none;">
                    <input type="url" name="image_url" id="image_url" placeholder="Enter image URL">
                </div>
                
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                
                <input type="submit" value="Add Product">
            </form>

            <h3>Product List</h3>
            <table class="product-list-table">
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
                    <?php while ($row = mysqli_fetch_assoc($product_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?php echo number_format($row['cost_price'], 2); ?></td>
                        <td><?php echo number_format($row['selling_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td>
                            <span class="status-badge <?php 
                                echo $row['status'] == 'active' ? 'status-badge-active' : 'status-badge-inactive'; 
                            ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?');">
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                                <input type="hidden" name="supplier_id" value="<?php echo $row['supplier_id']; ?>">
                                <input type="hidden" name="description" value="<?php echo htmlspecialchars($row['description']); ?>">
                                <input type="hidden" name="cost_price" value="<?php echo $row['cost_price']; ?>">
                                <input type="hidden" name="selling_price" value="<?php echo $row['selling_price']; ?>">
                                <input type="hidden" name="quantity" value="<?php echo $row['quantity']; ?>">
                                <input type="hidden" name="min_stock_level" value="<?php echo $row['min_stock_level']; ?>">
                                <input type="hidden" name="status" value="<?php echo $row['status']; ?>">
                                <input type="hidden" name="action" value="edit">
                                <input type="submit" value="Edit" class="edit-btn">
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

    <script>
    // Add event listeners for image upload type selection
    document.getElementById('file_upload').addEventListener('change', function() {
        document.getElementById('file_upload_section').style.display = 'block';
        document.getElementById('url_upload_section').style.display = 'none';
    });

    document.getElementById('url_upload').addEventListener('change', function() {
        document.getElementById('file_upload_section').style.display = 'none';
        document.getElementById('url_upload_section').style.display = 'block';
    });

    // Prefill form for edit
    <?php if(isset($_POST['action']) && $_POST['action'] == 'edit'): ?>
        document.getElementById('product_id').value = '<?php echo $_POST['product_id']; ?>';
        document.getElementById('name').value = '<?php echo htmlspecialchars($_POST['name']); ?>';
        document.getElementById('category_id').value = '<?php echo $_POST['category_id']; ?>';
        document.getElementById('supplier_id').value = '<?php echo $_POST['supplier_id']; ?>';
        document.getElementById('description').value = '<?php echo htmlspecialchars($_POST['description']); ?>';
        document.getElementById('cost_price').value = '<?php echo $_POST['cost_price']; ?>';
        document.getElementById('selling_price').value = '<?php echo $_POST['selling_price']; ?>';
        document.getElementById('quantity').value = '<?php echo $_POST['quantity']; ?>';
        document.getElementById('min_stock_level').value = '<?php echo $_POST['min_stock_level']; ?>';
        document.getElementById('status').value = '<?php echo $_POST['status']; ?>';
        document.getElementById('form_action').value = 'update';
        document.querySelector('input[type="submit"]').value = 'Update Product';
    <?php endif; ?>
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
