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
        header("Location: order_details.php?order_id=" . $order_id); // Redirect to refresh page
        exit();
    } catch (PDOException $e) {
        echo "Error: Unable to validate ticket. " . $e->getMessage();
        exit();
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
        }

        // Page header
        function Header()
        {
            $this->SetFont('Arial', 'B', 16);
            $this->SetFillColor(0, 123, 255); // Bootstrap primary color
            $this->Cell(0, 10, "Order Invoice", 0, 1, 'C', true);
            $this->Ln(10); // Add a line break
        }

        // Page footer
        function Footer()
        {
            $this->SetY(-35); // Adjust Y position to make space for the text below the QR code
            $this->SetFont('Arial', 'I', 10);
            
            // Generate QR code URL
            if (!empty($this->qrtoken)) {
                $url = 'http://localhost/lokpixpc/order_details.php?qrtoken=' . $this->qrtoken;
                $qrFilePath = $this->generateQrCode($url);
                $this->Image($qrFilePath, 90, 250, 30, 30); // Adjust position and size as needed
                unlink($qrFilePath); // Delete the QR code file after use
            } else {
                $this->Cell(0, 10, 'QR Token Not Available', 0, 0, 'C');
            }

            $this->SetY(-15);
            $this->Cell(0, 10, 'Generated by Lokpix', 0, 0, 'C');
        }

        // Function to generate a QR code and return the image file path
        function generateQrCode($url)
        {
            $tempDir = 'E:/xampp/htdocs/lokpixpc/temp';  // Adjust path if necessary
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $filePath = $tempDir . DIRECTORY_SEPARATOR . 'qr_' . uniqid() . '.png';
            QRcode::png($url, $filePath, 'L', 4, 4);
            return $filePath;
        }

        // Function to output buyer info
        function BuyerInfo()
        {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, "Buyer Information", 0, 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(80, 10, "Order ID: " . strval($this->order['id']), 0, 1);
            $this->Cell(80, 10, "Email: " . htmlspecialchars($this->order['user_email']), 0,  1);
            $this->Cell(80, 10, "Phone: " . htmlspecialchars($this->order['phone']), 0, 1);
            $this->Cell(80, 10, "Address: " . htmlspecialchars($this->order['address']), 0, 1);
            $this->Cell(80, 10, "Delivery Type: " . htmlspecialchars($this->order['delivery_type']), 0, 1);
            $this->Cell(80, 10, "Total Price: $" . strval($this->order['total_price']), 0, 1);
            $this->Cell(80, 10, "Order Date: " . htmlspecialchars($this->order['order_date']), 0, 1);
            $this->Cell(80, 10, "Status: " . htmlspecialchars($this->order['status']), 0, 1);
            $this->Ln(10); // Add a line break
        }

        // Function to output order items table
        function OrderTable()
        {
            $this->SetFont('Arial', 'B', 12); // Corrected font name
            $this->SetFillColor(0, 123, 255); // Header background color
            $this->Cell(70, 10, 'Product', 1, 0, 'C', true);
            $this->Cell(30, 10, 'Qty', 1, 0, 'C', true);
            $this->Cell(30, 10, 'Unit Price', 1, 0, 'C', true);
            $this->Cell(30, 10, 'Price', 1, 1, 'C', true);

            $this->SetFont('Arial', '', 12); // Corrected font name
            $fill = false; // For alternating row colors
            foreach ($this->order_items as $item) {
                $this->Cell(70, 10, htmlspecialchars($item['name']), 1, 0, 'L', $fill);
                $this->Cell(30, 10, htmlspecialchars($item['quantity']), 1, 0, 'C', $fill);
                $this->Cell(30, 10, "$" . htmlspecialchars($item['price']), 1, 0, 'C', $fill);
                $this->Cell(30, 10, "$" . ($item['quantity'] * $item['price']), 1, 1, 'C', $fill);
                $fill = !$fill; // Toggle fill for alternating row colors
            }
        }
    }

    // Create PDF instance with necessary data
    $pdf = new PDF($order, $order_items);
    $pdf->AddPage();  
    // Add buyer info
    $pdf->BuyerInfo();

    // Add order table
    $pdf->OrderTable();

    // Output the PDF
    $pdf->Output("D", "Ticket_Order_" . $order['id'] . ".pdf");
    exit();
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
/* General Styles */
body {
    font-family: 'Poppins',sans-serif;
    background-color: #f0f4f8;
    margin: 0;
    padding: 0;
    color: #333;
}

/* Header Styles */
header {
    background: linear-gradient(135deg, #6366f1, #3b82f6);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin: 0;
}

/* Ticket Container Styles */
.ticket-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-top: 20px;
    transition: transform 0.3s;
}

.ticket-container:hover {
    transform: translateY(-5px);
}

/* Headings */
h1, h2, h3 {
    color: #6366f1; /* Match the header gradient color */
}

h2 {
    border-bottom: 2px solid #6366f1;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

/* Paragraphs */
p {
    line-height: 1.6;
    margin: 10px 0;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

th {
    background-color: #6366f1;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #e2e6ea;
}

/* Button Styles */
.actions {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.actions button {
    padding: 10px 20px;
    flex: none;
}

button.validate-btn, button.pdf-btn {
    background-color: #6366f1;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    font-size: 16px;
    font-weight: bold;
}

button.validate-btn:hover, button.pdf-btn:hover {
    background-color: #3b82f6;
    transform: translateY(-2px);
}

/* Input and Textarea Styles */
label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
}

input[type="text"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    transition: border-color 0.3s;
}

input[type="text"]:focus,
input[type="number"]:focus,
select:focus,
textarea:focus {
    border-color: #007bff;
    outline: none;
}

/* Textarea Styles */
textarea {
    resize: vertical;
}

/* Footer Styles */
footer {
    margin-top: 20px;
    text-align: center;
    font-size: 0.9em;
    color: #777;
}
.tracking-container {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.update-tracking {
    flex: 1; /* Allow this section to take up available space */
    margin-right: 20px; /* Space between the two sections */
}

.tracking-info {
    flex: 1; /* Allow this section to take up available space */
    background-color: #f9f9f9; /* Light background for contrast */
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.update-tracking h3,
.tracking-info h3 {
    color: #6366f1; /* Match the header gradient color */
}
</style>
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 1rem;">
        <h1 style="font-size: 2rem; color: white;">Order Ticket</h1>
        <a href="orders.php" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; color: white; background: rgba(255, 255, 255, 0.1); padding: 0.5rem 1rem; border-radius: 8px; backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <img src="back.png" alt="Back" style="width: 20px; height: 20px;">
            <span style="font-size: 16px;">Back to Orders</span>
        </a>
    </header>

    <div class="ticket-container">
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
            <form method="POST">
                <button type="submit" name="validate_ticket" class="btn validate-btn">Validate Ticket</button>
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
</body>
</html>