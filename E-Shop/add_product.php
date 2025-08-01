<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch categories for dropdown
$categories_query = "SELECT category_id, name FROM categories";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch suppliers for dropdown
$suppliers_query = "SELECT supplier_id, name FROM suppliers";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Handle product addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category_id = intval($_POST['category_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $cost_price = floatval($_POST['cost_price']);
    $selling_price = floatval($_POST['selling_price']);
    $quantity = intval($_POST['quantity']);
    $min_stock_level = intval($_POST['min_stock_level']);
    $image_url = '';

    // Handle image upload
    if ($_POST['image_option'] == 'upload' && isset($_FILES['image_upload'])) {
        $upload_dir = 'uploads/products/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

        // Upload file
        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $upload_path)) {
            $image_url = $upload_path;
        } else {
            $error_message = "Failed to upload image.";
        }
    } 
    // Handle URL input
    elseif ($_POST['image_option'] == 'url' && !empty($_POST['image_url'])) {
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
        
        // Basic URL validation
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            $error_message = "Invalid image URL.";
        }
    }

    // If no errors, proceed with product insertion
    if (!isset($error_message)) {
        // Prepare insert query with image_url
        $insert_query = "INSERT INTO products 
        (name, category_id, supplier_id, description, cost_price, selling_price, quantity, min_stock_level, image_url) 
        VALUES 
        ('$name', $category_id, $supplier_id, '$description', $cost_price, $selling_price, $quantity, $min_stock_level, " . 
        (!empty($image_url) ? "'$image_url'" : "NULL") . ")";

        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Product added successfully!";
        } else {
            $error_message = "Error adding product: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="Seller_Styles.css">
    <style>
        .add-product-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 800px;
        }

        .add-product-header {
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .add-product-form {
            display: grid;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid var(--neutral-color);
            border-radius: 4px;
            font-size: 1em;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }

        .submit-btn:hover {
            background-color: #FFD700;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .success-message {
            background-color: var(--success-color);
            color: var(--white);
        }

        .error-message {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .image-option-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .navigation-section {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Add New Product</h1>
            <p class="dashboard-welcome">Welcome, Seller</p>
        </div>

        <div class="add-product-container">
            <?php 
            if (isset($success_message)) {
                echo "<div class='message success-message'>$success_message</div>";
            }
            if (isset($error_message)) {
                echo "<div class='message error-message'>$error_message</div>";
            }
            ?>

            <form method="POST" action="" enctype="multipart/form-data" class="add-product-form">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php 
                        mysqli_data_seek($categories_result, 0);
                        while ($category = mysqli_fetch_assoc($categories_result)) {
                            echo "<option value='{$category['category_id']}'>{$category['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" required>
                        <?php 
                        mysqli_data_seek($suppliers_result, 0);
                        while ($supplier = mysqli_fetch_assoc($suppliers_result)) {
                            echo "<option value='{$supplier['supplier_id']}'>{$supplier['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="cost_price">Cost Price</label>
                    <input type="number" id="cost_price" name="cost_price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="selling_price">Selling Price</label>
                    <input type="number" id="selling_price" name="selling_price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" required>
                </div>
                
                <div class="form-group">
                    <label for="min_stock_level">Minimum Stock Level</label>
                    <input type="number" id="min_stock_level" name="min_stock_level" value="10">
                </div>
                
                <div class="form-group">
                    <label>Image Option</label>
                    <div class="image-option-group">
                        <select name="image_option" id="image_option">
                            <option value="">No Image</option>
                            <option value="upload">Upload Image</option>
                            <option value="url">Image URL</option>
                        </select>
                    </div>
                </div>

                <div id="upload_div" class="form-group" style="display:none;">
                    <label for="image_upload">Upload Image</label>
                    <input type="file" id="image_upload" name="image_upload" accept="image/*">
                </div>

                <div id="url_div" class="form-group" style="display:none;">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url">
                </div>
                
                <button type="submit" class="submit-btn">Add Product</button>
            </form>
        </div>

        <div class="navigation-section">
            <a href="seller_dashboard.php" class="logout-link">Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Simple JavaScript for toggling image input fields
        document.getElementById('image_option').addEventListener('change', function() {
            var uploadDiv = document.getElementById('upload_div');
            var urlDiv = document.getElementById('url_div');
            
            uploadDiv.style.display = (this.value === 'upload') ? 'block' : 'none';
            urlDiv.style.display = (this.value === 'url') ? 'block' : 'none';
        });
    </script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
