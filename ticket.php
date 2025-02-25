<?php
session_start();
include 'db_connect.php';
// Include the necessary libraries
require_once('fpdf/fpdf.php'); // Ensure you have the FPDF library
require_once('phpqrcode/qrlib.php'); // Use require_once to avoid multiple inclusions

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


// Ensure order_id is provided in the query string
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo "Error: Order ID is missing.";
    exit();
}

$order_id = (int)$_GET['order_id']; // Use $order_id consistently

// Fetch the order details
// Fetch the order details
$stmt = $pdo->prepare("SELECT orders.id, users.email AS user_email, orders.phone, orders.address, orders.wilaya_id, orders.delivery_type, orders.total_price, orders.order_date, orders.status, orders.tracking_number, orders.qrtoken
                       FROM orders 
                       JOIN users ON orders.user_id = users.id 
                       WHERE orders.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Debugging: Check the value of qrtoken
if ($order) {
   // echo "QR Token: " . htmlspecialchars($order['qrtoken']); // Debugging line
} else {
    echo "Error: Order not found.";
    exit();
}

// Fetch the order items
$stmt_items = $pdo->prepare("SELECT products.name, order_details.quantity, products.price 
                             FROM order_details 
                             JOIN products ON order_details.product_id = products.id 
                             WHERE order_details.order_id = ?");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Fetch Wilaya name (assuming you have a wilayas table)
$stmt_wilaya = $pdo->prepare("SELECT name FROM wilayas WHERE id = ?");
$stmt_wilaya->execute([$order['wilaya_id']]);
$wilaya = $stmt_wilaya->fetch(PDO::FETCH_ASSOC);
$order['wilaya_name'] = $wilaya ? $wilaya['name'] : 'Unknown'; // If no wilaya found, use 'Unknown'

// Handle ticket validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_ticket'])) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'validated' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // Set a success message in session
        $_SESSION['validation_success'] = "Order #$order_id has been successfully validated!";
        
        // Return JSON response instead of redirecting immediately
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true, 'message' => "Order #$order_id has been successfully validated!"]);
            exit();
        } else {
            // Fallback for non-AJAX requests
            header("Location: orders.php");
            exit();
        }
    } catch (PDOException $e) {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => false, 'message' => "Error: Unable to validate ticket. " . $e->getMessage()]);
            exit();
        } else {
            echo "Error: Unable to validate ticket. " . $e->getMessage();
            exit();
        }
    }
}

// Handle tracking information submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tracking'])) {
    $tracking_number = $_POST['tracking_number'];
    $status = $_POST['status'];
    $location = $_POST['location'];
    $additional_info = $_POST['additional_info'];

    try {
        // Update tracking number in the orders table
        $stmt_order = $pdo->prepare("UPDATE orders SET tracking_number = ? WHERE id = ?");
$stmt_order->execute([$tracking_number, $order_id]);


        // Check if tracking information already exists for this order
        $stmt_check = $pdo->prepare("SELECT * FROM tracking_info WHERE order_id = ?");
        $stmt_check->execute([$order_id]);
        $existing_tracking = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_tracking) {
            // Update the existing tracking information
            $stmt = $pdo->prepare("UPDATE tracking_info SET tracking_number = ?, status = ?, last_updated = NOW(), location = ?, additional_info = ? WHERE order_id = ?");
            $stmt->execute([$tracking_number, $status, $location, $additional_info, $order_id]);
        } else {
            // Insert new tracking information if it doesn't exist
            $stmt = $pdo->prepare("INSERT INTO tracking_info (order_id, tracking_number, status, last_updated, location, additional_info) 
                                    VALUES (?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([$order_id, $tracking_number, $status, $location, $additional_info]);
        }

        // Refresh the page to show updated tracking info
        header("Location: order_details.php?order_id=" . $order_id);
        exit();
    } catch (PDOException $e) {
        echo "Error: Unable to update tracking information. " . $e->getMessage();
        exit();
    }
}


// Fetch tracking information for the order
$stmt_tracking = $pdo->prepare("SELECT * FROM tracking_info WHERE order_id = ?");
$stmt_tracking->execute([$order_id]);
$tracking_info = $stmt_tracking->fetchAll(PDO::FETCH_ASSOC);


