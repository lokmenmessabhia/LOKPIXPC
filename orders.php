<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

// Check if user is logged in and is an admin

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
    if (isset($_POST['validate_order'])) {
        $order_id = (int)$_POST['order_id'];
        $stmt = $pdo->prepare("UPDATE orders SET status = 'validated' WHERE id = ?");
        $stmt->execute([$order_id]);
    } elseif (isset($_POST['delete_order'])) {
        $order_id = (int)$_POST['order_id'];

        try {
            // First, delete the order details associated with the order
            $stmt = $pdo->prepare("DELETE FROM order_details WHERE order_id = ?");
            $stmt->execute([$order_id]);

            // Then, delete the order itself
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);

        } catch (PDOException $e) {
            echo "Error: Unable to delete the order. " . $e->getMessage();
            exit();
        }
    }
}

// Fetch orders from database
try {
    $stmt = $pdo->query("
        SELECT orders.id, users.email AS user_email, orders.phone, orders.address, orders.wilaya_id, orders.delivery_type, orders.total_price, orders.order_date, orders.status 
        FROM orders 
        JOIN users ON orders.user_id = users.id
        WHERE orders.status = 'pending'
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_validated = $pdo->query("
        SELECT orders.id, users.email AS user_email, orders.phone, orders.address, orders.wilaya_id, orders.delivery_type, orders.total_price, orders.order_date 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE orders.status = 'validated'
    ");
    $validated_orders = $stmt_validated->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: Unable to fetch orders. " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - EcoTech</title>
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

        .page-title {
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
            color: var(--text);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            background-color: var(--bg-card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

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

        .btn-validate, button[name="validate_order"], .btn-view, button[type="button"] {
            background-color: var(--primary);
            color: white;
        }

        .btn-validate:hover, button[name="validate_order"]:hover, .btn-view:hover, button[type="button"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-delete, button[name="delete_order"] {
            background-color: var(--danger);
            color: white;
        }

        .btn-delete:hover, button[name="delete_order"]:hover {
            filter: brightness(0.9);
            transform: translateY(-2px);
        }

        /* Action Buttons Container */
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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

            table {
                display: block;
                overflow-x: auto;
            }

            th, td {
                padding: 0.75rem;
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
            <h1>EcoTech</h1>
        </div>
        <div class="nav-menu">
            <a href="orders.php" class="active">Orders</a>
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
        <h2 class="page-title">Manage Orders</h2>
        
        <h3 class="section-title">Pending Orders</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Wilaya ID</th>
                    <th>Delivery Type</th>
                    <th>Total Price</th>
                    <th>Order Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td><?php echo htmlspecialchars($order['address']); ?></td>
                        <td><?php echo htmlspecialchars($order['wilaya_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['delivery_type']); ?></td>
                        <td><?php echo htmlspecialchars($order['total_price']); ?> DZD</td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td>
                            <div class="actions">
                                <?php if ($order['status'] == 'pending') : ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <button type="submit" name="validate_order" class="btn-validate">Validate</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                    <button type="submit" name="delete_order" class="btn-delete" onclick="return confirm('Are you sure you want to delete this order?');">Delete</button>
                                </form>
                                <a href="ticket.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" class="btn-view">View Ticket</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 class="section-title">Validated Orders</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Wilaya ID</th>
                    <th>Delivery Type</th>
                    <th>Total Price</th>
                    <th>Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($validated_orders as $order) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td><?php echo htmlspecialchars($order['address']); ?></td>
                        <td><?php echo htmlspecialchars($order['wilaya_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['delivery_type']); ?></td>
                        <td><?php echo htmlspecialchars($order['total_price']); ?> DZD</td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> EcoTech. All rights reserved.</p>
    </footer>
</body>
</html>
