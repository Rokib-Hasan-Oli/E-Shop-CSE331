<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$product_query = "SELECT * FROM products WHERE product_id = $product_id";
$product_result = mysqli_query($conn, $product_query);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    header("Location: manage_products.php");
    exit();
}

// Fetch categories for dropdown
$categories_query = "SELECT category_id, name FROM categories";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch suppliers for dropdown
$suppliers_query = "SELECT supplier_id, name FROM suppliers";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Handle product update
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
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Update product query
    $update_query = "UPDATE products SET 
        name = '$name', 
        category_id = $category_id, 
        supplier_id = $supplier_id, 
        description = '$description', 
        cost_price = $cost_price, 
        selling_price = $selling_price, 
        quantity = $quantity, 
        min_stock_level = $min_stock_level,
        status = '$status'
    WHERE product_id = $product_id";

    if (mysqli_query($conn, $update_query)) {
        $success_message = "Product updated successfully!";
        // Refresh product data
        $product_result = mysqli_query($conn, $product_query);
        $product = mysqli_fetch_assoc($product_result);
    } else {
        $error_message = "Error updating product: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
</head>
<body>
    <h2>Edit Product</h2>
    
    <?php 
    if (isset($success_message)) {
        echo "<p style='color:green;'>$success_message</p>";
    }
    if (isset($error_message)) {
        echo "<p style='color:red;'>$error_message</p>";
    }
    ?>

    <form method="POST" action="">
        <label>Product Name: <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></label><br>
        
        <label>Category:
            <select name="category_id" required>
                <?php 
                mysqli_data_seek($categories_result, 0);
                while ($category = mysqli_fetch_assoc($categories_result)) {
                    $selected = ($category['category_id'] == $product['category_id']) ? 'selected' : '';
                    echo "<option value='{$category['category_id']}' $selected>{$category['name']}</option>";
                }
                ?>
            </select>
        </label><br>
        
        <label>Supplier:
            <select name="supplier_id" required>
                <?php 
                mysqli_data_seek($suppliers_result, 0);
                while ($supplier = mysqli_fetch_assoc($suppliers_result)) {
                    $selected = ($supplier['supplier_id'] == $product['supplier_id']) ? 'selected' : '';
                    echo "<option value='{$supplier['supplier_id']}' $selected>{$supplier['name']}</option>";
                }
                ?>
            </select>
        </label><br>
        
        <label>Description: <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea></label><br>
        
        <label>Cost Price: <input type="number" step="0.01" name="cost_price" value="<?php echo $product['cost_price']; ?>" required></label><br>
        
        <label>Selling Price: <input type="number" step="0.01" name="selling_price" value="<?php echo $product['selling_price']; ?>" required></label><br>
        
        <label>Quantity: <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" required></label><br>
        
        <label>Minimum Stock Level: <input type="number" name="min_stock_level" value="<?php echo $product['min_stock_level']; ?>"></label><br>
        
        <label>Status:
            <select name="status">
                <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </label><br>
        
        <input type="submit" value="Update Product">
    </form>

    <p><a href="manage_products.php">Back to Product Management</a></p>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
