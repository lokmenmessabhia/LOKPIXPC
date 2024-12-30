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
