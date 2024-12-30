<?php
session_start(); // Start the session
ob_start(); // Start output buffering
include 'db_connect.php'; // Ensure this path is correct
include 'header.php';

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<div class='login-message' style='text-align: center; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; margin: 20px auto; max-width: 400px;'>";
    echo "Please login to continue. <a href='login.php' style='color: #0d6efd; text-decoration: none; margin-left: 5px;'>Login here</a>";
    echo "</div>";
    exit;
}
 else {
    // Fetch user data
    $user_id = $_SESSION['userid']; // Get the user ID from session
    if (!isset($user_id)) {
        // Redirect to login page if the user ID doesn't exist
        header('Location: login.php');
        exit;
    }

    // Prepare SQL to fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try {
            // Change Email
            if (isset($_POST['change_email'])) {
                $new_email = $_POST['new_email'];
                if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $stmt = $pdo->prepare("UPDATE users SET email = :new_email WHERE id = :user_id");
                    $stmt->bindParam(':new_email', $new_email);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    echo "<div class='alert success'>Email updated successfully!</div>";
                } else {
                    echo "<div class='alert error'>Invalid email format!</div>";
                }
            }

            // Change Password
            if (isset($_POST['change_password'])) {
                $old_password = $_POST['old_password'];
                $new_password = $_POST['new_password'];

                // Verify old password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($old_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = :new_password WHERE id = :user_id");
                    $stmt->bindParam(':new_password', $hashed_password);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    echo "<div class='alert success'>Password updated successfully!</div>";
                } else {
                    echo "<div class='alert error'>Old password is incorrect!</div>";
                }
            }

            // Update Phone Number
            if (isset($_POST['update_phone'])) {
                $new_phone = $_POST['phone'];
                // Validate phone number
                if (preg_match('/^[0-9]{10}$/', $new_phone)) { // Adjust regex as needed
                    $stmt = $pdo->prepare("UPDATE users SET phone = :new_phone WHERE id = :user_id");
                    $stmt->bindParam(':new_phone', $new_phone);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    echo "<div class='alert success'>Phone number updated successfully!</div>";
                } else {
                    echo "<div class='alert error'>Invalid phone number format!</div>";
                }
            }
        } catch (PDOException $e) {
            echo "<div class='alert error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    ob_end_flush(); // End the output buffer
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Lokpix</title>
     <!-- Ensure this path is correct -->
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

.container {
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

h2 {
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

h2::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100px;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6, transparent);
    border-radius: 3px;
}

form {
    margin-bottom: 35px;
    padding: 25px;
    border-radius: 16px;
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid rgba(59, 130, 246, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

form:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.06);
}

.form-group {
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    position: relative;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #4a5568;
    width: 140px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    position: relative;
    padding-left: 20px;
}

.form-group label::before {
    content: '→';
    position: absolute;
    left: 0;
    opacity: 0;
    transition: all 0.3s ease;
}

.form-group:hover label::before {
    opacity: 1;
    color: #3b82f6;
}

.form-group:hover label {
    color: #3b82f6;
    transform: translateX(5px);
}

.form-group input {
    flex: 1;
    min-width: 250px;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background-color: rgba(255, 255, 255, 0.9);
}

.form-group input:hover {
    border-color: #cbd5e1;
    background-color: #ffffff;
}

.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    background-color: #ffffff;
    transform: translateY(-1px);
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
    position: relative;
    overflow: hidden;
}

.form-group button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: 0.5s;
}

.form-group button:hover::before {
    left: 100%;
}

.form-group button:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
}

.alert {
    padding: 16px 24px;
    margin-bottom: 25px;
    border-radius: 14px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    animation: slideIn 0.4s ease;
    backdrop-filter: blur(8px);
}

@keyframes slideIn {
    from {
        transform: translateY(-10px) scale(0.98);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.alert.success {
    background: linear-gradient(135deg, rgba(220, 252, 231, 0.9) 0%, rgba(187, 247, 208, 0.9) 100%);
    color: #166534;
    border: 1px solid rgba(134, 239, 172, 0.5);
}

.alert.error {
    background: linear-gradient(135deg, rgba(254, 226, 226, 0.9) 0%, rgba(254, 202, 202, 0.9) 100%);
    color: #991b1b;
    border: 1px solid rgba(252, 165, 165, 0.5);
}

@media (max-width: 768px) {
    .container {
        margin: 20px auto;
        padding: 25px;
        border-radius: 20px;
    }

    form {
        padding: 20px;
    }

    .form-group {
        flex-direction: column;
        align-items: stretch;
    }

    .form-group label {
        width: 100%;
        margin-bottom: 8px;
    }

    .form-group input {
        width: 100%;
    }

    .form-group button {
        width: 100%;
        margin-top: 10px;
    }

    h2 {
        font-size: 24px;
        margin-bottom: 25px;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h2>Account Settings</h2>
        
        <!-- Email Update Form -->
        <form method="post" action="">
            <div class="form-group">
                <label for="new_email">New Email</label>
                <input type="email" id="new_email" name="new_email" required>
                <button type="submit" name="change_email">Update Email</button>
            </div>
        </form>
        
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
        
        <!-- Phone Update Form -->
        <form method="post" action="">
            <div class="form-group">
                <label for="phone">Edit Phone Number</label>
                <input type="tel" id="phone" name="phone" value="" required>
                <button type="submit" name="update_phone">Edit Phone</button>
            </div>
        </form>
    </div>
    <?php include'footer.php' ?>
</body>
</html>