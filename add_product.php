<?php
session_start();
require_once 'db_connect.php';



$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic product information
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
        $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
        $subcategory_id = filter_var($_POST['subcategory_id'], FILTER_VALIDATE_INT);
        $buying_price = filter_var($_POST['buying_price'], FILTER_VALIDATE_FLOAT);

        // Validation
        if (empty($name)) {
            $errors[] = "Product name is required";
        }
        if (empty($description)) {
            $errors[] = "Description is required";
        }
        if ($price === false || $price <= 0) {
            $errors[] = "Valid price is required";
        }
        if ($stock === false || $stock < 0) {
            $errors[] = "Valid stock quantity is required";
        }
        if ($category_id === false || $category_id <= 0) {
            $errors[] = "Valid category is required";
        }
        if ($subcategory_id === false || $subcategory_id <= 0) {
            $errors[] = "Valid subcategory is required";
        }
        if ($buying_price === false || $buying_price < 0) {
            $errors[] = "Valid buying price is required";
        }

        // If no errors, proceed with insertion
        if (empty($errors)) {
            $pdo->beginTransaction();

            // Insert product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, subcategory_id, buying_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $stock, $category_id, $subcategory_id, $buying_price]);
            $product_id = $pdo->lastInsertId();

            // Handle image uploads
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $uploadDir = 'uploads/products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Process each uploaded image
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === 0) {
                        $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                        $targetPath = $uploadDir . $fileName;

                        // Check if it's an image
                        $imageInfo = getimagesize($_FILES['images']['tmp_name'][$key]);
                        if ($imageInfo === false) {
                            throw new Exception("Invalid image file");
                        }

                        // Move uploaded file
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            // Set first image as primary
                            $isPrimary = ($key === 0) ? true : false;
                            
                            // Insert image record
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$product_id, $fileName, $isPrimary, $key + 1]);
                        } else {
                            throw new Exception("Failed to move uploaded file");
                        }
                    }
                }
            }

            $pdo->commit();
            $success_message = "Product added successfully!";
            
            // Redirect to products list or clear form
            header("Location: products.php?success=1");
            exit();

        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Get categories for the dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading categories: " . $e->getMessage();
}

// Get subcategories with their categories for the dropdown
try {
    $stmt = $pdo->query("
        SELECT s.id AS subcategory_id, s.name AS subcategory_name, s.category_id, c.name AS category_name 
        FROM subcategories s 
        JOIN categories c ON s.category_id = c.id 
        ORDER BY c.name, s.name
    ");
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading subcategories: " . $e->getMessage();
}

// Prepare subcategories for JavaScript
$subcategories_json = json_encode($subcategories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <style>
       /* css/style.css */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 600px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

h1 {
    text-align: center;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-bottom: 5px;
}

input[type="text"],
input[type="number"],
textarea,
select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #218838;
}

.error-messages {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
} 
    </style>
</head>
<body>

    <div class="container">
        <h1>Add New Product</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form action="add_product.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" required 
                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" required 
                       value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subcategory_id">Subcategory:</label>
                <select id="subcategory_id" name="subcategory_id" required>
                    <option value="">Select a subcategory</option>
                    <!-- Subcategories will be populated by JavaScript -->
                </select>
            </div>

            <div class="form-group">
                <label for="buying_price">Buying Price:</label>
                <input type="number" id="buying_price" name="buying_price" step="0.01" required 
                       value="<?php echo isset($_POST['buying_price']) ? htmlspecialchars($_POST['buying_price']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="images">Product Images:</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*">
                <small>You can select multiple images. The first image will be set as the primary image.</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Product</button>
                <a href="dashboard_products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>


    <script>
        const subcategories = <?php echo $subcategories_json; ?>;

        document.getElementById('category_id').addEventListener('change', function() {
            const selectedCategoryId = this.value;
            const subcategorySelect = document.getElementById('subcategory_id');

            // Clear existing options
            subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';

            // Filter and populate subcategories based on selected category
            subcategories.forEach(subcategory => {
                if (subcategory.category_id == selectedCategoryId) {
                    const option = document.createElement('option');
                    option.value = subcategory.subcategory_id;
                    option.textContent = subcategory.category_name + ' - ' + subcategory.subcategory_name;
                    subcategorySelect.appendChild(option);
                }
            });
        });
    </script>
</body>
</html>