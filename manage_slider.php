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

        /* Slider List Styles */
        .slider-list {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .slider-list li {
            background: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            width: 150px; /* Adjust the width to make the photos smaller */
        }

        .slider-list li:hover {
            transform: translateY(-5px);
        }

        .slider-list img {
            width: 100%;
            height: auto;
            display: block;
        }

        .slider-list .caption {
            padding: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .slider-list .actions {
            padding: 0.5rem;
            text-align: center;
        }

        .slider-list .actions a {
            color: var(--primary-gradient);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .slider-list .actions a:hover {
            color: var(--secondary-gradient);
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
<?php 
include 'footer.php';
?>
</body>
</html>