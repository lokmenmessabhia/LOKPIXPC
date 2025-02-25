<?php
include 'db_connect.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $product_id = $_POST['product_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Delete related comments first
        $stmt = $pdo->prepare("DELETE FROM comments WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // Delete related order details
        $stmt = $pdo->prepare("DELETE FROM order_details WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        // Commit transaction
        $pdo->commit();

        echo "Product deleted successfully.";
    } catch (PDOException $e) {
        // Rollback transaction in case of an error
        $pdo->rollBack();
        echo "Error deleting product: " . $e->getMessage();
    }
}

// Handle product update (name, description, stock, price)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $price = $_POST['price'];

    try {
        // Update product details
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, stock = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $description, $stock, $price, $product_id]);

        echo "Product updated successfully.";
    } catch (PDOException $e) {
        echo "Error updating product: " . $e->getMessage();
    }
}

// Fetch all products
try {
    $stmt = $pdo->prepare("SELECT * FROM products");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching products: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - EcoTech</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --success: #4cc9f0;
            --success-dark: #4895ef;
            --danger: #f72585;
            --warning: #f8961e;
            --text: #2b2d42;
            --text-light: #6c757d;
            --bg: #f8f9fa;
            --bg-card: #ffffff;
            --border: #e9ecef;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        .top-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 1.75rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: var(--shadow-sm);
        }

        /* Nav brand and menu */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        .nav-brand h1 {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .nav-menu a {
            color: var(--text);
            text-decoration: none;
            padding: 0.6rem 0.9rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
            white-space: nowrap;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: var(--bg);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-end {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            background-color: var(--bg);
        }

        .back-button:hover {
            background-color: var(--primary-light);
            color: white;
        }

        /* Main Content - adjust to account for fixed header */
        .main-content {
            margin-top: 4.5rem;
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }

        .page-title {
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color: var(--text);
            border-bottom: 2px solid var(--border);
            padding-bottom: 0.75rem;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            background-color: var(--bg-card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: var(--primary-light);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(242, 242, 242, 0.6);
        }

        /* Button Styles */
        .btn-update, .btn-delete, button[type="submit"] {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            border: none;
            transition: var(--transition);
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn-update, button[type="submit"] {
            background-color: var(--primary);
            color: white;
        }

        .btn-update:hover, button[type="submit"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            filter: brightness(0.9);
            transform: translateY(-2px);
        }

        /* Action Buttons Container */
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        /* Edit Form Styles - Popup Version */
        .edit-form-container {
            display: block;
            background-color: var(--bg-card);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: fadeIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Edit Form Styles */
        .input-group {
            margin-bottom: 1rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .input-group input, .input-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
        }

        .input-group input:focus, .input-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .input-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Footer */
        footer {
            background-color: var(--bg-card);
            color: var(--text-light);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid var(--border);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .nav-menu, .nav-end {
                width: 100%;
                justify-content: center;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            th, td {
                padding: 0.75rem;
            }

            .actions {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }

            .edit-form-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-brand">
            <h1>EcoTech</h1>
        </div>
        <div class="nav-menu">
            <a href="dashboard_products.php" class="active">Products</a>
        </div>
        <div class="nav-end">
            <a href="add_product.php" class="btn-update">Add Product</a>
            <a href="dashboard.php" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <main class="main-content">
        <h2 class="page-title">Manage Products</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo htmlspecialchars($product['price']); ?> DZD</td>
                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                        <td>
                            <div class="actions">
                                <!-- View product details (Non-editable) -->
                                <button onclick="toggleEditForm(<?php echo $product['id']; ?>)" class="btn-update">Edit</button>

                                <!-- Delete form -->
                                <form action="dashboard_products.php" method="post" style="display:inline; margin: 0; padding: 0; box-shadow: none; border: none;">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <!-- Edit forms as modals (outside the table) -->
    <?php foreach ($products as $product): ?>
        <div id="editForm_<?php echo $product['id']; ?>" class="modal-overlay">
            <div class="edit-form-container">
                <button class="close-modal" onclick="toggleEditForm(<?php echo $product['id']; ?>)">&times;</button>
                <h3>Edit Product</h3>
                <form action="dashboard_products.php" method="post">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                    
                    <!-- Name input field -->
                    <div class="input-group">
                        <label for="name_<?php echo $product['id']; ?>">Name:</label>
                        <input type="text" id="name_<?php echo $product['id']; ?>" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    
                    <!-- Description input field -->
                    <div class="input-group">
                        <label for="description_<?php echo $product['id']; ?>">Description:</label>
                        <textarea id="description_<?php echo $product['id']; ?>" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <!-- Stock input field -->
                    <div class="input-group">
                        <label for="stock_<?php echo $product['id']; ?>">Stock:</label>
                        <input type="number" id="stock_<?php echo $product['id']; ?>" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" min="0" required>
                    </div>
                    
                    <!-- Price input field -->
                    <div class="input-group">
                        <label for="price_<?php echo $product['id']; ?>">Price:</label>
                        <input type="number" id="price_<?php echo $product['id']; ?>" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" required>
                    </div>
                    
                    <button type="submit" name="update">Update</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <footer>
        <p>&copy; 2024 EcoTech. All rights reserved.</p>
    </footer>

    <script>
        // Function to toggle the visibility of the edit form modal
        function toggleEditForm(productId) {
            var editForm = document.getElementById("editForm_" + productId);
            if (editForm.style.display === "none" || editForm.style.display === "") {
                editForm.style.display = "flex";
                document.body.style.overflow = "hidden"; // Prevent scrolling when modal is open
            } else {
                editForm.style.display = "none";
                document.body.style.overflow = ""; // Re-enable scrolling
            }
        }
        
        // Close modal when clicking outside the content
        document.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(function(modal) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = "";
                }
            });
        });
    </script>
</body>
</html>