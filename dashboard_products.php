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
    <title>Manage Products - Lokpix</title>
    <link rel="stylesheet" href="dashboard_products.css"> <!-- Ensure this path is correct -->
    <style>
       /* Modern CSS Reset */
       * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1, #3b82f6);
            --secondary-gradient: linear-gradient(135deg, #f43f5e, #ec4899);
            --surface-color: #ffffff;
            --background-color: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Header Styles */
        header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        header a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        header a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Main Content Styles */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        /* Form Styles */
        form {
            background: var(--surface-color);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            margin-bottom: 2.5rem;
            transition: transform 0.3s ease;
        }

        form:hover {
            transform: translateY(-5px);
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.95rem;
        }

        input, textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background-color: white;
        }

        /* Button Styles */
        button {
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
            display: inline-block;
            margin-right: 0.5rem;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
        }

        /* Smaller Edit Button */
        .edit-button {
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
        }

        /* Delete Button Styles */
        button.delete {
            background-color: #ff3366; /* Match the specific shade */
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        button.delete:hover {
            background-color: #ff1a4d; /* Darker shade on hover */
            transform: translateY(-2px);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--surface-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
        }

        th, td {
            padding: 1.25rem;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: var(--text-primary);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:not(:last-child) td {
            border-bottom: 1px solid #e2e8f0;
        }

        tr td {
            transition: all 0.3s ease;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        /* Actions Container */
        .actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .actions button {
            background-color: #6366f1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .actions button:hover {
            background-color: #4f46e5;
            transform: translateY(-2px);
        }

        /* Image Preview */
        td img {
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            background-color: var(--surface-color);
            border-top: 1px solid #e2e8f0;
            margin-top: 4rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 1.5rem;
            }
            
            .main-content {
                padding: 0 1rem;
            }
            
            form {
                padding: 1.5rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            button, a[href*="delete"] {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .actions {
                flex-direction: column;
            }
        }

        /* Glass Morphism Effects */
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <h1>Manage Products</h1>
        <a href="dashboard.php" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; color: white; background: rgba(255, 255, 255, 0.1); padding: 0.5rem 1rem; border-radius: 8px; backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <img src="back.png" alt="Back" style="width: 20px; height: 20px;">
            <span style="font-size: 16px;">Back to Dashboard</span>
        </a>
    </header>
    <main class="main-content">
        <h2>Manage Products</h2>
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
                                <button onclick="toggleEditForm(<?php echo $product['id']; ?>)">Edit</button>

                                <!-- Delete form -->
                                <form action="dashboard_products.php" method="post" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <button type="submit" name="delete" class="delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                </form>
                            </div>

                            <!-- Edit product form (Hidden by default) -->
                            <div id="editForm_<?php echo $product['id']; ?>" style="display:none; margin-top: 10px;">
                                <form action="dashboard_products.php" method="post">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">

                                    <!-- Name input field -->
                                    <label for="name_<?php echo $product['id']; ?>">Name:</label>
                                    <input type="text" id="name_<?php echo $product['id']; ?>" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

                                    <!-- Description input field -->
                                    <label for="description_<?php echo $product['id']; ?>">Description:</label>
                                    <input type="text" id="description_<?php echo $product['id']; ?>" name="description" value="<?php echo htmlspecialchars($product['description']); ?>" required>

                                    <!-- Stock input field -->
                                    <label for="stock_<?php echo $product['id']; ?>">Stock:</label>
                                    <input type="number" id="stock_<?php echo $product['id']; ?>" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" min="0" required>

                                    <!-- Price input field -->
                                    <label for="price_<?php echo $product['id']; ?>">Price:</label>
                                    <input type="number" id="price_<?php echo $product['id']; ?>" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" required>

                                    <button type="submit" name="update">Update</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="button"><a href="add_product.php" style="color: white; text-decoration: none;">Add Product</a></button>
    </main>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>

    <script>
        // Function to toggle the visibility of the edit form
        function toggleEditForm(productId) {
            var editForm = document.getElementById("editForm_" + productId);
            if (editForm.style.display === "none" || editForm.style.display === "") {
                editForm.style.display = "block";
            } else {
                editForm.style.display = "none";
            }
        }
    </script>
</body>
</html>