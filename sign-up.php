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
    if (isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $verification_token = mt_rand(100000, 999999); // 6-digit verification code

        // Check if the email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo "Error: This email is already registered. Please use a different email.";
        } else {
            // Insert user into the database (including phone number and verification token)
            $stmt = $pdo->prepare("INSERT INTO users (email, phone, password, verification_token, email_verified) VALUES (?, ?, ?, ?, 0)");
            if ($stmt->execute([$email, $phone, $password, $verification_token])) {
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
    }}

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
    <title>Sign Up - Lokpix</title>
   <!-- <link rel="stylesheet" href="stylelog.css">  Ensure this path is correct -->
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #87CEEB, #4682B4);
        }

        .wrapper {
            width: 380px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(70, 130, 180, 0.2);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            padding: 30px;
        }

        .wrapper:hover {
            transform: translateY(-5px);
        }

        .title {
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            color: #4682B4;
        }

        /* Social Login Buttons */
        .social-login {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }

        .social-btn {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            width: 100%;
            font-size: 14px;
            text-align: center;
            justify-content: center;
            position: relative;
        }

        .facebook-login {
            background: #1877f2;
            color: white;
        }

        .facebook-login:hover {
            background: #0d65d9;
        }

        .google-login {
            background: white;
            border: 1px solid #ddd;
            color: #444;
        }

        .google-login:hover {
            background: #f8f8f8;
        }

        .social-btn img {
            width: 24px;
            height: 24px;
            position: absolute;
            left: 15px;
        }

        .social-btn span {
            flex: 1;
            text-align: center;
        }

        /* Divider */
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
            color: #666;
        }

        .divider::before,
        .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .field {
            margin-bottom: 20px;
            position: relative;
        }

        .field input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        .field input:focus {
            border-color: #2575fc;
            box-shadow: 0 0 10px rgba(37, 117, 252, 0.1);
        }

        .field label {
            position: absolute;
            top: -10px;
            left: 10px;
            
            padding: 0 5px;
            font-size: 14px;
            color: #666;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #87CEEB, #4682B4);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
            background: linear-gradient(135deg, #4682B4, #87CEEB);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #2575fc;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .modal h2 {
            color: #2575fc;
            margin-bottom: 20px;
            text-align: center;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #2575fc;
        }

        #verification_message {
            text-align: center;
            margin-top: 15px;
            color: #ff3333;
        }

        @media (max-width: 480px) {
            .wrapper {
                width: 90%;
                margin: 20px;
            }
        }
    </style>
    <!-- Include Google API JavaScript library -->
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" content="704518468882-mns555dgfcm37q89vula0c68jh6kcdb1.apps.googleusercontent.com"> <!-- Replace with your client ID -->
    <script>
        function onLoad() {
            gapi.load('auth2', function() {
                gapi.auth2.init({
                    client_id: '704518468882-mns555dgfcm37q89vula0c68jh6kcdb1.apps.googleusercontent.com' // Ensure this is your actual client ID
                });
            });
        }

        function onSignIn(googleUser) {
            var profile = googleUser.getBasicProfile();
            var email = profile.getEmail(); // Get the user's email
            // Send the email to your server for registration or login
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'sign-up.php');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                // Handle response from server
                console.log(xhr.responseText); // Check for any server response
            };
            xhr.send('email=' + encodeURIComponent(email) + '&phone=' + encodeURIComponent('')); // Add phone if needed
        }

        function signOut() {
            var auth2 = gapi.auth2.getAuthInstance();
            auth2.signOut().then(function () {
                console.log('User signed out.');
            });
        }
    </script>
