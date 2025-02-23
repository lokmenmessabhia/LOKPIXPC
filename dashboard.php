<?php
session_start();
include 'db_connect.php';

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

// Fetch the logged-in admin's details
$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// If the admin is not found, destroy the session and redirect to login
if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch random product and feature
$stmt = $pdo->prepare('SELECT * FROM products ORDER BY RAND() LIMIT 1');
$stmt->execute();
$product = $stmt->fetch();

// Fetch analytics
// 1. Most sold product
$stmt = $pdo->prepare('
    SELECT products.name, SUM(order_details.quantity) AS total_sold 
    FROM order_details 
    JOIN products ON order_details.product_id = products.id 
    GROUP BY products.id 
    ORDER BY total_sold DESC 
    LIMIT 1
');
$stmt->execute();
$most_sold_product = $stmt->fetch();

// 2. Total capital (stock value)
$stmt = $pdo->prepare('SELECT SUM(price * stock) AS total_capital FROM products');
$stmt->execute();
$total_capital = $stmt->fetchColumn();

// 3. Total profit from products (only for superadmin)
$total_profit = 0;
if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
    // Calculate total profit based on the orders
    $stmt = $pdo->prepare('
        SELECT 
            SUM((products.price - products.buying_price) * order_details.quantity) AS total_profit
        FROM order_details
        JOIN products ON order_details.product_id = products.id');
    $stmt->execute();
    $total_profit = $stmt->fetchColumn();
}

// Get sales data for chart
$stmt = $pdo->prepare('
    SELECT products.name, SUM(order_details.quantity) AS total_sold 
    FROM order_details 
    JOIN products ON order_details.product_id = products.id 
    GROUP BY products.id 
    ORDER BY total_sold DESC
');
$stmt->execute();
$products_sales = $stmt->fetchAll();

// Prepare product names, quantities, and percentages for Chart.js
$product_names = [];
$sold_quantities = [];
$percentages = [];
$total_sales = 0;

foreach ($products_sales as $product) {
    $product_names[] = $product['name'];
    $sold_quantities[] = (int) $product['total_sold'];
    $total_sales += (int) $product['total_sold'];
}

// Calculate percentages
foreach ($sold_quantities as $quantity) {
    $percentages[] = ($total_sales > 0) ? ($quantity / $total_sales) * 100 : 0;
}

// Fetch unread notifications for the logged-in admin
$query = "SELECT id, message, created_at FROM notifications WHERE is_read = 0 ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();

// Fetch notifications
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count new notifications
$new_orders_count = count($notifications);
try {
    $sql = "UPDATE notifications SET is_read = 1 WHERE is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTech Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #1f2937;
            --text-light: #6b7280;
            --bg: #f9fafb;
            --bg-card: #ffffff;
            --border: #e5e7eb;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        /* Top Navigation */
        .top-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        .nav-brand h1 {
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .nav-menu a {
            color: var(--text);
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: 0.2s;
            white-space: nowrap;
        }

        .nav-menu a:hover {
            background: var(--bg);
            color: var(--primary);
        }

        .nav-end {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        /* Main Content */
        .main-content {
            margin-top: 4rem;
            padding: 2rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .card h2 {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .card p {
            color: var(--text);
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Chart */
        .chart-container {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 1.5rem;
            border: 1px solid var(--border);
            margin-top: 2rem;
            min-height: 400px;
            position: relative;
        }

        .chart-container h2 {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .chart-container canvas {
            width: 100% !important;
            height: 350px !important;
        }

        /* Notifications */
        .notification-icon {
            background: var(--bg);
            width: 38px;
            height: 38px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
        }

        .notification-icon svg {
            width: 18px;
            height: 18px;
            color: var(--text);
        }

        .notification-icon:hover {
            background: var(--border);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary);
            color: white;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 2px solid var(--bg-card);
        }

        .notification-popup {
            position: fixed;
            top: 3.75rem;
            right: 1.5rem;
            width: 320px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 1000;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-popup.show {
            opacity: 1;
            transform: translateY(0);
        }

        .popup-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }

        .popup-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #1f2937;
        }

        .mark-read-btn {
            background: transparent;
            color: var(--primary);
            border: none;
            font-size: 0.875rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .mark-read-btn:hover {
            background: rgba(37, 99, 235, 0.1);
        }

        .notification-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #f3f4f6;
        }

        .notification-dot {
            width: 8px;
            height: 8px;
            background-color: var(--primary);
            border-radius: 50%;
            margin-top: 0.5rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

        .empty-notifications {
            padding: 2rem;
            text-align: center;
            color: #6b7280;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .welcome-section h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .date {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .stats-overview {
            margin-bottom: 2rem;
        }

        .card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
        }

        .card.highlight {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .card.highlight h3,
        .card.highlight p {
            color: white;
        }

        .card-icon {
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius);
        }

        .card-content {
            flex: 1;
        }

        .card h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .number {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .trend {
            font-size: 0.875rem;
            color: var(--success);
        }

        .subtitle {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-section {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }

        .chart-period {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
        }

        .chart-period.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .recent-activity {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .activity-list {
            margin-top: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            padding: 0.5rem;
            border-radius: var(--radius);
            background: var(--bg);
        }

        .activity-icon.processing {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .activity-icon.completed {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .activity-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .activity-status.processing {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .activity-status.completed {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .admin-insights {
            margin-top: 2rem;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 1rem;
        }

        .insight-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .category-list {
            margin-top: 1rem;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-info h4 {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .category-orders {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .inventory-status {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .inventory-item {
            text-align: center;
            padding: 1rem;
            border-radius: var(--radius);
        }

        .inventory-item.critical {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .inventory-item.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .inventory-item.success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .inventory-item .count {
            display: block;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .inventory-item .label {
            font-size: 0.875rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .insights-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .quick-actions {
                width: 100%;
            }

            .action-btn {
                flex: 1;
            }

            .grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-brand">
            <h1>EcoTech</h1>
        </div>
        <div class="nav-menu">
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
                <a href="add_admin.php">Admins</a>
            <?php endif; ?>
            <a href="add_feature.php">Features</a>
            <a href="dashboard_products.php">Products</a>
            <a href="orders.php">Orders</a>
            <a href="manage_recycle.php">Recycling</a>
            <a href="manage_slider.php">Slider</a>
            <a href="manage_marketplace.php">Marketplace</a>
            <a href="index.php">Website</a>
        </div>
        <div class="nav-end">
            <div class="notification-icon" id="notificationIcon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <span class="notification-badge"><?php echo $new_orders_count; ?></span>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($admin['email']); ?></h1>
                <p class="date"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="quick-actions">
            <button class="action-btn" onclick="location.href='add_product.php'">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12h14"/>
    </svg>
    Add Product
</button>



   
</div>
        </div>

        <div class="stats-overview">
            <div class="grid">
                <div class="card highlight">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 01-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 000 4h4v-4z"/></svg>
                    </div>
                    <div class="card-content">
                        <h3>Total Revenue</h3>
                        <p class="number">
                            <?php
                                $stmt = $pdo->prepare('
                                    SELECT COALESCE(SUM(p.price * od.quantity), 0) as total_revenue
                                    FROM orders o
                                    JOIN order_details od ON o.id = od.order_id
                                    JOIN products p ON od.product_id = p.id
                                ');
                                $stmt->execute();
                                echo number_format($stmt->fetchColumn(), 2) . ' DZD';
                            ?>
                        </p>
                        <p class="trend">
                            <?php
                                $last_month = date('Y-m-d', strtotime('-1 month'));
                                $stmt = $pdo->prepare('
                                    SELECT COALESCE(SUM(p.price * od.quantity), 0) as last_month_revenue
                                    FROM orders o
                                    JOIN order_details od ON o.id = od.order_id
                                    JOIN products p ON od.product_id = p.id
                                    WHERE DATE(o.order_date) >= ?
                                ');
                                $stmt->execute([$last_month]);
                                $monthly_revenue = $stmt->fetchColumn();
                                echo 'Last 30 days: ' . number_format($monthly_revenue, 2) . ' DZD';
                            ?>
                        </p>
                    </div>
                </div>

                <div class="card">
    <div class="card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-7L10 4H4a2 2 0 00-2 2v12c0 1.1.9 2 2 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
    </div>
    <div class="card-content">
        <h3>Total Products</h3>
        <p class="number">
            <?php
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM products');
                $stmt->execute();
                echo $stmt->fetchColumn();
            ?>
        </p>
        <p class="subtitle">In Store</p>
    </div>
</div>

<div class="card">
    <div class="card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    </div>
    <div class="card-content">
        <h3>Total Sales</h3>
        <p class="number">
            <?php
                $stmt = $pdo->prepare('
                    SELECT COUNT(*) 
                    FROM orders
                ');
                $stmt->execute();
                echo $stmt->fetchColumn();
            ?>
        </p>
        <p class="subtitle">All Time</p>
    </div>
</div>

                <div class="card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-7L10 4H4a2 2 0 00-2 2v12c0 1.1.9 2 2 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
                    </div>
                    <div class="card-content">
                        <h3>Low Stock Items</h3>
                        <p class="number">
                            <?php
                                $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE stock <= 10');
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                            ?>
                        </p>
                        <p class="subtitle">Need Attention</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="chart-section">
                <div class="chart-container">
                    <div class="chart-header">
                        <h2>Sales Overview</h2>
                        <div class="chart-actions">
                            <button class="chart-period active" data-period="week">Week</button>
                            <button class="chart-period" data-period="month">Month</button>
                            <button class="chart-period" data-period="year">Year</button>
                        </div>
                    </div>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Activity</h2>
                <div class="activity-list">
                    <?php
                        // Fetch recent orders
                        $stmt = $pdo->prepare('
                            SELECT o.id, o.order_date, u.email, o.status,
                                   SUM(p.price * od.quantity) as total_amount
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            JOIN order_details od ON o.id = od.order_id
                            JOIN products p ON od.product_id = p.id
                            GROUP BY o.id, o.order_date, u.email, o.status
                            ORDER BY o.order_date DESC
                            LIMIT 5
                        ');
                        $stmt->execute();
                        $recent_orders = $stmt->fetchAll();

                        foreach ($recent_orders as $order):
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $order['status']; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/></svg>
                        </div>
                        <div class="activity-details">
                            <p class="activity-title">New order #<?php echo $order['id']; ?></p>
                            <p class="activity-meta">
                                <?php echo htmlspecialchars($order['email']); ?> •
                                <?php echo number_format($order['total_amount'], 2); ?> DZD
                            </p>
                            <p class="activity-time"><?php echo date('g:i A', strtotime($order['order_date'])); ?></p>
                        </div>
                        <div class="activity-status <?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
        <div class="admin-insights">
            <h2>Business Insights</h2>
            <div class="insights-grid">
                <div class="insight-card">
                    <h3>Top Performing Categories</h3>
                    <div class="category-list">
                        <?php
                            $stmt = $pdo->prepare('
                                SELECT c.name, COUNT(o.id) as order_count, SUM(p.price * od.quantity) as revenue
                                FROM categories c
                                JOIN products p ON c.id = p.category_id
                                JOIN order_details od ON p.id = od.product_id
                                JOIN orders o ON od.order_id = o.id
                                GROUP BY c.id
                                ORDER BY revenue DESC
                                LIMIT 3
                            ');
                            $stmt->execute();
                            $top_categories = $stmt->fetchAll();

                            foreach ($top_categories as $category):
                        ?>
                        <div class="category-item">
                            <div class="category-info">
                                <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                <p><?php echo number_format($category['revenue'], 2); ?> DZD</p>
                            </div>
                            <div class="category-orders">
                                <?php echo $category['order_count']; ?> orders
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="insight-card">
                    <h3>Inventory Status</h3>
                    <div class="inventory-status">
                        <?php
                            $stmt = $pdo->prepare('
                                SELECT 
                                    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                                    SUM(CASE WHEN stock BETWEEN 1 AND 10 THEN 1 ELSE 0 END) as low_stock,
                                    SUM(CASE WHEN stock > 10 THEN 1 ELSE 0 END) as in_stock
                                FROM products
                            ');
                            $stmt->execute();
                            $inventory = $stmt->fetch();
                        ?>
                        <div class="inventory-item critical">
                            <span class="count"><?php echo $inventory['out_of_stock']; ?></span>
                            <span class="label">Out of Stock</span>
                        </div>
                        <div class="inventory-item warning">
                            <span class="count"><?php echo $inventory['low_stock']; ?></span>
                            <span class="label">Low Stock</span>
                        </div>
                        <div class="inventory-item success">
                            <span class="count"><?php echo $inventory['in_stock']; ?></span>
                            <span class="label">In Stock</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

<div class="notification-popup" id="notificationPopup">
    <div class="popup-header">
        <h3>Notifications</h3>
        <button id="markAsRead" class="mark-read-btn">Mark all as read</button>
    </div>
    <div class="notification-list" id="notificationList">
        <!-- Notifications will be dynamically inserted here -->
        <div class="empty-notifications">
            <p>No new notifications</p>
        </div>
    </div>
</div>

<script>
    document.getElementById('notificationIcon').addEventListener('click', function(event) {
    const popup = document.getElementById('notificationPopup');
    const currentDisplay = popup.style.display;

    // Toggle popup visibility with smooth animation
    if (currentDisplay === 'none' || currentDisplay === '') {
        popup.style.display = 'block';
        setTimeout(() => popup.classList.add('show'), 10); // Ensure smooth fade-in
    } else {
        popup.classList.remove('show');
        setTimeout(() => popup.style.display = 'none', 300); // Hide after animation
    }
    
    event.stopPropagation(); // Prevent click propagation
});

// Close the popup if clicked outside
window.addEventListener('click', function(event) {
    const popup = document.getElementById('notificationPopup');
    const notificationIcon = document.getElementById('notificationIcon');
    
    // Ensure click outside of the notification popup or icon closes the popup
    if (!event.target.matches('#notificationIcon') && !popup.contains(event.target)) {
        popup.classList.remove('show');
        setTimeout(() => popup.style.display = 'none', 300);
    }
});

// Handle the "Mark All as Read" functionality
document.getElementById('markAsRead').addEventListener('click', function() {
    const unreadItems = document.querySelectorAll('.unread');
    const readList = document.getElementById('readNotificationList');
    
    // Move unread notifications to read section in the UI
    unreadItems.forEach(function(item) {
        // Mark the notification as read by changing its class and moving it to the read section
        item.classList.remove('unread');
        item.classList.add('read-notification-item');
        
        // Append the notification to the read section
        readList.appendChild(item);
    });

    // Hide the unread notifications section and show the read notifications section
    document.getElementById('unreadNotificationsSection').style.display = 'none';
    document.getElementById('readNotificationsSection').style.display = 'block';

    // Optionally, hide the notification count if no more unread notifications
    const notificationCount = document.getElementById('notificationCount');
    if (notificationCount) {
        notificationCount.style.display = 'none';
    }

    // Make AJAX request to update the database
   

});

</script>



    <script>
        // Check if the data arrays are empty
        if (<?php echo count($product_names); ?> === 0 || <?php echo count($sold_quantities); ?> === 0) {
            alert('No sales data available for the chart');
        } else {
            // Pass PHP data to JavaScript
            const productNames = <?php echo json_encode($product_names); ?>;
            const soldQuantities = <?php echo json_encode($sold_quantities); ?>;
            const percentages = <?php echo json_encode($percentages); ?>;
            
            // Create the chart using Chart.js
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Create the chart with original options
            const salesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: productNames,
                    datasets: [{
                        data: soldQuantities,
                        backgroundColor: [
                            '#4a69a5',    // Primary blue
                            '#2ecc71',    // Green
                            '#3498db',    // Light Blue
                            '#9b59b6',    // Purple
                            '#f1c40f',    // Yellow
                            '#e74c3c',    // Red
                            '#1abc9c',    // Turquoise
                            '#ff6f61'     // Coral
                        ],
                        borderWidth: 0,
                        hoverOffset: 15   // Increase hover effect
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 20
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#4a69a5',
                            bodyColor: '#2c3e50',
                            titleFont: {
                                family: "'Poppins', sans-serif",
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: "'Poppins', sans-serif",
                                size: 13
                            },
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            cornerRadius: 8,
                            displayColors: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = percentages[context.dataIndex].toFixed(1);
                                    return ` ${label}: ${value} units (${percentage}%)`;
                                },
                                labelPointStyle: function(context) {
                                    return {
                                        pointStyle: 'circle',
                                        rotation: 0
                                    };
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    radius: '85%',
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    }
                }
            });
        }

        // Function to fetch notifications
        function fetchNotifications() {
            fetch('fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    notificationList.innerHTML = ''; // Clear existing notifications

                    if (data.length > 0) {
                        data.forEach(notification => {
                            const li = document.createElement('li');
                            li.innerHTML = `${notification.message} <small>${notification.created_at}</small>`;
                            notificationList.appendChild(li);
                        });
                    } else {
                        notificationList.innerHTML = '<li>No new notifications.</li>';
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }



        // Fetch notifications every 10 seconds
        setInterval(fetchNotifications, 10000); // 10000 milliseconds = 10 seconds

        // Initial fetch of notifications
        fetchNotifications();
    </script>

    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');

            // Toggle menu when hamburger is clicked
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });

            // Close menu when overlay is clicked
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });

            // Close menu when a nav link is clicked
            document.querySelectorAll('.sidebar nav a').forEach(link => {
                link.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            });
        });
    </script>
</body>
</html>