<?php
session_start();
include 'db_connect.php';

// Get the product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: marketplace.php");
    exit;
}

// Get user info if logged in
$user = null;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Check if the user is an admin
$isAdmin = false; // Default to false
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $isAdmin = $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        echo "Error: Unable to verify admin status. " . $e->getMessage();
    }
}

// Fetch product details including category, subcategory, and seller information
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, s.name as subcategory_name, 
        u.email as seller_email, u.phone as seller_phone,
        u.profile_picture as seller_picture, u.username as seller_name
        FROM marketplace_items p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: marketplace.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching product details: " . $e->getMessage());
}

// Get cart count
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Listing Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Base styles */
        :root {
            --primary-blue: #0275d8;
            --primary-blue-dark: #025aa5;
            --primary-green: #218838;
            --hover-blue: #e7f1ff;
            --text-dark: #2c3e50;
            --text-light: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text-dark);
            background: #f8f9fa;
            line-height: 1.6;
        }

        /* Header */
        header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            flex-shrink: 0;
        }

        .logo img {
            height: 70px;
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        /* Search Bar */
        .search-container {
            flex: 1;
            margin: 0;
        }

        .new-search-form {
            display: flex;
            background: white;
            border-radius: 30px;
            box-shadow: var(--shadow);
            overflow: hidden;
            max-width: 500px;
            margin-left: auto;
        }

        .search-input {
            flex: 1;
            padding: 0.8rem 1.5rem;
            border: 2px solid transparent;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-blue);
        }

        .new-search-button {
            padding: 0.8rem 2rem;
            background: var(--primary-blue);
            color: var(--text-light);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .new-search-button:hover {
            background: var(--primary-blue-dark);
        }

        /* Navigation */
        nav {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* Profile Section */
        .profile-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-pic img {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-email {
            color: var(--text-dark);
            font-weight: 500;
        }
        .main-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            list-style: none;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .main-nav li a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            color: var(--primary-blue);
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
            box-shadow: var(--shadow);
        }

        .main-nav li a:hover {
            background: var(--hover-blue);
            transform: translateY(-2px);
        }

        /* Side Menu */
        .side-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 300px;
            height: 100%;
            background: white;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1001;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .side-menu.show {
            right: 0;
        }

        /* Product container */
        .product-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        /* Image section */
        .product-image {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .product-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
        }

        /* Details section */
        .product-details {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .product-title {
            font-size: 2rem;
            margin: 0 0 1rem 0;
            color: var(--text-dark);
        }

        .product-price {
            font-size: 1.5rem;
            color: var(--primary-green);
            font-weight: bold;
            margin: 1rem 0;
        }

        .product-categories {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .category-tag {
            background: var(--hover-blue);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .subcategory-tag {
            background: #e9ecef;
            color: var(--text-dark);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .condition-tag {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }

        /* Add to cart form */
        .cart-form {
            margin-top: 2rem;
        }

        .quantity-input {
            padding: 0.5rem;
            width: 80px;
            margin-right: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .add-to-cart-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .add-to-cart-btn:hover {
            background: #1e7e34;
        }

        /* Seller info */
        .seller-info {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .seller-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
        }

        /* Back button */
        .back-btn {
            display: inline-block;
            margin: 1rem;
            padding: 0.5rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 4px;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: var(--primary-blue);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 0.5rem 1rem;
            }

            .logo {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.5rem;
            }

            .logo img {
                height: 40px;
            }

            .search-container {
                width: 100%;
                order: 2;
                margin: 0.5rem 0;
            }

            .new-search-form {
                margin: 0;
                width: 100%;
            }

            .product-container {
                grid-template-columns: 1fr;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .product-price {
                font-size: 1.25rem;
            }
        }

        .product-description {
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .product-description h3 {
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .seller-profile {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .seller-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .seller-details {
            flex: 1;
        }

        .seller-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .seller-contact {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .seller-contact i {
            width: 20px;
            color: var(--primary-blue);
        }

        .listing-date {
            font-size: 0.9rem;
            color: #666;
        }

        .login-prompt {
            text-align: center;
            margin: 1rem 0;
            padding: 1rem;
            background: var(--hover-blue);
            border-radius: 8px;
        }

        .login-prompt a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: bold;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        .contact-seller {
            margin-top: 2rem;
        }

        .contact-seller-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .contact-seller-btn:hover {
            background-color: var(--primary-blue-dark);
        }

        .contact-seller-btn i {
            font-size: 18px;
        }

        /* Modal styles */
        :root {
            --primary-blue: #0084ff;
            --light-gray: #f0f2f5;
            --border-color: #e4e6eb;
            --text-dark: #050505;
            --text-gray: #65676b;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            height: 80vh;
            overflow: hidden;
        }

        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-radius: 12px 12px 0 0;
        }

        .chat-header-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .chat-user {
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 16px;
            color: var(--text-dark);
        }

        .chat-product {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 4px;
        }

        .close {
            position: absolute;
            right: 16px;
            top: 16px;
            color: #8e8e8e;
            font-size: 20px;
            cursor: pointer;
        }

        .close:hover {
            color: #262626;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .message {
            display: flex;
            align-items: flex-start;
            margin: 2px 0;
            max-width: 85%;
            position: relative;
            width: 100%;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message-wrapper {
            display: flex;
            align-items: flex-start;
            max-width: 70%;
        }

        .message.sent .message-wrapper {
            flex-direction: row-reverse;
        }

        .chat-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin: 0 8px;
            object-fit: cover;
        }

        .message.sent .chat-avatar {
            margin-left: 8px;
        }

        .message.received .chat-avatar {
            margin-right: 8px;
        }

        .message-content {
            padding: 8px 12px;
            border-radius: 22px;
            font-size: 14px;
            line-height: 1.4;
            position: relative;
            word-wrap: break-word;
            max-width: 100%;
        }

        .message.sent .message-content {
            background-color: var(--primary-blue);
            color: white;
            margin-right: 8px;
        }

        .message.received .message-content {
            background-color: var(--light-gray);
            color: var(--text-dark);
            margin-left: 8px;
        }

        .message-time {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 2px;
            margin-bottom: 4px;
        }

        .message-status {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 2px;
            margin-bottom: 8px;
            text-align: right;
        }

        .message.received .message-status {
            display: none;
        }

        .message-input-container {
            display: flex;
            align-items: center;
            padding: 16px;
            background-color: #fff;
            border-top: 1px solid var(--border-color);
        }

        .message-input {
            flex: 1;
            border: 1px solid var(--border-color);
            border-radius: 22px;
            padding: 8px 12px;
            margin-right: 8px;
            font-size: 14px;
            outline: none;
            resize: none;
            max-height: 100px;
        }

        .message-input:focus {
            border-color: var(--primary-blue);
        }

        .send-button {
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .send-button:hover {
            background: #0073e6;
        }

        .send-button i {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .modal {
                padding: 10px;
            }

            .modal-content {
                margin: 2% auto;
                width: 95%;
                height: 90vh;
            }

            .message {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="logo (1) text.png" alt="EcoTech Logo"></a>
            <span class="nav-toggle" onclick="toggleMenu()">☰</span>
        </div>
        
        <div class="search-container">
            <form action="search.php" method="GET" class="new-search-form">
                <button type="submit" class="new-search-button"><i class="fas fa-search"></i></button>
                <input type="text" name="query" class="search-input" placeholder=" " aria-label="Search products...">
            </form>
        </div>
    </header>

    <nav>
        <ul class="main-nav">
            <li><a href="index.php"><i class="fas fa-home fa-sm"></i> Home</a></li>
            <li><a href="about.php"><i class="fas fa-info-circle fa-sm"></i> About</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope fa-sm"></i> Contact Us</a></li>
            <li><a href="marketplace.php"><i class="fas fa-store fa-sm"></i> Marketplace</a></li>
            
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <li class="profile-item">
                    <a href="profile.php" class="profile-pic">
                        <img src="<?php echo $user['profile_picture'] ? 'uploads/profiles/' . htmlspecialchars($user['profile_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" 
                             alt="Profile Picture">
                        <span class="profile-email"><?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?></span>
                    </a>
                </li>
                <li>
                <a href="inbox.php" class="nav-link">
                    <i class="far fa-envelope"></i> Messages
                    <?php if (isset($unread) && $unread > 0): ?>
                        <span class="unread-count"><?php echo $unread; ?></span>
                    <?php endif; ?>
                </a>
            </li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart fa-sm"></i> Cart (<span id="cart-count"><?php echo $cartCount; ?></span>)</a></li>
                <li><a href="logout.php" style="color:red;"><i class="fas fa-sign-out-alt fa-sm"></i> Logout</a></li>
                
                <?php if ($isAdmin): ?>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt fa-sm"></i> Dashboard</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt fa-sm"></i> Login</a></li>
                <li><a href="sign-up.php"><i class="fas fa-user-plus fa-sm"></i> Sign Up</a></li>
            <?php endif; ?>
            <li><a href="#" onclick="openAddListingModal(event)" style="background-color: #218838;color:white;font-weight: bold;"><i class="fas fa-plus-circle fa-sm"></i> Add Listing</a></li>
            <li><a href="recycle.php" style="background-color: #218838;color:white;font-weight: bold;"><i class="fa fa-recycle"style="font-size:22px"></i> Recycle</a></li>
        </ul>
    </nav>

    <div id="side-menu" class="side-menu">
        <span class="closebtn" onclick="closeMenu()">×</span>
        
        <div class="side-menu-top">
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <div class="profile">
                    <img src="<?php echo $user['profile_picture'] ? 'uploads/profiles/' . htmlspecialchars($user['profile_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" 
                         alt="Profile Picture">
                    <h2 style="font-size: 1.2rem; margin-top: 0.5rem;color:green;">Welcome, <?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?>!</h2>
                </div>
            <?php else: ?>
                <div class="profile">
                    <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Welcome!</h2>
                    <p style="font-size: 0.9rem; opacity: 0.9;color:green;">Please login or sign up to access all features</p>
                </div>
            <?php endif; ?>

            <div class="side-menu-nav">
                <a href="index.php" style="text-decoration:none"><i class="fas fa-home"></i></a>
                <a href="about.php" style="text-decoration:none"><i class="fas fa-info-circle"></i></a>
                <a href="contact.php" style="text-decoration:none"><i class="fas fa-envelope"></i></a>
                
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <a href="profile.php" style="text-decoration:none"><i class="fas fa-user"></i></a>
                    <a href="cart.php" style="text-decoration:none"><i class="fas fa-shopping-cart"></i></a>
                    <?php if ($isAdmin): ?>
                        <a href="dashboard.php" style="text-decoration:none"><i class="fas fa-tachometer-alt"></i></a>
                    <?php endif; ?>
                    <a href="logout.php" class="logout-icon" style="text-decoration:none"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" style="text-decoration:none"><i class="fas fa-sign-in-alt"></i></a>
                    <a href="sign-up.php" style="text-decoration:none"><i class="fas fa-user-plus"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="side-menu-bottom">
            <div class="bottom-actions">
                <a href="#" onclick="openAddListingModal(event)" class="bottom-action-btn add-listing-btn">
                    <i class="fas fa-plus-circle"></i>
                    Add Listing
                </a>
                <a href="recycle.php" class="bottom-action-btn recycle-btn"style="color:white;font-weight: bold;">
                    <i class="fa fa-recycle"></i>
                    Recycle
                </a>
            </div>
        </div>
    </div>

    <a href="marketplace.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Marketplace
    </a>

    <div class="product-container">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>

        <div class="product-details">
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-categories">
                <span class="category-tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <span class="subcategory-tag"><?php echo htmlspecialchars($product['subcategory_name']); ?></span>
                <span class="condition-tag"><?php echo htmlspecialchars($product['condition']); ?></span>
            </div>

            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
            
            <div class="product-description">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <div class="seller-info">
                <h3>Seller Information</h3>
                <div class="seller-profile">
                    <img src="<?php echo $product['seller_picture'] ? 'uploads/profiles/' . htmlspecialchars($product['seller_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" 
                         alt="Seller Profile Picture" class="seller-picture">
                    <div class="seller-details">
                        <p class="seller-name"><?php echo htmlspecialchars($product['seller_name']); ?></p>
                        <p class="seller-contact">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($product['seller_email']); ?><br>
                            <?php if ($product['seller_phone']): ?>
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($product['seller_phone']); ?>
                            <?php endif; ?>
                        </p>
                        <p class="listing-date">Listed on: <?php echo date('F j, Y', strtotime($product['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <div class="contact-seller">
                    <button onclick="openMessagingModal('<?php echo htmlspecialchars($product['seller_email']); ?>', '<?php echo htmlspecialchars($product['name']); ?>')" 
                            class="contact-seller-btn">
                        <i class="fas fa-envelope"></i> Contact Seller
                    </button>
                </div>
            <?php else: ?>
                <p class="login-prompt">Please <a href="login.php">login</a> to contact the seller</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Menu toggle functions
        function toggleMenu() {
            const sideMenu = document.getElementById('side-menu');
            sideMenu.classList.toggle('show');
            document.body.style.overflow = sideMenu.classList.contains('show') ? 'hidden' : '';
        }

        function closeMenu() {
            const sideMenu = document.getElementById('side-menu');
            sideMenu.classList.remove('show');
            document.body.style.overflow = '';
        }

        function openMessagingModal(sellerEmail, productName) {
            // Get seller info from PHP variables
            const sellerName = '<?php echo htmlspecialchars($product['seller_name'] ? $product['seller_name'] : explode('@', $product['seller_email'])[0]); ?>';
            const sellerPic = '<?php echo $product['seller_picture'] ? 'uploads/profiles/' . htmlspecialchars($product['seller_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>';
            const productImg = '<?php echo htmlspecialchars($product['image_url']); ?>';
            
            // Set hidden inputs
            document.getElementById('seller_email').value = sellerEmail;
            document.getElementById('product_name').value = productName;
            
            // Update chat header
            document.getElementById('chat-title').innerHTML = `
                <div class="chat-header-content">
                    <div class="chat-user">
                        <img src="${sellerPic}" alt="Seller Profile" class="chat-avatar" style="width: 24px; height: 24px; margin-right: 8px;">
                        ${sellerName}
                    </div>
                    <div class="chat-product">
                        <img src="${productImg}" alt="Product" style="width: 16px; height: 16px; margin-right: 4px; border-radius: 2px;">
                        ${productName}
                    </div>
                </div>
                <span class="close" onclick="closeModal()">&times;</span>
            `;
            
            // Show modal
            const modal = document.getElementById('messaging-modal');
            modal.style.display = 'block';
            
            // Clear existing messages and load new ones
            const messagesContainer = document.getElementById('messages-container');
            messagesContainer.innerHTML = '';
            loadMessages(sellerEmail);
            
            // Focus input
            document.getElementById('message-input').focus();
            
            // Start message checking interval
            if (window.messageInterval) {
                clearInterval(window.messageInterval);
            }
            window.messageInterval = setInterval(() => {
                if (modal.style.display === 'block') {
                    loadMessages(sellerEmail);
                } else {
                    clearInterval(window.messageInterval);
                }
            }, 3000);
        }

        function loadMessages(userEmail) {
            fetch('get_messages.php?seller_email=' + encodeURIComponent(userEmail))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const messagesContainer = document.getElementById('messages-container');
                        const wasScrolledToBottom = isScrolledToBottom(messagesContainer);
                        let html = '';

                        data.messages.forEach(message => {
                            const messageClass = message.is_sent ? 'sent' : 'received';
                            const leftOnReadClass = message.left_on_read ? 'left-on-read' : '';
                            
                            html += `
                                <div class="message ${messageClass} ${leftOnReadClass}">
                                    <div class="message-wrapper">
                                        <img src="${message.profile_picture}" alt="Profile" class="chat-avatar">
                                        <div class="message-content">
                                            ${message.message}
                                            <div class="message-time">${message.formatted_time}</div>
                                            ${message.show_seen ? `
                                                <div class="message-status">
                                                    ${message.seen_text}
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        messagesContainer.innerHTML = html;

                        if (wasScrolledToBottom) {
                            scrollToBottom(messagesContainer);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            const sellerEmail = document.getElementById('seller_email').value;
            const productName = document.getElementById('product_name').value;

            if (!message) return;

            const formData = new FormData();
            formData.append('message', message);
            formData.append('seller_email', sellerEmail);
            formData.append('product_name', productName);

            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(sellerEmail);
                    scrollToBottom(document.getElementById('messages-container'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message');
            });
        }

        function isScrolledToBottom(element) {
            return Math.abs(element.scrollHeight - element.scrollTop - element.clientHeight) < 50;
        }

        function scrollToBottom(element) {
            element.scrollTop = element.scrollHeight;
        }

        // Event listeners
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function closeModal() {
            const modal = document.getElementById('messaging-modal');
            modal.style.display = 'none';
            if (window.messageInterval) {
                clearInterval(window.messageInterval);
            }
        }

        // Close modal when clicking the close button
        document.querySelector('.close').addEventListener('click', closeModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('messaging-modal');
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>

    <!-- Modal for Messaging System -->
    <div id="messaging-modal" class="modal">
        <div class="modal-content">
            <div class="chat-header">
                <div id="chat-title"></div>
            </div>
            
            <div id="messages-container" class="messages-container">
                <!-- Messages will be loaded here -->
            </div>
            
            <div class="message-input-container">
                <input type="text" id="message-input" class="message-input" placeholder="Type a message...">
                <button onclick="sendMessage()" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>

            <input type="hidden" id="seller_email">
            <input type="hidden" id="product_name">
        </div>
    </div>
</body>
</html>
