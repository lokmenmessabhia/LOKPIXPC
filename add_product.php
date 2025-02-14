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
        .content {
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

        input, textarea, select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }

        input:focus, textarea:focus, select:focus {
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

        /* Smaller Button */
        .small-button {
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
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
            
            .content {
                padding: 0 1rem;
            }
            
            form {
                padding: 1.5rem;
            }
            
            button {
                width: 100%;
                margin-bottom: 0.5rem;
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