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
</head>
<body>
    <header>
        <h1 style="position: left;">Manage Products</h1>
        <a href="dashboard.php" style="text-decoration: none;">
            <img src="back.png" alt="Back" style="width: 30px; height: 30px; vertical-align: middle;">
            <span style="color: #fff; font-size: 18px; vertical-align: middle;">Back to Dashboard</span>
        </a>
    </header>

    <main>
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
                        <td><?php echo htmlspecialchars($product['price']); ?>   DZD</td>
                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                        <td>
                            <!-- View product details (Non-editable) -->
                            <button onclick="toggleEditForm(<?php echo $product['id']; ?>)">Edit</button>

                            <!-- Delete form -->
                            <form action="dashboard_products.php" method="post" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                            </form>

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
        <button> <a href="add_product.php">Add Product</a></button>
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
