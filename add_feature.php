<?php
include 'db_connect.php'; // Ensure this path is correct

// Handle Add Feature
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $feature_name = $_POST['feature_name'];
    $feature_description = $_POST['feature_description'];
    $feature_image = '';

    // Handle file upload
    if (isset($_FILES['feature_image']) && $_FILES['feature_image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['feature_image']['tmp_name'];
        $feature_image = basename($_FILES['feature_image']['name']);
        move_uploaded_file($tmp_name, "uploads/$feature_image");
    }

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO features (title, description, photo, created_at) VALUES (:title, :description, :photo, NOW())");
    $stmt->execute([
        ':title' => $feature_name,
        ':description' => $feature_description,
        ':photo' => $feature_image
    ]);

    echo "Feature added successfully!";
}

// Handle Edit Feature
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $feature_name = $_POST['feature_name'];
    $feature_description = $_POST['feature_description'];
    $feature_image = $_POST['existing_image'];

    // Handle file upload
    if (isset($_FILES['feature_image']) && $_FILES['feature_image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['feature_image']['tmp_name'];
        $feature_image = basename($_FILES['feature_image']['name']);
        move_uploaded_file($tmp_name, "uploads/$feature_image");
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE features SET title = :title, description = :description, photo = :photo WHERE id = :id");
    $stmt->execute([
        ':title' => $feature_name,
        ':description' => $feature_description,
        ':photo' => $feature_image,
        ':id' => $id
    ]);

    echo "Feature updated successfully!";
}

// Handle Delete Feature
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM features WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo "Feature deleted successfully!";
}

// Fetch all features to display
$stmt = $pdo->prepare("SELECT * FROM features ORDER BY created_at DESC");
$stmt->execute();
$features = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add/Edit/View Features - Lokpix</title>
    <link rel="stylesheet" href="add_admin.css"> <!-- Ensure this path is correct -->
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

        /* File Input Styles */
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-container input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
        }

        .file-input-label {
            background: var(--primary-gradient);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
            display: inline-block;
        }

        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
        }

        /* Button Styles */
        button {
            background: var(--primary-gradient);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
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

        /* Delete Link Style */
        a[href*="delete"] {
            display: inline-block;
            background-color: #ff3366;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }

        a[href*="delete"]:hover {
            background-color: #ff1a4d;
            transform: translateY(-1px);
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
        <h1 style="position: left;">Add/Edit/View Features</h1>
        <a href="dashboard.php" style="text-decoration: none;">
            <img src="back.png" alt="Back" style="width: 30px; height: 30px; vertical-align: middle;">
            <span style="color: #fff; font-size: 18px; vertical-align: middle;">Back to Dashboard</span>
        </a>
    </header>

    <div class="main-content">
        

        <h2>Add New Feature</h2>
        <form action="add_feature.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="input-group">
                <label for="feature-name">Feature Name</label>
                <input type="text" id="feature-name" name="feature_name" required>
            </div>
            <div class="input-group">
                <label for="feature-description">Description</label>
                <textarea id="feature-description" name="feature_description" required></textarea>
            </div>
            <div class="input-group">
                <label for="feature-image">Feature Image</label>
                <input type="file" id="feature-image" name="feature_image" accept="image/*">
            </div>
            <button type="submit">Add Feature</button>
        </form>

        <h2>View/Edit/Delete Features</h2>
        <table>
            <thead>
                <tr>
                    <th>Feature Name</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($features as $feature): ?>
                <tr>
                    <td><?php echo htmlspecialchars($feature['title']); ?></td>
                    <td><?php echo htmlspecialchars($feature['description']); ?></td>
                    <td><img src="uploads/<?php echo htmlspecialchars($feature['photo']); ?>" alt="Image" style="width: 100px;"></td>
                    <td>
                        <!-- Edit Feature Form -->
                        <form action="add_feature.php" method="post" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $feature['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($feature['photo']); ?>">
                            <input type="text" name="feature_name" value="<?php echo htmlspecialchars($feature['title']); ?>" required>
                            <textarea name="feature_description" required><?php echo htmlspecialchars($feature['description']); ?></textarea>
                            <input type="file" name="feature_image" accept="image/*">
                            <button type="submit">Update</button>
                        </form>

                        <!-- Delete Feature -->
                        <a href="add_feature.php?action=delete&id=<?php echo $feature['id']; ?>" onclick="return confirm('Are you sure you want to delete this feature?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>