</head>
<body onload="onLoad()">
    <div class="wrapper">
        <div class="title">Sign Up</div>
        <form action="sign-up.php" method="post">
            <div class="social-login">
                <button type="button" class="social-btn facebook-login">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSJ3aGl0ZSIgZD0iTTEyIDJDNi40NzcgMiAyIDYuNDc3IDIgMTJjMCA0Ljk5MSAzLjY1NyA5LjEyOCA4LjQzOCA5Ljg3OVYxNC44OWgtMi41NFYxMmgyLjU0VjkuNzk3YzAtMi41MDYgMS40OTItMy44OSAzLjc3Ny0zLjg5IDEuMDk0IDAgMi4yMzguMTk1IDIuMjM4LjE5NXYyLjQ2aC0xLjI2Yy0xLjI0MyAwLTEuNjMuNzcxLTEuNjMgMS41NjJ2MS44NzhoMi43NzFsLS40NDMgMi44OWgtMi4zMjh2Ni45ODlDMTguMzQzIDIxLjEyOCAyMiAxNi45OTEgMjIgMTJjMC01LjUyMy00LjQ3Ny0xMC0xMC0xMHoiLz48L3N2Zz4=" alt="Facebook">
                    <span>Continue with Facebook</span>
                </button>
                <button type="button" class="social-btn google-login" onclick="onSignIn()">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjNDI4NUY0IiBkPSJNMjIuNTYgMTIuMjVjMC0uNzgtLjA3LTEuNTMtLjItMi4yNUgxMnYzLjI2aDUuOTJjLS4yNiAxLjM3LTEuMDQgMi41My0yLjIxIDMuMzF2Mi43N2gzLjU3YzIuMDgtMS45MiAzLjI4LTQuNzQgMy4yOC04LjA5eiIvPjxwYXRoIGZpbGw9IiMzNEE4NTMiIGQ9Ik0xMiAyM2MyLjk3IDAgNS40Ni0uOTggNy4yOC0yLjY2bC0zLjU3LTIuNzdjLS45OS42Ni0yLjI2IDEuMDYtMy43MSAxLjA2LTIuODYgMC01LjI5LTEuOTMtNi4xNi00LjUzSDIuMTh2Mi44NkM0IDIwLjIxIDcuODEgMjMgMTIgMjN6Ii8+PHBhdGggZmlsbD0iI0ZCQkMwNSIgZD0iTTUuODQgMTQuMDljLS41Ny0xLjctLjU3LTMuNTUgMC01LjI0VjUuOTlIMi4xOEE5Ljk2IDkuOTYgMCAwIDAgMCAxMmMwIDIuMDQuNjEgMy45MyAxLjY3IDUuNWwzLjczLTIuODd6Ii8+PHBhdGggZmlsbD0iI0VBNDMzNSIgZD0iTTEyIDUuMzdjMS42MiAwIDMuMDYuNTYgNC4yMSAxLjY0bDMuMTUtMy4xNUMxNy40NSAyLjA5IDE0Ljk3IDEgMTIgMSA3LjgxIDEgNCAzLjc5IDIuMTggNS45OWwzLjY2IDIuODRDNi43MSA3LjA3IDkuMTQgNS4zNyAxMiA1LjM3eiIvPjwvc3ZnPg==" alt="Google">
                    <span>Continue with Google</span>
                </button>
            </div>
            <div class="divider">OR</div>
            <div class="field">
                <input type="email" id="email" name="email" required>
                <label for="email">Email</label>
            </div>
            <div class="field">
                <input type="text" id="phone" name="phone" required>
                <label for="phone">Phone Number</label>
            </div>
            <div class="field">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
            </div>
            <div class="field">
                <input type="submit" value="Sign Up">
            </div>
            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
            <div class="privacy-policy">
                By signing up, you agree to our <a href="privacy-policy.php" target="_blank">Privacy Policy</a>.
            </div>
        </form>
    </div>

    <!-- Modal for Verification -->
    <?php if ($showModal): ?>
    <div id="verificationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('verificationModal').style.display='none'">&times;</span>
            <h2>Verify Your Email</h2>
            <form action="sign-up.php" method="post">
                <div class="field">
                    <input type="text" id="verification_token" name="verification_token" required>
                    <label for="verification_token">Verification Code</label>
                </div>
                <div class="field">
                    <input type="submit" value="Verify">
                </div>
            </form>
            <p id="verification_message"><?php echo $verification_message; ?></p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
