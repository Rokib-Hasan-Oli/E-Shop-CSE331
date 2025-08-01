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
    // Add or Update Supplier
    if (isset($_POST['action']) && ($_POST['action'] == 'add' || $_POST['action'] == 'update')) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        // Check if adding or updating
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO suppliers (
                name, contact_person, email, phone, address, status
            ) VALUES (
                '$name', '$contact_person', '$email', '$phone', '$address', '$status'
            )";
        } else {
            // Update supplier
            $supplier_id = intval($_POST['supplier_id']);
            $query = "UPDATE suppliers SET 
                name = '$name', 
                contact_person = '$contact_person', 
                email = '$email', 
                phone = '$phone', 
                address = '$address', 
                status = '$status' 
                WHERE supplier_id = $supplier_id";
        }

        if (mysqli_query($conn, $query)) {
            $success_message = ($_POST['action'] == 'add') ? "Supplier added successfully" : "Supplier updated successfully";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }

    // Delete Supplier
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $supplier_id = intval($_POST['supplier_id']);
        
        // Check if supplier is associated with any products
        $check_query = "SELECT COUNT(*) as product_count FROM products WHERE supplier_id = $supplier_id";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if ($check_row['product_count'] > 0) {
            $error_message = "Cannot delete supplier. Some products are still linked to this supplier.";
        } else {
            $query = "DELETE FROM suppliers WHERE supplier_id = $supplier_id";
            
            if (mysqli_query($conn, $query)) {
                $success_message = "Supplier deleted successfully";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch suppliers for display
$query = "SELECT s.*, 
            COUNT(DISTINCT p.product_id) as product_count,
            COUNT(DISTINCT po.po_id) as purchase_order_count
          FROM suppliers s 
          LEFT JOIN products p ON s.supplier_id = p.supplier_id
          LEFT JOIN purchase_orders po ON s.supplier_id = po.supplier_id
          GROUP BY s.supplier_id
          ORDER BY s.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Admin_Styles.css">
    <style>
        .suppliers-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .suppliers-table th, .suppliers-table td {
            border: 1px solid var(--neutral-color);
            padding: 10px;
            text-align: left;
        }
        .suppliers-table th {
            background-color: var(--primary-color);
            color: var(--white);
        }
        .supplier-form {
            margin-bottom: 20px;
        }
        .supplier-form label {
            display: block;
            margin-bottom: 10px;
        }
        .supplier-form input[type="text"],
        .supplier-form input[type="email"],
        .supplier-form textarea,
        .supplier-form select {
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
        .status-active {
            color: var(--success-color);
            font-weight: bold;
        }
        .status-inactive {
            color: var(--danger-color);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Manage Suppliers</h1>
        </div>

        <?php 
        if (isset($success_message)) {
            echo "<p class='text-success'>" . htmlspecialchars($success_message) . "</p>";
        }
        if (isset($error_message)) {
            echo "<p class='text-danger'>" . htmlspecialchars($error_message) . "</p>";
        }
        ?>

        <div class="dashboard-section supplier-form">
            <h3>Add/Edit Supplier</h3>
            <form method="post">
                <input type="hidden" name="supplier_id" id="supplier_id">
                <input type="hidden" name="action" id="form_action" value="add">
                
                <label>
                    Supplier Name:
                    <input type="text" name="name" required>
                </label>
                
                <label>
                    Contact Person:
                    <input type="text" name="contact_person">
                </label>
                
                <label>
                    Email:
                    <input type="email" name="email" required>
                </label>
                
                <label>
                    Phone:
                    <input type="text" name="phone" required>
                </label>
                
                <label>
                    Address:
                    <textarea name="address" rows="4"></textarea>
                </label>
                
                <label>
                    Status:
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                
                <input type="submit" value="Add Supplier" class="logout-link">
            </form>
        </div>

        <div class="dashboard-section">
            <h3>Supplier List</h3>
            <table class="suppliers-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th>Purchase Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['supplier_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td>
                            <span class="<?php 
                                echo $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                            ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['product_count']); ?></td>
                        <td><?php echo htmlspecialchars($row['purchase_order_count']); ?></td>
                        <td class="action-buttons">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="supplier_id" value="<?php echo $row['supplier_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="submit" value="Delete" class="delete-btn" 
                                       onclick="return confirm('Are you sure? Delete only if no products or purchase orders are linked.');">
                            </form>
                            <button onclick="editSupplier(
                                '<?php echo htmlspecialchars(addslashes($row['supplier_id'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['name'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['contact_person'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['email'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['phone'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['address'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['status'])); ?>'
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
    function editSupplier(id, name, contact_person, email, phone, address, status) {
        document.getElementById('supplier_id').value = id;
        document.querySelector('input[name="name"]').value = name;
        document.querySelector('input[name="contact_person"]').value = contact_person;
        document.querySelector('input[name="email"]').value = email;
        document.querySelector('input[name="phone"]').value = phone;
        document.querySelector('textarea[name="address"]').value = address;
        document.querySelector('select[name="status"]').value = status;
        
        document.getElementById('form_action').value = 'update';
        document.querySelector('input[type="submit"]').value = 'Update Supplier';
    }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
