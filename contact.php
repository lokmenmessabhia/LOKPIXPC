<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo '<!DOCTYPE html>
          <html lang="en">
          <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>Access Denied - Lokpix</title>
          </head>
          <body>
              <p>You must be logged in to access this page. <a href="login.php">Log in here</a>.</p>
          </body>
          </html>';
    exit; // Stop further execution of the script
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'lokmen13.messabhia@gmail.com'; // Your Gmail address
        $mail->Password = 'dfbk qkai wlax rscb'; // Your Gmail App Password
        $mail->SMTPSecure = 'tls'; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to

        // Recipients
        $mail->setFrom('lokmen13.messabhia@gmail.com', 'Your Name');
        $mail->addAddress('lokmen13.messabhia@gmail.com'); // Add the recipient email address here

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'Contact Form Submission';
        $mail->Body    = "Name: $name<br>Email: $email<br>Message: $message";

        $mail->send();
        $message_sent = 'Message has been sent';
    } catch (Exception $e) {
        $message_sent = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

include 'db_connect.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - Lokpix</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e5e9f0 100%);
            margin: 0;
        
            color: #1a1a1a;
            line-height: 1.6;
            min-height: 100vh;
        }

        .contact-container {
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

        .contact-heading {
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

        .contact-heading::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, transparent);
            border-radius: 3px;
        }

        .contact-form {
            margin-bottom: 35px;
            padding: 25px;
            border-radius: 16px;
            background: rgba(248, 250, 252, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-form:hover {
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

        .input-container {
            flex: 1;
            min-width: 250px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-group input:hover,
        .form-group textarea:hover {
            border-color: #cbd5e1;
            background-color: #ffffff;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            background-color: #ffffff;
            transform: translateY(-1px);
        }

        .submit-button {
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

        .submit-button::before {
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

        .submit-button:hover::before {
            left: 100%;
        }

        .submit-button:hover {
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

        .alert-success {
            background: linear-gradient(135deg, rgba(220, 252, 231, 0.9) 0%, rgba(187, 247, 208, 0.9) 100%);
            color: #166534;
            border: 1px solid rgba(134, 239, 172, 0.5);
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(254, 226, 226, 0.9) 0%, rgba(254, 202, 202, 0.9) 100%);
            color: #991b1b;
            border: 1px solid rgba(252, 165, 165, 0.5);
        }

        @media (max-width: 768px) {
            .contact-container {
                margin: 20px auto;
                padding: 25px;
                border-radius: 20px;
            }

            .contact-form {
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

            .input-container {
                width: 100%;
            }

            .submit-button {
                width: 100%;
                margin-top: 10px;
            }

            .contact-heading {
                font-size: 24px;
                margin-bottom: 25px;
            }
        }
    </style>
</head>
<body>
    <main class="contact-container">
        <h2 class="contact-heading">Contact Us</h2>
        <?php if (isset($message_sent)) : ?>
            <div class="alert <?php echo strpos($message_sent, 'sent') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message_sent; ?>
            </div>
        <?php endif; ?>
        
        <form action="contact.php" method="post" class="contact-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-container">
                    <input type="text" id="name" name="name" required>
                    
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-container">
                    <input type="email" id="email" name="email" required>
                  
                </div>
            </div>
            
            <div class="form-group">
                <label for="message">Message Content</label>
                <div class="input-container">
                    <textarea id="message" name="message" required></textarea>
                    
                </div>
            </div>
            
            <div class="submit-section">
                <button type="submit" class="submit-button">Send Message</button>
            </div>
        </form>
    </main>
    <?php
include 'footer.php';
?>
</body>
</html>