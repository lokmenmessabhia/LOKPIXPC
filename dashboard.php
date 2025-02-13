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
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    display: flex;
    background-color: #f5f7fa;
    color: #2c3e50;
}

/* Sidebar Styling */
.sidebar {
    width: 280px;
    background: linear-gradient(180deg, #4a69a5 0%, #3b5a8c 100%);
    color: white;
    padding: 25px;
    height: 100vh;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    position: fixed;
    transition: transform 0.3s ease;
}

.sidebar h1 {
    font-size: 28px;
    margin-bottom: 40px;
    font-weight: 600;
    text-align: center;
    letter-spacing: 1px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.sidebar nav a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-radius: 10px;
    transition: all 0.3s ease;
    margin-bottom: 12px;
    font-weight: 500;
}

.sidebar nav a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Menu Icon */
.menu-icon {
    font-size: 24px;
    cursor: pointer;
    display: none; /* Hidden by default */
    margin-bottom: 20px;
    text-align: center;
}

/* Main Content Area */
.content {
    flex: 1;
    padding: 35px;
    margin-left: 280px;
    overflow-y: auto;
}

.content h2 {
    font-size: 32px;
    margin-bottom: 30px;
    color: #2c3e50;
    font-weight: 600;
}

/* Cards Section */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    border: 1px solid rgba(74, 105, 165, 0.1);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
}

.card h3 {
    font-size: 18px;
    color: #4a69a5;
    margin-bottom: 15px;
    font-weight: 600;
}

.card p {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

/* Chart Section */
.chart-section {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin: 0 auto 40px;
    max-width: 500px;
    height: 400px;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(226, 232, 240, 0.8);
    position: relative;
}

.chart-section h3 {
    text-align: center;
    margin-bottom: 15px;
    font-size: 16px;
    color: #4f566b;
    font-weight: 500;
}

/* Notification Icon and Badge */
.notification-icon {
    position: fixed;
    top: 15px;
    right: 20px;
    z-index: 1000;
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.notification-icon img {
    width: 20px;
    height: 20px;
    filter: invert(31%) sepia(15%) saturate(2254%) hue-rotate(182deg) brightness(92%) contrast(87%);
}

#notificationCount {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #3a4f7a;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    border: 2px solid white;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.notification-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

/* Notification Popup */
.notification-popup {
    position: fixed;
    top: 70px;
    right: 20px;
    background: white;
    border-radius: 12px;
    width: 320px;
    max-height: 400px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    overflow: hidden;
}

.notification-popup.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.popup-header {
    padding: 15px 20px;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.popup-header h3 {
    margin: 0;
    font-size: 16px;
    color: #1a1f36;
    font-weight: 600;
}

.mark-read-btn {
    padding: 6px 12px;
    background: #3a4f7a;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mark-read-btn:hover {
    background: #2c3e50;
    transform: translateY(-1px);
}

/* Notification Items */
#notificationList {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 20px;
    border-bottom: 1px solid #edf2f7;
    transition: all 0.2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: #f8fafc;
    border-left: 3px solid #3a4f7a;
}

.notification-item:hover {
    background: #f1f5 f9;
}

.notification-item small {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
}

/* Scrollbar Styling */
#notificationList::-webkit-scrollbar {
    width: 6px;
}

#notificationList::-webkit-scrollbar-track {
    background: #f1f5f9;
}

#notificationList::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

#notificationList::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar {
        width: 240px;
    }
    
    .content {
        margin-left: 240px;
    }
    
    .cards {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }

    .menu-icon {
        display: none; /* Hide on larger screens */
    }
}