// Handle PDF generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    // Define the PDF class
    class PDF extends FPDF
    {
        private $order;  // To store all the order details
        private $order_items; // To store order items
        private $qrtoken; // To store the qrtoken

        public function __construct($order, $order_items)
        {
            parent::__construct();
            $this->order = $order; // Store the order details
            $this->order_items = $order_items; // Store the order items
            $this->qrtoken = $order['qrtoken']; // Store the qrtoken
            // Set default font
            $this->SetFont('Arial', '', 10);
        }

        // Page header
        function Header()
        {
            // Add a gradient-style header
            $this->SetFillColor(67, 97, 238); // Primary color #4361ee
            $this->Rect(0, 0, 210, 25, 'F');
            
            // Add logo if exists (adjust path and size as needed)
            if (file_exists('images/logo.png')) {
                $this->Image('images/logo.png', 10, 5, 15, 15);
            }
            
            // Add header text
            $this->SetFont('Arial', 'B', 18);
            $this->SetTextColor(255, 255, 255);
            $this->SetX(30); // Adjust position after logo
            $this->Cell(0, 10, "EcoTech - Order Invoice", 0, 1, 'C');
            
            // Reset text color for the rest of the document
            $this->SetTextColor(43, 45, 66); // --text #2b2d42
            $this->Ln(15); // Add space after header
        }

        // Page footer
        function Footer()
        {
            $this->SetY(-60); // Position higher up for QR code
            
            // Add QR Code with some styling
            if (!empty($this->qrtoken)) {
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(0, 10, 'Scan to view order details:', 0, 1, 'C');
                
                $url = 'http://localhost/lokpixpc/order_details.php?qrtoken=' . $this->qrtoken;
                $qrFilePath = $this->generateQrCode($url);
                
                if (file_exists($qrFilePath)) {
                    $this->Image($qrFilePath, 90, $this->GetY(), 30, 30);
                    unlink($qrFilePath); // Delete the QR code file after use
                } else {
                    $this->Cell(0, 10, 'Failed to generate QR code', 0, 1, 'C');
                }
            }
            
            // Add footer with styling
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(108, 117, 125); // --text-light #6c757d
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Generated by EcoTech ' . date('Y-m-d'), 0, 0, 'C');
        }

        // Function to generate a QR code and return the image file path
        function generateQrCode($url)
        {
            // Use a more reliable temp directory path
            $tempDir = dirname(__FILE__) . '/temp';
            
            // Create the temp directory if it doesn't exist
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                    // If we can't create the directory, use the system temp directory
                    $tempDir = sys_get_temp_dir();
                }
            }
            
            $filePath = $tempDir . DIRECTORY_SEPARATOR . 'qr_' . uniqid() . '.png';
            
            // Error handling for QR code generation
            try {
                QRcode::png($url, $filePath, 'L', 4, 4);
                return $filePath;
            } catch (Exception $e) {
                error_log('QR Code Generation Error: ' . $e->getMessage());
                return false;
            }
        }

        // Function to output buyer info with improved styling
        function BuyerInfo()
        {
            // Add a styled section header with gradient effect (no border)
            $this->SetFillColor(72, 149, 239); // --primary-light #4895ef
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(0, 8, "   Buyer Information", 0, 1, 'L', true);
            $this->SetTextColor(43, 45, 66); // --text #2b2d42
            $this->Ln(5);
            
            // Create a two-column layout for customer information
            $this->SetFont('Arial', '', 10);
            
            // Left column
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(40, 8, "Order ID:", 0);
            $this->SetFont('Arial', '', 10);
            $this->Cell(50, 8, $this->order['id'], 0);
            
            // Right column
            $this->SetX(120);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(40, 8, "Order Date:", 0);
            $this->SetFont('Arial', '', 10);
            $this->Cell(50, 8, $this->order['order_date'], 0, 1);
            
            // Continue with other info in the same format
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(40, 8, "Email:", 0);
            $this->SetFont('Arial', '', 10);
            $this->Cell(50, 8, $this->order['user_email'], 0);
            
            $this->SetX(120);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(40, 8, "Phone:", 0);
            $this->SetFont('Arial', '', 10);
            $this->Cell(50, 8, $this->order['phone'], 0, 1);
            
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(40, 8, "Address:", 0);
            $this->SetFont('Arial', '', 10);
            $this->Cell(140, 8, $this->order['address'], 0, 1);
            
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(40, 8, "Delivery Type:", 0);
            $this->SetFont('Arial', '', 10);
            $this->Cell(50, 8, $this->order['delivery_type'], 0);
            
             
         
            
            // Total with highlight
            $this->Ln(5);
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(40, 10, "Total Price:", 0);
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(63, 55, 201); // --primary-dark #3f37c9
            $this->Cell(50, 10, "$" . $this->order['total_price'], 0, 1);
            $this->SetTextColor(43, 45, 66); // Reset text color
            
            $this->Ln(10); // Add space
        }

        // Draw a table cell with custom styling (gradient-like) with no visible border
        function GradientCell($w, $h, $txt, $align='L', $fill=false, $background='white')
        {
            // Save the current fill color
            $currentFill = $this->FillColor;
            
            if ($background != 'white' && $fill) {
                $this->SetFillColor(...$background);
            }
            
            // Draw cell without visible border
            $this->Cell($w, $h, $txt, 0, 0, $align, $fill);
            
            // Restore the original fill color
            $this->FillColor = $currentFill;
        }

        // Function to output order items table with improved styling
        function OrderTable()
        {
            // Add a styled section header (no border)
            $this->SetFillColor(67, 97, 238); // --primary #4361ee
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(0, 8, "   Order Details", 0, 1, 'L', true);
            $this->SetTextColor(43, 45, 66); // Reset text color
            $this->Ln(5);
            
            // Create custom table with gradient look and no borders
            // Table headers with styling
            $headerBackground = [72, 149, 239]; // --primary-light #4895ef
            $this->SetFillColor(...$headerBackground);
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(255, 255, 255);
            
            // Draw table header with no borders
            $this->GradientCell(90, 10, ' Product', 'L', true, $headerBackground);
            $this->GradientCell(25, 10, 'Quantity', 'C', true, $headerBackground);
            $this->GradientCell(35, 10, 'Unit Price', 'R', true, $headerBackground);
            $this->GradientCell(40, 10, 'Total', 'R', true, $headerBackground);
            $this->Ln();
            
            // Add subtle shadow underneath header (gradient effect)
            $this->SetDrawColor(230, 230, 230);
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(43, 45, 66); // --text #2b2d42
            
            $fill = false;
            $totalAmount = 0;
            
            foreach ($this->order_items as $item) {
                // Calculate the item total
                $itemTotal = $item['quantity'] * $item['price'];
                $totalAmount += $itemTotal;
                
                // Set alternate row background
                if ($fill) {
                    $rowBackground = [248, 249, 250]; // --bg #f8f9fa
                } else {
                    $rowBackground = [255, 255, 255]; // --bg-card #ffffff
                }
                
                // Draw cells without visible borders
                $this->GradientCell(90, 8, ' ' . $item['name'], 'L', $fill, $rowBackground);
                $this->GradientCell(25, 8, $item['quantity'], 'C', $fill, $rowBackground);
                $this->GradientCell(35, 8, "$" . number_format($item['price'], 2), 'R', $fill, $rowBackground);
                $this->GradientCell(40, 8, "$" . number_format($itemTotal, 2), 'R', $fill, $rowBackground);
                $this->Ln();
                
                // Add subtle separator line
                $this->SetDrawColor(240, 240, 240);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                
                $fill = !$fill;
            }
            
            // Add a total row with styling (no visible border)
            $totalBackground = [67, 97, 238]; // --primary #4361ee
            $this->SetFillColor(...$totalBackground);
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(255, 255, 255);
            $this->GradientCell(150, 10, 'TOTAL', 'R', true, $totalBackground);
            $this->GradientCell(40, 10, "$" . number_format($totalAmount, 2), 'R', true, $totalBackground);
            $this->Ln();
            
            // Add note about payment
            $this->Ln(10);
            $this->SetFont('Arial', 'I', 9);
            $this->SetTextColor(108, 117, 125); // --text-light #6c757d
            $this->Cell(0, 5, 'Thank you for your order! For any questions please contact our customer service.', 0, 1, 'C');
        }
    }

    // Create PDF instance with necessary data
    try {
        $pdf = new PDF($order, $order_items);
        $pdf->AddPage();  
        // Add buyer info
        $pdf->BuyerInfo();

        // Add order table
        $pdf->OrderTable();

        // Output the PDF
        $pdf->Output("D", "Ticket_Order_" . $order['id'] . ".pdf");
        exit();
    } catch (Exception $e) {
        echo "Error generating PDF: " . $e->getMessage();
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Ticket</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        /* Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 4.5rem auto 0;
            padding: 20px;
        }

        /* Ticket Container Styles */
        .ticket-container {
            background-color: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin: 20px 0;
        }

        /* Headings */
        h1, h2, h3 {
            color: var(--text);
            margin-top: 0;
        }

        h2 {
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.5rem;
        }

        h3 {
            font-size: 1.2rem;
            margin-top: 25px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        /* Paragraphs */
        p {
            line-height: 1.6;
            margin: 8px 0;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 0.9rem;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        th, td {
            border: 1px solid var(--border);
            padding: 12px 16px;
            text-align: left;
        }

        th {
            background-color: var(--primary-light);
            color: white;
            font-weight: 500;
        }

        tr:hover {
            background-color: var(--bg);
        }

        /* Button Styles */
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        button, .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover, .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .validate-btn {
            background-color: var(--success);
        }

        .validate-btn:hover {
            background-color: var(--success-dark);
        }

        .pdf-btn {
            background-color: var(--warning);
        }

        .pdf-btn:hover {
            background-color: #e36c0a;
        }

        .update-btn {
            background-color: var(--primary-light);
            margin-top: 15px;
        }

        .update-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Form Styles */
        label {
            display: block;
            margin-top: 15px;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text);
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px 14px;
            margin-top: 5px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-sizing: border-box;
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        /* Textarea Styles */
        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Tracking Section */
        .tracking-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 25px;
        }

        .update-tracking,
        .tracking-info {
            flex: 1;
            min-width: 300px;
        }

        .tracking-info {
            background-color: var(--bg);
            border-radius: var(--radius-sm);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }

        /* Validation message styles */
        .validation-message {
            background: linear-gradient(to right, #dcfce7, #bbf7d0);
            color: #166534;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .validation-message .close-btn {
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Button styles for disabled/validated state */
        .btn-validated {
            background-color: var(--text-light) !important;
            cursor: not-allowed !important;
            opacity: 0.7;
            pointer-events: none;
            box-shadow: none;
        }

        .btn-validated:hover {
            background-color: var(--text-light) !important;
            transform: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .tracking-container {
                flex-direction: column;
            }
            
            .top-nav {
                flex-wrap: wrap;
                padding: 0.75rem 1rem;
            }
            
            .nav-menu {
                order: 3;
                width: 100%;
                margin-top: 10px;
                overflow-x: auto;
            }
            
            .container {
                padding: 15px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
        <div class="top-nav">
            <div class="nav-brand">
                <h1>EcoTech Admin</h1>
            </div>
            <div class="nav-menu">
                <a href="ticket.php" class="active">Order Ticket</a>
                <!-- Add other menu items as needed -->
            </div>
            <div class="nav-end">
                <a href="orders.php" class="back-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span>Back to manage orders</span>
                </a>
            </div>
        </div>

    <div class="container">
        <div class="ticket-container">
            <?php if (isset($_SESSION['validation_success'])): ?>
                <div class="validation-message">
                    <?php echo $_SESSION['validation_success']; ?>
                    <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
                </div>
                <?php unset($_SESSION['validation_success']); ?>
            <?php endif; ?>
            
            <h2>Order ID: <?php echo htmlspecialchars($order['id']); ?></h2>
            <p><strong>User Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p><strong>Wilaya Name:</strong> <?php echo htmlspecialchars($order['wilaya_name']); ?></p>
            <p><strong>Delivery Type:</strong> <?php echo htmlspecialchars($order['delivery_type']); ?></p>
            <p><strong>Total Price:</strong> $<?php echo htmlspecialchars($order['total_price']); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>

            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price (per unit)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="actions">
                <form method="POST" id="validateForm" style="margin-right: 10px;">
                    <?php if ($order['status'] === 'validated'): ?>
                        <button type="button" class="btn validate-btn btn-validated" disabled>Validated</button>
                    <?php else: ?>
                        <button type="button" onclick="confirmValidation()" class="btn validate-btn">Validate Ticket</button>
                        <input type="hidden" name="validate_ticket" value="1">
                    <?php endif; ?>
                </form>
                <form method="POST">
                    <button type="submit" name="generate_pdf" class="btn pdf-btn">Generate PDF</button>
                </form>
            </div>

            <div class="tracking-container">
                <div class="update-tracking">
                    <h3>Update Tracking Information</h3>
                    <form method="POST">
                        <label for="tracking_number">Tracking Number:</label>
                        <input type="text" id="tracking_number" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number']); ?>">

                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="" disabled selected>Select Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Shipped">Shipped</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Returned">Returned</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>

                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location">

                        <label for="additional_info">Additional Info:</label>
                        <textarea id="additional_info" name="additional_info"></textarea>

                        <button type="submit" name="update_tracking" class="btn update-btn">Update Tracking</button>
                    </form>
                </div>

                <div class="tracking-info">
                    <h3>Tracking Information</h3>
                    <?php if ($tracking_info): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Location</th>
                                    <th>Additional Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tracking_info as $tracking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tracking['tracking_number']); ?></td>
                                        <td><?php echo htmlspecialchars($tracking['status']); ?></td>
                                        <td><?php echo htmlspecialchars($tracking['last_updated']); ?></td>
                                        <td><?php echo htmlspecialchars($tracking['location']); ?></td>
                                        <td><?php echo htmlspecialchars($tracking['additional_info']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No tracking information available for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function confirmValidation() {
        if (confirm('Are you sure you want to validate this ticket?')) {
            document.getElementById('validateForm').submit();
        }
    }
    </script>
</body>
</html>