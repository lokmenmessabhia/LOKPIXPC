<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

// Check if user is logged in and is an admin
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$isAdmin = false; // Default to false
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $admin = $stmt->fetch();

        if ($admin) {
            $isAdmin = true; // Set true if email exists in the admins table
            $_SESSION['admin_id'] = $admin['id']; // Store admin ID in session
            $_SESSION['admin_role'] = $admin['role']; // Store admin role in session
        }
    } catch (PDOException $e) {
        echo "Error: Unable to verify admin status. " . $e->getMessage();
    }
}

// Check if the user is logged in and is an admin
if (!$isAdmin) {
    header('Location: login.php');
    exit;
}

// Handle validation and deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['validate_request'])) {
        $request_id = (int)$_POST['request_id'];
        
        try {
            // Update the status
            $stmt = $pdo->prepare("UPDATE recycle_requests SET status = 'validated' WHERE id = ?");
            $stmt->execute([$request_id]);

            // Fetch request details for the email
            $stmt = $pdo->prepare("
                SELECT rr.*, u.email as user_email,
                       c.name as category_name, 
                       s.name as subcategory_name
                FROM recycle_requests rr
                JOIN users u ON rr.user_id = u.id
                JOIN categories c ON rr.category_id = c.id
                JOIN subcategories s ON rr.subcategory_id = s.id
                WHERE rr.id = ?
            ");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            // Send validation email
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'lokmen13.messabhia@gmail.com';
                $mail->Password = 'dfbk qkai wlax rscb';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('lokmen13.messabhia@gmail.com', 'Lokpix');
                $mail->addAddress($request['user_email']);
                $mail->isHTML(true);
                $mail->Subject = "Recycling Request Validated";

                // Create HTML email body
                $emailBody = "
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
                            background-color: #28a745;
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
                            <h1>🎉 Recycling Request Validated!</h1>
                        </div>
                        
                        <div class='content'>
                            <h2>Good news! Your recycling request has been validated.</h2>
                            <p>We're pleased to inform you that your recycling request has been reviewed and approved.</p>
                            
                            <div class='details'>
                                <p><strong>Category:</strong> " . htmlspecialchars($request['category_name']) . "</p>
                                <p><strong>Subcategory:</strong> " . htmlspecialchars($request['subcategory_name']) . "</p>
                                <p><strong>Condition:</strong> " . htmlspecialchars($request['component_condition']) . "</p>
                                <p><strong>Delivery Option:</strong> " . htmlspecialchars($request['pickup_option']) . "</p>
                            </div>

                            <p>Our team will be in touch shortly with next steps for " . 
                            ($request['pickup_option'] === 'pickup' ? "collecting your item" : "dropping off your item") . 
                            ".</p>

                            <p>If you have any questions, please don't hesitate to contact our support team.</p>
                        </div>

                        <div class='footer'>
                            <p>This email was sent by Lokpix PC Recycling Service</p>
                            <p>© " . date('Y') . " Lokpix. All rights reserved.</p>
                            <p>23 Rue Zaafrania, Annaba 23000, Algeria</p>
                        </div>
                    </div>
                </body>
                </html>
                ";

                $mail->Body = $emailBody;
                $mail->AltBody = strip_tags(str_replace(
                    ['<br>', '</div>', '</p>'], 
                    ["\n", "\n", "\n\n"],
                    $emailBody
                ));

                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send validation email. Mailer Error: {$mail->ErrorInfo}");
            }

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            exit();
        }
    } elseif (isset($_POST['delete_request'])) {
        $request_id = (int)$_POST['request_id'];

        try {
            // Delete the recycle request
            $stmt = $pdo->prepare("DELETE FROM recycle_requests WHERE id = ?");
            $stmt->execute([$request_id]);

        } catch (PDOException $e) {
            echo "Error: Unable to delete the request. " . $e->getMessage();
            exit();
        }
    }
}

