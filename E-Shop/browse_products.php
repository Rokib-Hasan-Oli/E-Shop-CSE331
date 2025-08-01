<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Initialize filter variables
$category_filters = isset($_GET['category']) ? $_GET['category'] : [];
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 2000000;
$sort_by = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'default';
$items_per_page = isset($_GET['show']) ? intval($_GET['show']) : 20;

// Build SQL query with filters
$products_sql = "SELECT p.*, c.name as category_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 WHERE p.status = 'active' AND p.quantity > 0 
                 AND p.selling_price BETWEEN $min_price AND $max_price";

// Add category filter if categories are selected
if (!empty($category_filters)) {
    // Sanitize category filters
    $sanitized_categories = array_map(function($category) use ($conn) {
        return "'". mysqli_real_escape_string($conn, $category) ."'";
    }, $category_filters);
    
    $category_list = implode(',', $sanitized_categories);
    $products_sql .= " AND c.name IN ($category_list)";
}

// Add sorting
switch($sort_by) {
    case 'price_low':
        $products_sql .= " ORDER BY p.selling_price ASC";
        break;
    case 'price_high':
        $products_sql .= " ORDER BY p.selling_price DESC";
        break;
    case 'name':
        $products_sql .= " ORDER BY p.name ASC";
        break;
    default:
        $products_sql .= " ORDER BY p.created_at DESC";
}

// Add pagination limit
$products_sql .= " LIMIT $items_per_page";

$products_result = mysqli_query($conn, $products_sql);

// Fetch all categories for sidebar
$categories_sql = "SELECT DISTINCT name FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products</title>
    <link rel="stylesheet" href="Customer_Styles.css">
</head>
<body>
    <div class="customer-dashboard">
        <header class="dashboard-header">
            <div class="dashboard-logo"></div>
            <h1 class="dashboard-title">Browse Products</h1>
        </header>

        <div class="product-browse-container">
            <aside class="product-filters">
                <form action="" method="get">
                    <!-- Price Range Filter -->
                    <div class="filter-section price-range">
                        <h3>Price Range</h3>
                        <div class="price-inputs">
                            <input type="number" name="min_price" placeholder="Min" 
                                   value="<?php echo $min_price; ?>" min="0">
                            <input type="number" name="max_price" placeholder="Max" 
                                   value="<?php echo $max_price; ?>" max="2000000">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-section category-filter">
                        <h3>Categories</h3>
                        <?php 
                        mysqli_data_seek($categories_result, 0); // Reset pointer
                        while($category = mysqli_fetch_assoc($categories_result)): 
                        ?>
                            <div class="category-checkbox">
                                <input type="checkbox" 
                                       name="category[]" 
                                       id="cat-<?php echo htmlspecialchars($category['name']); ?>" 
                                       value="<?php echo htmlspecialchars($category['name']); ?>"
                                       <?php echo (in_array($category['name'], $category_filters) ? 'checked' : ''); ?>>
                                <label for="cat-<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Sort and Show Options -->
                    <div class="filter-section sort-options">
                        <h3>Sort & Display</h3>
                        <select name="sort">
                            <option value="default" <?php echo ($sort_by == 'default' ? 'selected' : ''); ?>>Default</option>
                            <option value="price_low" <?php echo ($sort_by == 'price_low' ? 'selected' : ''); ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo ($sort_by == 'price_high' ? 'selected' : ''); ?>>Price: High to Low</option>
                            <option value="name" <?php echo ($sort_by == 'name' ? 'selected' : ''); ?>>Name</option>
                        </select>

                        <select name="show">
                            <option value="20" <?php echo ($items_per_page == 20 ? 'selected' : ''); ?>>Show: 20</option>
                            <option value="50" <?php echo ($items_per_page == 50 ? 'selected' : ''); ?>>Show: 50</option>
                            <option value="100" <?php echo ($items_per_page == 100 ? 'selected' : ''); ?>>Show: 100</option>
                        </select>

                        <button type="submit" class="apply-filters-btn">Apply Filters</button>
                    </div>
                </form>
            </aside>

            <section id="product-catalog">
            <h2>Available Products</h2>
            
            <?php if (mysqli_num_rows($products_result) > 0): ?>
                <div class="product-grid">
                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card">
                            <?php if (!empty($product['image_url'])): ?>
                                <div class="product-image-container">
                                    <img 
                                        src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="product-image"
                                    >
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-details">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <div class="product-info">
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                                    <p class="product-price">$<?php echo number_format($product['selling_price'], 2); ?></p>
                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <p class="stock-info">
                                        <?php 
                                        if ($product['quantity'] <= 5) {
                                            echo "<span class='text-danger'>Low Stock: {$product['quantity']} available</span>";
                                        } else {
                                            echo "<span class='text-success'>{$product['quantity']} in stock</span>";
                                        }
                                        ?>
                                    </p>
                                </div>
                                
                                <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <div class="quantity-selector">
                                        <label for="quantity-<?php echo $product['product_id']; ?>">Quantity:</label>
                                        <input 
                                            type="number" 
                                            id="quantity-<?php echo $product['product_id']; ?>" 
                                            name="quantity" 
                                            min="1" 
                                            max="<?php echo $product['quantity']; ?>" 
                                            value="1"
                                        >
                                    </div>
                                    <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-products">No products available at the moment.</p>
            <?php endif; ?>
        </section>
        </div>

        <div id="quick-navigation">
            <ul>
                <li><a href="customer_dashboard.php">Back to Dashboard</a></li>
                <li><a href="view_cart.php">View Cart</a></li>
                <li><a href="order_history.php">Order History</a></li>
            </ul>
        </div>
    </div>

    <style>
        /* Updated Product Card Styles */
        .product-grid {
            display: grid;
            grid-template-columns: auto auto auto;
            gap: 20px;
        }

        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: scale(1.03);
        }

        .product-image-container {
            width: 100%;
            height: 300px;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-details {
            padding: 5px;
        }

        .product-name {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2em;
            color: var(--primary-color);
            text-align: center;
        }

        .product-price {
            font-weight: bold;
            color: var(--accent-color);
            text-align: center;
            font-size: 1.1em;
            margin: 10px 0;
        }

        .product-description {
            color: var(--text-color);
            margin-bottom: 10px;
            text-align: center;
            height: 110px;
            overflow: auto
        }

        .add-to-cart-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .quantity-selector input {
            width: 60px;
            margin-left: 10px;
            padding: 5px;
        }

        .add-to-cart-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background-color: var(--accent-color);
        }

        /* Previous styles remain the same */
        .product-browse-container {
            display: grid;
            grid-template-columns: auto auto auto auto;
        }

        .product-filters {
            width: 250px;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
            margin-right: 20px;
        }

        .price-inputs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .price-inputs input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .category-checkbox {
            margin-bottom: 10px;
        }

        .category-checkbox input {
            margin-right: 10px;
        }

        .sort-options select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .apply-filters-btn {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .apply-filters-btn:hover {
            background-color: var(--accent-color);
        }
    </style>
</body>
</html>