@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        top: 0;
        left: -100%;
        width: 80%;
        height: 100vh;
        z-index: 1000;
        transition: left 0.3s ease;
    }

    .sidebar.active {
        left: 0;
    }

    .content {
        margin-left: 0;
        padding: 15px;
        padding-top: 60px;
        width: 100%;
    }

    .content h2 {
        margin-top: 20px;
    }

    /* Hamburger menu button */
    .menu-toggle {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: #4a69a5;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    /* Cards layout */
    .cards {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .card {
        padding: 15px;
    }

    /* Chart section */
    .chart-section {
        height: 300px;
        margin: 15px 0;
    }

    /* Notification adjustments */
    .notification-icon {
        top: 15px;
        right: 15px;
    }

    .notification-popup {
        width: 90%;
        right: 5%;
        left: 5%;
        top: 60px;
    }

    /* Overlay for when sidebar is open */
    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .overlay.active {
        display: block;
    }
}
    </style>
</head>
<body>
<div class="menu-toggle">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
        <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
    </svg>
</div>
<div class="overlay"></div>
<div class="sidebar">
    <h1>Dashboard</h1>
    <nav>
        <div class="menu-icon" id="menuIcon">...</div> <!-- Three dots icon -->
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
            <a href="add_admin.php" data-page="admins">Admins</a>
        <?php endif; ?>
        <a href="add_feature.php" data-page="features">Features</a>
        <a href="dashboard_products.php" data-page="products">Products</a>
        <a href="orders.php" data-page="orders">Orders</a>
        <a href="manage_recycle.php" data-page="recycle">Recycling</a>
        <a href="manage_slider.php" data-page="slider">Slider</a>
    </nav>
    <nav>
        <a href="index.php">Go back to the website</a>
    </nav>
</div>

    <div class="content">
        <h2>Welcome to the Dashboard</h2>
        <p><strong>Admin Name:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
        
        <div class="cards">
            <div class="card">
                <h3>Total Products</h3>
                <p><?php 
                    $stmt = $pdo->query('SELECT COUNT(*) FROM products');
                    echo $stmt->fetchColumn(); 
                ?> Products</p>
            </div>

            <div class="card">
                <h3>Total Sales</h3>
                <p><?php 
                    $stmt = $pdo->query('SELECT SUM(quantity) FROM order_details');
                    echo $stmt->fetchColumn(); 
                ?> Units Sold</p>
            </div>

            <div class="card">
                <h3>Total Stock Value</h3>
                <p><?php 
                    // Query to calculate total stock value (stock * price for each product)
                    $stmt = $pdo->query('SELECT SUM(stock * price) FROM products');
                    echo "   DZD" . number_format($stmt->fetchColumn(), 2); 
                ?> Total Value</p>
            </div>

            <div class="card">
                <h3>Most Sold Product</h3>
                <p>
                    <?php 
                    try {
                        $stmt = $pdo->query('
                            SELECT p.name, SUM(od.quantity) AS total_sales
                            FROM products p
                            JOIN order_details od ON p.id = od.product_id
                            GROUP BY p.id
                            ORDER BY total_sales DESC
                            LIMIT 1
                        ');
                        
                        $most_sold = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($most_sold) {
                            echo htmlspecialchars($most_sold['name']) . " (Sold: " . htmlspecialchars($most_sold['total_sales']) . " units)";
                        } else {
                            echo "No sold product.";
                        }
                    } catch (PDOException $e) {
                        echo "Error retrieving most sold product: " . htmlspecialchars($e->getMessage());
                    }
                    ?>
                </p>
            </div>

            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
                <div class="card" style="color: green;">
                    <h3>Total Profit</h3>
                    <p><?php echo htmlspecialchars(number_format($total_profit, 2)); ?>   DZDs</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Section -->
        <div class="chart-section">
            <h3>Total Product Sales</h3>
            <canvas id="salesChart" width="300" height="300"></canvas>
        </div>

        <div class="notification-icon" id="notificationIcon">
    <img src="https://icons.veryicon.com/png/o/object/material-design-icons/notifications-1.png" alt="Notifications" />
    <span id="notificationCount"><?php echo $new_orders_count; ?></span>
</div>

<div class="notification-popup" id="notificationPopup">
    <div class="popup-header">
        <h3>Unread Notifications</h3>
        <button id="markAsRead" class="mark-read-btn">Mark All as Read</button>
    </div>
    
    <!-- Unread Notifications Section -->
    <div id="unreadNotificationsSection">
        <ul id="notificationList">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <li class="notification-item unread">
                        <?php echo htmlspecialchars($notification['message']); ?>
                        <small><?php echo htmlspecialchars($notification['created_at']); ?></small>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No new notifications.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Read Notifications Section -->
    <div id="readNotificationsSection" style="display: none;">
        <h3>Read Notifications</h3>
        <ul id="readNotificationList">
            <!-- Read notifications will be populated here -->
        </ul>
    </div>
</div>

<script>
    document.getElementById('menuIcon').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
});
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