<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

session_start();


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
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
        $mail->Subject = $subject;
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background-color: #007bff;
                        color: white;
                        padding: 20px;
                        text-align: center;
                        border-radius: 5px 5px 0 0;
                    }
                    .content {
                        background-color: #ffffff;
                        padding: 20px;
                        border: 1px solid #dddddd;
                        border-radius: 0 0 5px 5px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        padding: 20px;
                        color: #666666;
                        font-size: 12px;
                    }
                    .details {
                        background-color: #f8f9fa;
                        padding: 15px;
                        border-radius: 5px;
                        margin: 15px 0;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>New Contact Message</h1>
                    </div>
                    
                    <div class='content'>
                        <div class='details'>
                            <p><strong>From:</strong> " . htmlspecialchars($name) . "</p>
                            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                            <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
                            <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                            <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                    </div>

                    <div class='footer'>
                        <p>This email was sent from LokPixPC Contact Form</p>
                        <p>  " . date('Y') . " LokPixPC. All rights reserved.</p>
                        <p>Route Nationale N-16, Souk Ahras, Algeria</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Set plain text version
        $mail->AltBody = "
From: $name
Email: $email
Phone: $phone
Subject: $subject

Message:
$message

Sent from LokPixPC Contact Form
";
        
        $mail->send();
        $message_sent = 'Message has been sent';
    } catch (Exception $e) {
        $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

include 'db_connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - EcoTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .contact-container {
            display: flex;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            gap: 50px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .contact-info {
            flex: 1;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .contact-form {
            flex: 1.5;
            padding: 30px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 15px;
        }

        .info-item i {
            font-size: 24px;
            color: #007bff;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e7f1ff;
            border-radius: 50%;
        }

        .content h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }

        .content p {
            margin: 0;
            color: #666;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #0056b3;
            transform: translateY(-3px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #0056b3;
        }

        @media (max-width: 768px) {
            .contact-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <?php if (isset($message_sent) && $message_sent): ?>
        <div class="alert alert-success">
            Message sent successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="contact-container">
        <div class="contact-info">
            <h2>Contact Information</h2>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="content">
                    <h3>Address</h3>
                    <p>Route Nationale N-16, Souk Ahras, Algeria</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <div class="content">
                    <h3>Phone</h3>
                    <p>+213 794159854</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div class="content">
                    <h3>Email</h3>
                    <p>lokmen13.messabhia@gmail.com</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div class="content">
                    <h3>Working Hours</h3>
                    <p>Mon - Sat: 9:00 AM - 8:00 PM</p>
                </div>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>

        <div class="contact-form">
            <h2>Send Us a Message</h2>
            <form id="contactForm" method="POST" action="contact.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>