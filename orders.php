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
</head>
<body>
    <header>
        <h1 style="position: left;">Manage Products</h1>
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
                        <td><?php echo htmlspecialchars($order['total_price']); ?>   DZD</td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td>
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
                        <td>$<?php echo htmlspecialchars($order['total_price']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
