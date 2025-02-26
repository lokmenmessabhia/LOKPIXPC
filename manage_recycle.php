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
    <title>Manage Recycling Requests</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --success: #4cc9f0;
            --success-dark: #4895ef;
            --danger: #f72585;
            --warning: #f8961e;
            --text: #2b2d42;
            --text-light: #6c757d;
            --bg: #f8f9fa;
            --bg-card: #ffffff;
            --border: #e9ecef;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        .top-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 1.75rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: var(--shadow-sm);
        }

        /* Nav brand and menu */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        .nav-brand h1 {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .nav-menu a {
            color: var(--text);
            text-decoration: none;
            padding: 0.6rem 0.9rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
            white-space: nowrap;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: var(--bg);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-end {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            background-color: var(--bg);
        }

        .back-button:hover {
            background-color: var(--primary-light);
            color: white;
        }

        /* Main Content - adjust to account for fixed header */
        .main-content {
            margin-top: 4.5rem;
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }

        .page-title, .section-title {
            width: 100%;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color: var(--text);
            border-bottom: 2px solid var(--border);
            padding-bottom: 0.75rem;
        }

        .section-title {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        /* Table container */
        .table-container {
            width: 100%;
            margin-bottom: 2rem;
            margin-left: auto;
            margin-right: auto;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--bg-card);
            overflow: hidden;
            margin: 0 auto;
            table-layout: fixed;
        }

        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Specific column widths */
        th:nth-child(1), td:nth-child(1) { width: 5%; } /* ID */
        th:nth-child(2), td:nth-child(2) { width: 10%; } /* User Email */
        th:nth-child(3), td:nth-child(3) { width: 10%; } /* Contact Email */
        th:nth-child(4), td:nth-child(4) { width: 7%; } /* Phone */
        th:nth-child(5), td:nth-child(5) { width: 7%; } /* Category */
        th:nth-child(6), td:nth-child(6) { width: 8%; } /* Subcategory */
        th:nth-child(7), td:nth-child(7) { width: 7%; } /* Condition */
        th:nth-child(8), td:nth-child(8) { width: 10%; } /* Photo */
        th:nth-child(9), td:nth-child(9) { width: 7%; } /* Pickup Option */
        th:nth-child(10), td:nth-child(10) { width: 9%; } /* Submitted At */
        th:nth-child(11), td:nth-child(11) { width: 7%; } /* Status */
        th:nth-child(12), td:nth-child(12) { width: 13%; } /* Actions */

        th {
            background-color: var(--primary-light);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(242, 242, 242, 0.6);
        }

        /* Button Styles */
        .btn-validate, .btn-delete, .btn-view, button[type="submit"] {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            border: none;
            transition: var(--transition);
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn-validate, button[name="validate_request"], .btn-view, button[type="button"] {
            background-color: var(--primary);
            color: white;
        }

        .btn-validate:hover, button[name="validate_request"]:hover, .btn-view:hover, button[type="button"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-delete, button[name="delete_request"] {
            background-color: var(--danger);
            color: white;
        }

        .btn-delete:hover, button[name="delete_request"]:hover {
            filter: brightness(0.9);
            transform: translateY(-2px);
        }

        /* Action Buttons Container */
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-validated {
            background-color: var(--success);
            color: white;
        }

        .status-pending {
            background-color: var(--warning);
            color: white;
        }

        /* Image styles */
        img {
            max-width: 100px;
            height: auto;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            cursor: pointer;
        }

        img:hover {
            transform: scale(1.05);
        }

        /* Modal Styles */
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
            padding: 20px;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.2);
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .close:hover {
            color: #bbb;
        }

        /* Footer */
        footer {
            background-color: var(--bg-card);
            color: var(--text-light);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid var(--border);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .table-container {
                width: 100%;
            }
            
            th, td {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .nav-menu, .nav-end {
                width: 100%;
                justify-content: center;
            }
            
            /* Allow for scrolling on mobile only */
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px; /* Force minimum width on mobile */
            }

            .actions {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-brand">
            <h1>Lokpix</h1>
        </div>
        <div class="nav-menu">
            <a href="manage_recycle.php" class="active">Recycling Requests</a>
        </div>
        <div class="nav-end">
            <a href="dashboard.php" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <main class="main-content">
        <h2 class="page-title">Manage Recycling Requests</h2>
        
        <h3 class="section-title">Pending Requests</h3>
        <div class="table-container">
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
                                     onclick="openModal('<?php echo htmlspecialchars($request['photo']); ?>')"></td>
                            <td><?php echo htmlspecialchars($request['pickup_option']); ?></td>
                            <td><?php echo htmlspecialchars($request['submitted_at']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $request['status'] === 'validated' ? 'status-validated' : 'status-pending'; ?>">
                                    <?php echo htmlspecialchars($request['status'] ?? 'pending'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                        <button type="submit" name="validate_request" class="btn-validate">Validate</button>
                                    </form>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                        <button type="submit" name="delete_request" class="btn-delete" onclick="return confirm('Are you sure you want to delete this request?');">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="section-title">Validated Requests</h3>
        <div class="table-container">
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
                                     onclick="openModal('<?php echo htmlspecialchars($request['photo']); ?>')"></td>
                            <td><?php echo htmlspecialchars($request['pickup_option']); ?></td>
                            <td><?php echo htmlspecialchars($request['submitted_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lokpix. All rights reserved.</p>
    </footer>

    <!-- Modal for image preview -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

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