// Fetch recycle requests from database
try {
    $stmt = $pdo->query("
        SELECT rr.id, rr.email, rr.phone, rr.category_id, rr.subcategory_id, 
               rr.component_condition, rr.photo, rr.pickup_option, rr.submitted_at,
               rr.status, users.email AS user_email
        FROM recycle_requests rr
        JOIN users ON rr.user_id = users.id
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate validated and pending requests
    $validated_requests = array_filter($requests, function($request) {
        return $request['status'] === 'validated';
    });
    
    $pending_requests = array_filter($requests, function($request) {
        return $request['status'] !== 'validated';
    });
    
} catch (PDOException $e) {
    echo "Error: Unable to fetch recycle requests. " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Recycling Requests</title>
    <style>
        /* General styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #1a1a1a;
        }

        /* Header styles */
        header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        /* Content area styles */
        .content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Section headings */
        h2, h3 {
            color: #1e3c72;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        h2 {
            font-size: 1.8rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }

        h3 {
            font-size: 1.4rem;
            margin-top: 2rem;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            margin-bottom: 2rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eef2f7;
        }

        th {
            background: #f8fafc;
            color: #1e3c72;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8fafc;
            transition: background-color 0.2s ease;
        }

        /* Button styles */
        button {
            padding: 0.6rem 1.2rem;
            margin: 0 4px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        button[name="validate_request"] {
            background-color: #10B981;
            color: white;
        }

        button[name="delete_request"] {
            background-color: #EF4444;
            color: white;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        button:active {
            transform: translateY(0);
        }

        /* Image styles */
        img {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Back button link */
        header a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }

        header a:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }

        header a img {
            width: 24px;
            height: 24px;
        }

        /* Header container */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Status badge */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-validated {
            background-color: #D1FAE5;
            color: #065F46;
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .content {
                padding: 1rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                padding: 0.8rem;
            }

            button {
                padding: 0.5rem 1rem;
            }
        }

        @media (max-width: 768px) {
            header {
                padding: 1rem;
            }

            header h1 {
                font-size: 1.4rem;
            }

            .header-container {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Manage Recycling Requests</h1>
            <a href="dashboard.php">
                <img src="back.png" alt="Back">
                <span>Back to Dashboard</span>
            </a>
        </div>
    </header>

    <div class="content">
        <h2>Recycling Requests</h2>
        
        <h3>Pending Requests</h3>
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>User Email</th>
                    <th>Contact Email</th>
                    <th>Phone</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Condition</th>
                    <th>Photo</th>
                    <th>Pickup Option</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_requests as $request) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['id']); ?></td>
                        <td><?php echo htmlspecialchars($request['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($request['email']); ?></td>
                        <td><?php echo htmlspecialchars($request['phone']); ?></td>
                        <td><?php echo htmlspecialchars($request['category_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['subcategory_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['component_condition']); ?></td>
                        <td><img src="<?php echo htmlspecialchars($request['photo']); ?>" 
                                 alt="Item Photo" 
                                 style="max-width: 100px; cursor: pointer;" 
                                 onclick="openModal('<?php echo htmlspecialchars($request['photo']); ?>')"></td>
                        <td><?php echo htmlspecialchars($request['pickup_option']); ?></td>
                        <td><?php echo htmlspecialchars($request['submitted_at']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $request['status'] === 'validated' ? 'status-validated' : 'status-pending'; ?>">
                                <?php echo htmlspecialchars($request['status'] ?? 'pending'); ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <button type="submit" name="validate_request">Validate</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <button type="submit" name="delete_request" onclick="return confirm('Are you sure you want to delete this request?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Validated Requests</h3>
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>User Email</th>
                    <th>Contact Email</th>
                    <th>Phone</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Condition</th>
                    <th>Photo</th>
                    <th>Pickup Option</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($validated_requests as $request) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['id']); ?></td>
                        <td><?php echo htmlspecialchars($request['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($request['email']); ?></td>
                        <td><?php echo htmlspecialchars($request['phone']); ?></td>
                        <td><?php echo htmlspecialchars($request['category_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['subcategory_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['component_condition']); ?></td>
                        <td><img src="<?php echo htmlspecialchars($request['photo']); ?>" 
                                 alt="Item Photo" 
                                 style="max-width: 100px; cursor: pointer;" 
                                 onclick="openModal('<?php echo htmlspecialchars($request['photo']); ?>')"></td>
                        <td><?php echo htmlspecialchars($request['pickup_option']); ?></td>
                        <td><?php echo htmlspecialchars($request['submitted_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add this modal div before closing body tag -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- Add this script before closing body tag -->
    <script>
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');

        function openModal(imgSrc) {
            modal.style.display = "flex";
            modalImg.src = imgSrc;
        }

        function closeModal() {
            modal.style.display = "none";
        }

        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });
    </script>
</body>
</html>
