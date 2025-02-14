<?php
session_start();
include 'db_connect.php';

// Add at the top with other includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
// Initialize variables
$email = '';
$password = '';
$error = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle forgot password request
    if (isset($_POST['action']) && $_POST['action'] == 'forgot_password') {
        // Add error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $email = $_POST['email'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate reset token and expiry (24 hours from now)
                $reset_token = bin2hex(random_bytes(32));
                $reset_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Store reset token and expiry in database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
                $stmt->execute([$reset_token, $reset_expiry, $email]);
                
                // Update the reset link generation to use the correct path
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $reset_token;

                // For debugging, you can log the generated URL
                error_log("Reset link generated: " . $reset_link);
                
                // Create PHPMailer instance
                $mail = new PHPMailer(true);

                // Server settings
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'lokmen13.messabhia@gmail.com'; // Your Gmail address
                $mail->Password = 'dfbk qkai wlax rscb'; // Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('lokmen13.messabhia@gmail.com', 'EcoTech Support');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Click the following link to reset your password: <br><a href='{$reset_link}'>{$reset_link}</a><br>This link will expire in 24 hours.";
                $mail->AltBody = "Click the following link to reset your password: {$reset_link}\nThis link will expire in 24 hours.";

                $mail->send();
                echo json_encode(['status' => 'success', 'message' => 'Password reset instructions have been sent to your email']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Email address not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check for user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
       if (password_verify($password, $user['password'])) {
            // User login successful
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user';
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EcoTech</title>
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
            padding-top: 2rem;
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

        .field input[type="email"],
        .field input[type="password"],
        .field input[type="text"] {
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

        .content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox input[type="checkbox"] {
            accent-color: var(--primary-color);
        }

        .checkbox label {
            color: var(--text-secondary);
            font-size: 0.925rem;
        }

        .content a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.925rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .content a:hover {
            color: var(--primary-hover);
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

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.925rem;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .signup-link a:hover {
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

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
        }

        .modal-content {
            background: var(--card-background);
            margin: 15% auto;
            padding: 2.5rem;
            width: 90%;
            max-width: 440px;
            border-radius: 1.5rem;
            position: relative;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }

        .modal-content h2 {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }

        .submit-btn {
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
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1), 0 2px 4px -2px rgba(79, 70, 229, 0.1);
        }

        .submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: none;
            background: var(--background);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.25rem;
        }

        .close:hover {
            background: var(--input-border);
            color: var(--text-primary);
        }

        #forgot-email {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 0.75rem;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
            background-color: var(--input-background);
            font-family: 'Poppins', sans-serif;
            margin-bottom: 1rem;
        }

        #forgot-email:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.925rem;
        }

        .message {
            padding: 1rem 1.25rem;
            margin: 1rem 0;
            border-radius: 0.75rem;
            text-align: center;
            font-size: 0.925rem;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        .message.success {
            color: var(--success-color);
            background: rgb(34 197 94 / 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }

        .message.error {
            color: var(--error-color);
            background: rgb(239 68 68 / 0.08);
            border: 1px solid rgba(239, 68, 68, 0.1);
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

            .field input[type="email"],
            .field input[type="password"],
            .field input[type="text"],
            #forgot-email {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }

            .content {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .checkbox {
                margin-bottom: 0.5rem;
            }

            .field input[type="submit"],
            .submit-btn {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }

            .modal-content {
                margin: 5% auto;
                padding: 1.5rem;
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
            }

            .modal-content h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .close {
                right: 1rem;
                top: 1rem;
            }

            .message {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }

            .signup-link {
                font-size: 0.875rem;
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

            .field label,
            .checkbox label {
                font-size: 0.875rem;
            }

            .content a {
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

            .modal-content {
                margin: 2% auto;
            }

            .title {
                margin-bottom: 1.5rem;
            }

            .field {
                margin-bottom: 1rem;
            }

            .logo-container {
                top: 1rem;
            }

            .logo {
                width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo-container">
            <img src="logo (1).png" alt="EcoTech Logo" class="logo">
        </div>
        <div class="title">Welcome Back</div>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="field">
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" placeholder=" ">
                <label for="email">Email</label>
            </div>
            <div class="field">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Password</label>
            </div>
            <div class="content">
                <div class="checkbox">
                    <input type="checkbox" id="remember-me" name="remember-me">
                    <label for="remember-me">Remember me</label>
                </div>
                <a href="#" onclick="showForgotPassword()" style="color: #4682B4;">Forgot Password?</a>
            </div>
            <div class="field">
                <input type="submit" value="Login">
            </div>
            <div class="signup-link">
                Don't have an account? <a href="sign-up.php">Sign Up</a>
            </div>
        </form>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
            <h2>Reset Password</h2>
            <div class="field">
                <label for="forgot-email">Email Address</label>
                <input type="email" id="forgot-email" required placeholder="Enter your email">
            </div>
            <button onclick="sendResetLink()" class="submit-btn">Send Reset Link</button>
        </div>
    </div>

    <script>
    const modal = document.getElementById('forgotPasswordModal');
    
    function showForgotPassword() {
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    function sendResetLink() {
        const email = document.getElementById('forgot-email').value;
        const submitBtn = document.querySelector('.submit-btn');
        const modalContent = document.querySelector('.modal-content');
        
        // Remove any existing message
        const existingMessage = modalContent.querySelector('.message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        if (!email) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message error';
            messageDiv.textContent = 'Please enter your email address';
            modalContent.querySelector('h2').insertAdjacentElement('afterend', messageDiv);
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        
        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=forgot_password&email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${data.status}`;
            messageDiv.textContent = data.message;
            modalContent.querySelector('h2').insertAdjacentElement('afterend', messageDiv);
            
            if (data.status === 'success') {
                document.getElementById('forgot-email').value = '';
                // Close modal after 3 seconds on success
                setTimeout(closeModal, 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message error';
            messageDiv.textContent = 'An error occurred. Please try again.';
            modalContent.querySelector('h2').insertAdjacentElement('afterend', messageDiv);
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Reset Link';
        });
    }
    </script>
</body>
</html>
