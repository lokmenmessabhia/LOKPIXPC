<?php
include 'db_connect.php'; // Ensure this path is correct

// Handle Add Feature
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $photo = '';
    $is_gold = isset($_POST['is_gold']) ? 1 : 0;

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['photo']['tmp_name'];
        $photo = basename($_FILES['photo']['name']);
        move_uploaded_file($tmp_name, "uploads/$photo");
    }

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO features (title, description, photo, is_gold, created_at) VALUES (:title, :description, :photo, :is_gold, NOW())");
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':photo' => $photo,
        ':is_gold' => $is_gold
    ]);

    echo "Feature added successfully!";
}

// Handle Edit Feature
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $photo = $_POST['existing_image'];
    $is_gold = isset($_POST['is_gold']) ? 1 : 0;

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['photo']['tmp_name'];
        $photo = basename($_FILES['photo']['name']);
        move_uploaded_file($tmp_name, "uploads/$photo");
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE features SET title = :title, description = :description, photo = :photo, is_gold = :is_gold WHERE id = :id");
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':photo' => $photo,
        ':is_gold' => $is_gold,
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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4ff; /* Light blue background */
            margin: 0;
            padding: 0;
        }

        header {
            background: linear-gradient(135deg, #6366f1, #3b82f6); /* Gradient background */
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 800px;
            transition: transform 0.3s;
        }

        .main-content:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: #003366; /* Dark blue for headings */
            border-bottom: 2px solid #007bff; /* Blue border for headings */
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #003366; /* Dark blue for labels */
        }

        .input-group input[type="text"],
        .input-group textarea,
        .input-group input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #007bff; /* Blue border for input fields */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2); /* Subtle shadow */
            transition: border-color 0.3s, box-shadow 0.3s; /* Smooth transition */
        }

        .input-group input[type="text"]:focus,
        .input-group textarea:focus {
            border-color: #0056b3; /* Darker blue on focus */
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Glow effect on focus */
            outline: none;
        }

        .input-group input[type="file"] {
            padding: 0.5rem; /* Adjusted padding for file input */
            border: 1px solid #007bff; /* Blue border for file input */
            border-radius: 10px; /* Rounded corners */
            background-color: #f9f9f9; /* Light background for file input */
            transition: border-color 0.3s; /* Smooth transition */
        }

        .input-group input[type="file"]:focus {
            border-color: #0056b3; /* Darker blue on focus */
            outline: none;
        }

        button {
            background:linear-gradient(135deg, #6366f1, #3b82f6); /* Gradient background */
            color: white;
            border: none;
            padding: 0.75rem 1.5rem; /* Increased padding for buttons */
            border-radius: 10px; /* Rounded corners */
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s; /* Added transition effects */
            font-size: 1rem; /* Increased font size for better readability */
            font-weight: bold; /* Bold text for emphasis */
            box-shadow: 0 4px 10px rgba(0, 86, 179, 0.3); /* Shadow for depth */
        }

        button:hover {
            background: linear-gradient(135deg, #6366f1, #3b82f6); /* Darker gradient on hover */
            transform: scale(1.05); /* Slightly enlarge on hover */
        }

        button:active {
            transform: scale(0.95); /* Slightly shrink on click */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Added shadow for depth */
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #e7f1ff; /* Light blue for table headers */
            font-weight: bold;
            color: #003366; /* Dark blue text for headers */
        }

        tr:hover {
            background-color: #f1f1f1; /* Light gray background on row hover */
        }

        a {
            color: #007bff; /* Blue links */
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            text-decoration: underline;
            color: #0056b3; /* Darker blue on hover */
        }

        /* Table input styles */
        td input[type="text"],
        td textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background-color: #fff;
            margin-bottom: 0.5rem;
        }

        td input[type="text"]:focus,
        td textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        td textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* File input container */
        .file-input-container {
            position: relative;
            margin-bottom: 0.5rem;
        }

        /* Custom file input button */
        .file-input-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .file-input-button:hover {
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        /* Hide the default file input */
        td input[type="file"] {
            display: none;
        }

        /* Action buttons container */
        .table-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* Update and Delete buttons */
        .btn-update,
        .btn-delete {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-update {
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
        }

        .btn-update:hover,
        .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        /* Table cell spacing */
        td {
            padding: 1rem;
            vertical-align: top;
        }

        /* Image preview */
        .feature-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 1rem;">
        <h1 style="font-size: 2rem;">Add/Edit/View Features</h1>
        <a href="dashboard.php" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; color: white; background: rgba(255, 255, 255, 0.1); padding: 0.5rem 1rem; border-radius: 8px; backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <img src="back.png" alt="Back" style="width: 20px; height: 20px;">
            <span style="font-size: 16px;">Back to Dashboard</span>
        </a>
    </header>

    <div class="main-content">
        

        <h2>Add New Feature</h2>
        <form action="add_feature.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="input-group">
                <label for="title">Feature Name</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="input-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="input-group">
                <label for="photo">Feature Image</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>
            <div class="input-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="is_gold" name="is_gold" style="width: auto;">
                <label for="is_gold">Make this a Gold Feature</label>
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
                    <form action="add_feature.php" method="post" enctype="multipart/form-data" style="display:inline;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $feature['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($feature['photo']); ?>">
                        <td>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($feature['title']); ?>" required>
                        </td>
                        <td>
                            <textarea name="description" required><?php echo htmlspecialchars($feature['description']); ?></textarea>
                        </td>
                        <td>
                            <img src="uploads/<?php echo htmlspecialchars($feature['photo']); ?>" alt="Image" class="feature-image">
                            <div class="file-input-container">
                                <label class="file-input-button" for="file-input-<?php echo $feature['id']; ?>">Change Photo</label>
                                <input type="file" id="file-input-<?php echo $feature['id']; ?>" name="photo" accept="image/*">
                            </div>
                        </td>
                        <td class="table-actions">
                            <div style="margin-bottom: 10px;">
                                <input type="checkbox" id="is_gold_<?php echo $feature['id']; ?>" name="is_gold" <?php echo $feature['is_gold'] ? 'checked' : ''; ?> style="width: auto;">
                                <label for="is_gold_<?php echo $feature['id']; ?>">Gold Feature</label>
                            </div>
                            <button type="submit" class="btn-update">Update</button>
                            <a href="add_feature.php?action=delete&id=<?php echo $feature['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this feature?');">Delete</a>
                        </td>
                    </form>
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