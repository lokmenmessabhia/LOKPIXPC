<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
session_start();
include 'db_connect.php'; // Ensure this path is correct

$showModal = false; // Initialize popup flag
$verification_message = ''; // Variable to store verification result message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sign-up logic
    if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $verification_token = mt_rand(100000, 999999); // 6-digit verification code

        // Check if the email or username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo "Error: This email or username is already registered. Please use different credentials.";
        } else {
            // Insert user into the database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, verification_token, email_verified) VALUES (?, ?, ?, ?, ?, 0)");
            if ($stmt->execute([$username, $email, $phone, $password, $verification_token])) {
                // Send verification email (optional since the user enters the code in the popup)
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'lokmen13.messabhia@gmail.com';
                    $mail->Password   = 'dfbk qkai wlax rscb'; // Use app-specific password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('lokmen13.messabhia@gmail.com', 'Lokpix');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Email Verification';
                    $mail->Body    = "Thank you for signing up with Lokpix!
                     Please enter the verification code below 
                     to confirm your email address: <br><br>
                                      <strong>$verification_token</strong>";

                    $mail->send();
                    $_SESSION['email'] = $email; // Store email in session for verification
                    $showModal = true; // Trigger the popup on successful registration
                } catch (Exception $e) {
                    echo "Error: Email could not be sent. {$mail->ErrorInfo}";
                }
            } else {
                echo "Error: Could not register. Please try again.";
            }
        }
    }
}

// Token verification logic (POST from modal)
if (isset($_POST['verification_token']) && isset($_SESSION['email'])) {
    $verification_token = $_POST['verification_token'];
    $email = $_SESSION['email'];

    // Fetch stored verification token from the database
    $stmt = $pdo->prepare("SELECT verification_token FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['verification_token'] == $verification_token) {
            // Update user as verified
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE email = ?");
            $stmt->execute([$email]);

            // Clear session variable to avoid re-verification
            unset($_SESSION['email']);

            // Redirect to login page after successful verification
            header("Location: login.php");
            exit(); // Stop further execution
        } else {
            $verification_message = 'Invalid token. Please try again.';
        }
    } else {
        $verification_message = 'User not found. Please try again.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EcoTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --error-color: #ef4444;
            --success-color: #22c55e;
            --background: #f5f7ff;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --input-border: #e2e8f0;
            --input-background: #f8fafc;
        }

        body {
            margin: 0;
            padding: 2rem;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
            background: var(--background);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(79, 70, 229, 0.05) 0%, transparent 20%);
            padding-top: 6rem;
        }

        .logo-container {
            position: fixed;
            top: 2rem;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            z-index: 1000;
        }

        .logo {
            width: 140px;
            height: auto;
            animation: fadeIn 0.6s ease-out;
        }

        .wrapper {
            background: var(--card-background);
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.05), 0 8px 10px -6px rgb(0 0 0 / 0.05);
            width: 100%;
            max-width: 440px;
            margin: 1rem;
            position: relative;
            overflow: hidden;
        }

        .wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), #818cf8);
        }

        .title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }

        .field {
            margin-bottom: 1.5rem;
        }

        .field label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.925rem;
        }

        .field input[type="text"],
        .field input[type="email"],
        .field input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 0.75rem;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
            background-color: var(--input-background);
            font-family: 'Poppins', sans-serif;
        }

        .field input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--text-primary);
        }

        .eye-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        .field input[type="submit"] {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        .field input[type="submit"]:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1), 0 2px 4px -2px rgba(79, 70, 229, 0.1);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.925rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: var(--primary-hover);
        }

        .error {
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            text-align: center;
            font-size: 0.925rem;
            font-weight: 500;
            color: var(--error-color);
            background: rgb(239 68 68 / 0.08);
            border: 1px solid rgba(239, 68, 68, 0.1);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
                background-image: none;
                padding-top: 5rem;
            }
            
            .wrapper {
                padding: 1.5rem;
                margin: 0;
                border-radius: 1rem;
            }

            .title {
                font-size: 1.5rem;
                margin-bottom: 2rem;
            }

            .field {
                margin-bottom: 1.25rem;
            }

            .field input[type="text"],
            .field input[type="email"],
            .field input[type="password"] {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }

            .logo-container {
                top: 1.5rem;
            }

            .logo {
                width: 120px;
            }
        }

        @media (max-width: 360px) {
            .wrapper {
                padding: 1.25rem;
            }

            .title {
                font-size: 1.25rem;
            }

            .field label {
                font-size: 0.875rem;
            }
        }

        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 0.5rem;
                padding-top: 4rem;
            }

            .wrapper {
                margin: 0;
                padding: 1.25rem;
            }

            .logo-container {
                top: 1rem;
            }

            .logo {
                width: 100px;
            }
        }

        .field input[type="tel"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 0.75rem;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
            background-color: var(--input-background);
            font-family: 'Poppins', sans-serif;
        }

        .field input[type="tel"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="logo (1).png" alt="EcoTech Logo" class="logo">
    </div>

    <div class="wrapper">
        <div class="title">Create Account</div>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="sign-up.php" method="post">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username">
            </div>
            
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>

            <div class="field">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
            </div>
            
            <div class="field">
                <label for="password">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="field">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="field">
                <input type="submit" value="Sign Up">
            </div>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            const button = input.nextElementSibling;
            const svg = button.querySelector('svg');
            if (type === 'text') {
                svg.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                svg.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }
    </script>
</body>
</html>
