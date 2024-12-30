<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

// Handle add photo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $photo_tmp_name = $_FILES['photo']['tmp_name'];
        $photo_name = basename($_FILES['photo']['name']);
        $photo_path = 'uploads/' . $photo_name;
        move_uploaded_file($photo_tmp_name, $photo_path);

        $caption = trim($_POST['caption']);

        try {
            $stmt = $pdo->prepare("INSERT INTO slider_photos (photo_url, caption) VALUES (?, ?)");
            $stmt->execute([$photo_path, $caption]);
            header("Location: manage_slider.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Handle delete photo
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $photo_id = (int)$_GET['id'];

    try {
        // Fetch photo details
        $stmt = $pdo->prepare("SELECT photo_url FROM slider_photos WHERE id = ?");
        $stmt->execute([$photo_id]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($photo) {
            // Delete the photo file from server
            unlink($photo['photo_url']);

            // Delete the photo record from database
            $stmt = $pdo->prepare("DELETE FROM slider_photos WHERE id = ?");
            $stmt->execute([$photo_id]);

            header("Location: manage_slider.php");
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Fetch existing slider photos
try {
    $stmt = $pdo->query("SELECT * FROM slider_photos");
    $slider_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: Unable to fetch slider photos. " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Slider Photos</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Styles for the manage slider page */
        <style>
    /* Styles for the manage slider page */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    main {
        padding: 20px;
        max-width: 800px;
        margin: auto;
    }

    h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 20px;
    }

    label {
        font-weight: bold;
    }

    input[type="text"], input[type="file"] {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background-color: #0056b3;
    }

    .slider-list {
        list-style: none;
        padding: 0;
    }

    .slider-list li {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 15px;
        background-color: #f9f9f9;
    }

    .slider-list img {
        max-width: 150px; /* Adjust the width as needed */
        max-height: 100px; /* Adjust the height as needed */
        object-fit: cover; /* Ensures the image covers the dimensions without distortion */
        border-radius: 5px;
    }

    .slider-list .caption {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .slider-list .actions {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .slider-list .actions a {
        color: #dc3545;
        text-decoration: none;
    }


        header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #343a40;
    color: #ffffff;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

header h1 {
    font-size: 1.8em;
    margin: 0;
}

header a {
    color: #ffffff;
    text-decoration: none;
    display: flex;
    align-items: center;
}

header a img {
    margin-right: 10px;
}

      
       
    </style>
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
        <h1>Manage Slider Photos</h1>

        <!-- Form to upload new photo -->
        <form action="manage_slider.php" method="post" enctype="multipart/form-data">
            <label for="photo">Upload Slider Photo:</label>
            <input type="file" id="photo" name="photo" required>

            <label for="caption">Caption:</label>
            <input type="text" id="caption" name="caption">

            <button type="submit" name="action" value="add">Add Photo</button>
        </form>

        <!-- List of existing slider photos -->
        <h2>Existing Slider Photos</h2>
        <ul class="slider-list">
            <?php foreach ($slider_photos as $photo): ?>
                <li>
                    <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>" alt="<?php echo htmlspecialchars($photo['caption']); ?>">
                    <div class="caption"><?php echo htmlspecialchars($photo['caption']); ?></div>
                    <div class="actions">
                        <a href="manage_slider.php?action=delete&id=<?php echo $photo['id']; ?>">Delete</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>
