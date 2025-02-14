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
    <title>Dashboard - Orders</title>
    <link rel="stylesheet" href="add_product.css"> <!-- Ensure this path is correct -->
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

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            background-color: var(--surface-color);
            border-top: 1px solid #e2e8f0;
            margin-top: 4rem;
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

        /* Actions Styles */
        .actions {
            display: flex;
            flex-direction: row;
            justify-content: space-around;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .actions button {
            padding: 10px 20px;
            flex: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>Manage Orders</h1>
        <a href="dashboard.php" style="text-decoration: none;">
            <img src="back.png" alt="Back" style="width: 30px; height: 30px; vertical-align: middle;">
            <span style="color: #fff; font-size: 18px; vertical-align: middle;">Back to Dashboard</span>
        </a>
    </header>

    <div class="content">
        <h2>Orders</h2>
        
        <h3>Pending Orders</h3>
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
                        <td class="actions">
                            <?php if ($order['status'] == 'pending') : ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                    <button type="submit" name="validate_order">Validate</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                <button type="submit" name="delete_order" onclick="return confirm('Are you sure you want to delete this order?');">Delete</button>
                            </form>
                            <a href="ticket.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" style="margin-left: 5px;">
                                <button type="button">View Ticket</button>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Validated Orders</h3>
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
    </div>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>
