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
    <title>Add/Edit/View Features - EcoTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
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

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Form Styles */
        form {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        form:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        button {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }

        /* Table Styles */
        h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text);
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--bg-card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        th {
            background-color: #f8f9fa;
            color: var(--text);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(67, 97, 238, 0.05);
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

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text);
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }

        /* Feature images */
        .feature-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }

        .file-input-container {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .file-input-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .file-input-button:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-1px);
        }

        input[type="file"] {
            display: none;
        }

        /* Action buttons */
        .btn-update, .btn-delete {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-right: 0.5rem;
        }

        .btn-update {
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(45deg, var(--danger), #e5156c);
            color: white;
        }

        .btn-update:hover, .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            background-color: var(--bg-card);
            color: var(--text-light);
            border-top: 1px solid var(--border);
            margin-top: 3rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }
            
            form {
                padding: 1.5rem;
            }
            
            th, td {
                padding: 1rem;
            }
            
            .top-nav {
                padding: 0.75rem 1rem;
                flex-direction: column;
            }
            
            .nav-menu {
                overflow-x: auto;
                width: 100%;
                padding-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="nav-brand">
            <h1>EcoTech Admin</h1>
        </div>
        <div class="nav-menu">
            <a href="add_feature.php" class="active">Manage Features</a>
            <!-- Add other menu items as needed -->
        </div>
        <div class="nav-end">
            <a href="dashboard.php" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <main class="main-content" style="margin-top: 4.5rem;">
        <h1 class="page-title">Manage Features</h1>

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
                <div class="file-input-container">
                    <label class="file-input-button" for="photo">Choose File</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
            </div>
            <div class="input-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="is_gold" name="is_gold" style="width: auto;">
                <label for="is_gold" style="display: inline;">Make this a Gold Feature</label>
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
                    <form action="add_feature.php" method="post" enctype="multipart/form-data">
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
                        <td>
                            <div style="margin-bottom: 10px;">
                                <input type="checkbox" id="is_gold_<?php echo $feature['id']; ?>" name="is_gold" <?php echo $feature['is_gold'] ? 'checked' : ''; ?> style="width: auto;">
                                <label for="is_gold_<?php echo $feature['id']; ?>" style="display: inline;">Gold Feature</label>
                            </div>
                            <button type="submit" class="btn-update">Update</button>
                            <a href="add_feature.php?action=delete&id=<?php echo $feature['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this feature?');">Delete</a>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> EcoTech. All rights reserved.</p>
    </footer>
</body>
</html>