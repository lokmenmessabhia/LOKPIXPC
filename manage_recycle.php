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

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--surface-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        th, td {
            padding: 1.25rem;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: var(--text-primary);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:not(:last-child) td {
            border-bottom: 1px solid #e2e8f0;
        }

        tr td {
            transition: all 0.3s ease;
        }

        tr:hover td {
            background-color: #f8fafc;
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
            
            table {
                display: block;
                overflow-x: auto;
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
<?php
include 'footer.php';
?>