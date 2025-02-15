<?php
session_start();
ob_start();
include 'db_connect.php';
include 'header.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Updated login check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<div class='login-message' style='text-align: center; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; margin: 20px auto; max-width: 400px;'>";
    echo "Please login to continue. <a href='login.php' style='color: #0d6efd; text-decoration: none; margin-left: 5px;'>Login here</a>";
    echo "</div>";
    exit;
}

$user_id = $_SESSION['userid'];

// Fetch current user details
try {
    $stmt = $pdo->prepare("SELECT email , created_at, profile_picture, phone, email_verified FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('User not found');
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Fetch user orders with product details
try {
    $stmt = $pdo->prepare("
        SELECT o.id AS order_id, p.name AS product_name, pi.image_url AS product_photo, o.total_price, o.order_date, o.status, o.tracking_number 
        FROM orders o
        JOIN order_details od ON o.id = od.order_id
        JOIN products p ON od.product_id = p.id
        JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE o.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($file['name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB

    // Check if the uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxFileSize) {
        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id");
            $stmt->bindParam(':profile_picture', $file['name']);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            
            
        } else {
            $error = "Failed to upload file. Please check directory permissions.";
        }
    } else {
        $error = "Invalid file type or size.";
    }
}




// Handle phone number update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    $new_phone = $_POST['phone'];
    // Validate phone number (example validation, adjust as needed)
    if (preg_match('/^[0-9]{10}$/', $new_phone)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET phone = :phone WHERE id = :user_id");
            $stmt->bindParam(':phone', $new_phone);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $success_msg = "Phone number updated successfully!";
            $user['phone'] = $new_phone; // Update the phone number in the displayed profile
        } catch (PDOException $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Invalid phone number format.";
    }
}

// Add new form handling for email and password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Handle verification code submission
        if (isset($_POST['verify_code'])) {
            $submitted_code = $_POST['verification_token'];
            $stmt = $pdo->prepare("SELECT verification_token FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $stored_code = $stmt->fetchColumn();

            if ($submitted_code === $stored_code) {
                // Update user verification status
                $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $success_msg = "Email successfully verified!";
                $user['email_verified'] = 1; // Update current session
            } else {
                $error = "Invalid verification code. Please try again.";
            }
        }

        // Handle send verification email
        if (isset($_POST['verify_email'])) {
            // Generate 6-digit code
            $verification_token = sprintf("%06d", mt_rand(1, 999999));
            
            // Store the verification code
            $stmt = $pdo->prepare("UPDATE users SET verification_token = :code WHERE id = :user_id");
            $stmt->bindParam(':code', $verification_token);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Send verification email using PHPMailer
           

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'lokmen13.messabhia@gmail.com';
                $mail->Password = 'dfbk qkai wlax rscb';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('lokmen13.messabhia@gmail.com', 'Lokpix');
                $mail->addAddress($user['email']);
                $mail->isHTML(true);
                $mail->Subject = "Email Verification Code";
                $mail->Body = "Your verification code is: " . $verification_token;

                $mail->send();
                $show_verification_popup = true;
                $success_msg = "Verification code sent! Please check your inbox.";
            } catch (Exception $e) {
                $error = "Failed to send verification email. Error: " . $mail->ErrorInfo;
            }
        }
        
        // Change Email
        if (isset($_POST['change_email'])) {
            $new_email = $_POST['new_email'];
            if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $pdo->prepare("UPDATE users SET email = :new_email WHERE id = :user_id");
                $stmt->bindParam(':new_email', $new_email);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $success_msg = "Email updated successfully!";
                $user['email'] = $new_email;
            } else {
                $error = "Invalid email format!";
            }
        }

        // Change Password
        if (isset($_POST['change_password'])) {
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];

            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user_pwd = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($old_password, $user_pwd['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :new_password WHERE id = :user_id");
                $stmt->bindParam(':new_password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
               
                $success_msg = "Password updated successfully!";
                
            } else {
                $error = "Old password is incorrect!";
            }
        }

        // Update Phone Number
        if (isset($_POST['update_phone'])) {
            $new_phone = $_POST['phone'];
            // Validate phone number
            if (preg_match('/^[0-9]{10}$/', $new_phone)) {
                $stmt = $pdo->prepare("UPDATE users SET phone = :new_phone WHERE id = :user_id");
                $stmt->bindParam(':new_phone', $new_phone);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                header("Location: " . $_SERVER['PHP_SELF']);
                $success_msg = "Phone number updated successfully!";
                $user['phone'] = $new_phone;
            } else {
                $error = "Invalid phone number format!";
            }
        }
    } catch (PDOException $e) {
        $error = "Error: " . htmlspecialchars($e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Lokpix</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e5e9f0 100%);
            margin: 0;
            color: #1a1a1a;
            line-height: 1.6;
            min-height: 100vh;
        }

        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.04),
                0 8px 16px rgba(59, 130, 246, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        h1, h2 {
            font-size: 32px;
            margin-bottom: 35px;
            color: #2d3748;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #edf2f7;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        h1::after, h2::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, transparent);
            border-radius: 3px;
        }

        .form-group {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
        }

        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group input[type="file"] {
            flex: 1;
            min-width: 250px;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-group button {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }

        /* Updated Orders Section Styling */
        .orders-section {
            margin-top: 30px;
            padding: 25px;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .order-card {
            display: flex;
            align-items: center;
            gap: 24px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }

        .order-image {
            flex-shrink: 0;
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
        }

        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-details {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .order-details p {
            margin: 0;
            font-size: 0.95rem;
            color: #4a5568;
        }

        .order-details strong {
            color: #2d3748;
            font-weight: 600;
        }

        .view-details {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .view-details:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .order-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .order-image {
                width: 100%;
                height: 200px;
            }

            .order-details {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        /* Add these new styles */
        .verification-group {
            background: rgba(254, 243, 199, 0.5);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .verification-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .verification-badge {
            display: inline-block;
            background: rgba(251, 191, 36, 0.2);
            color: #b45309;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .verify-button {
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%) !important;
        }

        .verify-button:hover {
            background: linear-gradient(135deg, #92400e 0%, #78350f 100%) !important;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            max-width: 400px;
            width: 90%;
        }

        .popup h3 {
            margin-top: 0;
            color: #2d3748;
        }

        .popup input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }

        .popup button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }

        .close-popup {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 20px;
            color: #666;
        }

        .profile-picture {
            width: 150px;  /* Reduced from default size */
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #3b82f6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            background: rgba(255, 255, 255, 0.8);
            padding: 25px;
            border-radius: 16px;
            margin: 30px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .profile-info p {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 12px;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 10px;
            transition: transform 0.2s ease;
        }

        .profile-info p:hover {
            transform: translateX(5px);
            background: rgba(239, 246, 255, 0.8);
        }

        .profile-info strong {
            min-width: 150px;
            color: #3b82f6;
            font-weight: 600;
            margin-right: 15px;
        }

        /* Add these styles to your existing CSS */
        .drag-area {
            border: 2px dashed #3b82f6;
            height: 200px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 20px;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .drag-area.active {
            border: 2px solid #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .drag-area .icon {
            font-size: 50px;
            color: #3b82f6;
        }

        .drag-area header {
            font-size: 20px;
            font-weight: 500;
            color: #2d3748;
            margin: 10px 0;
        }

        .drag-area span {
            font-size: 14px;
            font-weight: 400;
            color: #4a5568;
            margin: 10px 0;
        }

        .drag-area .browse-btn {
            padding: 10px 25px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .drag-area .browse-btn:hover {
            background: #2563eb;
        }

        .drag-area img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .upload-btn {
            width: 10%;
            padding: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            background: #2563eb;
        }

       /* Style Option 1 - Modern Gradient */
       .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(145deg, #3498db, #2980b9);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            z-index: 1000;
            border: none;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(145deg, #2980b9, #3498db);
        }

        .back-to-top i {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .back-to-top:hover i {
            transform: translateY(-2px);
        }

        /* Optional: Add a pulse animation */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(52, 152, 219, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0);
            }
        }

        .back-to-top.visible {
            animation: pulse 2s infinite;
        }

        /* New styles for the Choose File button */
        .upload-form input[type="file"] {
            display: none; /* Hide the default file input */
        }

        .upload-form .custom-file-upload {
            display: inline-block;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .upload-form .custom-file-upload:hover {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        }
    </style>
</head>
<body>
    <main>
        <div class="profile-container">
            <div class="back-button">
                <a href="index.php">
                    <img src="back.png" alt="Back">
                </a>
            </div>
            <h1>Profile</h1>

            <div class="profile-picture">
                <img src="<?php echo htmlspecialchars($user['profile_picture']) ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" alt="Profile Picture">
            </div>

            <div class="profile-info">
                
                <p><strong>Email Address:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Date of Creation:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            </div>

       
            <div class="upload-form">
                <form action="profile.php" method="post" enctype="multipart/form-data">
                    <label class="custom-file-upload">
                        Choose File
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </label>
                    <button type="submit" class="upload-btn">Upload</button>
                </form>

            

                <?php if (isset($success_msg)) : ?>
                    <p class="alert success"><?php echo $success_msg; ?></p>
                <?php endif; ?>

                <?php if (isset($error)) : ?>
                    <p class="alert error"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>

            

            <div class="settings-section">
                <h2>Account Settings</h2>
                
                <?php if (!$user['email_verified']): ?>
                    <!-- Email Verification Form -->
                    <form method="post" action="">
                        <div class="form-group verification-group">
                            <div class="verification-info">
                                <span class="verification-badge">Unverified Email</span>
                                <p>Your email (<?php echo htmlspecialchars($user['email']); ?>) is not verified.</p>
                            </div>
                            <button type="submit" name="verify_email" class="verify-button">Send Verification Email</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <!-- Email Update Form -->
                
                
                <!-- Password Change Form -->
                <form method="post" action="">
                    <div class="form-group">
                        <label for="old_password">Old Password</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="submit" name="change_password">Update Password</button>
                    </div>
                </form>

                <!-- Add this form in the settings-section div, after the password change form -->
                <form method="post" action="">
                    <div class="form-group">
                        <label for="phone">Edit Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        <button type="submit" name="update_phone">Edit Phone</button>
                    </div>
                </form>
            </div>

            <div class="orders-section">
                <h2>Your Orders</h2>
                <?php if (count($orders) > 0): ?>
                    <div class="order-card-container">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-image">
                                    <img src="<?php echo 'uploads/products/' . htmlspecialchars($order['product_photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                                </div>
                                <div class="order-details">
                                    <div>
                                        <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
                                        <p><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
                                    </div>
                                    <div>
                                        <p><strong>Price:</strong> <?php echo htmlspecialchars($order['total_price']); ?> DA</p>
                                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                                        <?php if (!empty($order['tracking_number'])): ?>
                                            <p><strong>Tracking:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                            <a href="track_order.php?tracking_number=<?php echo htmlspecialchars($order['tracking_number']); ?>" 
                                               class="view-details">Track Order</a>
                                        <?php else: ?>
                                            <p>No tracking information available.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="popup-overlay" id="verificationPopup">
        <div class="popup">
            <span class="close-popup" onclick="closePopup()">&times;</span>
            <h3>Enter Verification Code</h3>
            <p>Please enter the verification code sent to your email.</p>
            <form method="post" action="">
                <input type="text" name="verification_token" placeholder="Enter 6-digit code" required>
                <button type="submit" name="verify_code">Verify</button>
            </form>
        </div>
    </div>
   
    <script>
        function closePopup() {
            document.getElementById('verificationPopup').style.display = 'none';
        }

        <?php if (isset($show_verification_popup) && $show_verification_popup): ?>
        document.getElementById('verificationPopup').style.display = 'block';
        <?php endif; ?>
   
    </script>
<?php   include'footer.php' ?>
    <button class="back-to-top" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Show the button when scrolling down
        window.onscroll = function() {
            const button = document.getElementById('backToTop');
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                button.style.display = "block";
            } else {
                button.style.display = "none";
            }
        };

        // Scroll to the top of the document
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        // Add this JavaScript for the Back to Top button
        const backToTopButton = document.querySelector('.back-to-top');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) { // Show button after scrolling 300px
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>

    <!-- Add Font Awesome for the upload icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</body>
</html>