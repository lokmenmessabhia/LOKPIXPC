<?php
include 'db_connect.php'; // Ensure this path is correct

if (isset($_SESSION['userid'])) {
    $user_id = $_SESSION['userid'];
} else {
    $user_id = null;
}

// Check if the user is an admin
$isAdmin = false; // Default to false
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $isAdmin = $stmt->fetchColumn() > 0; // Set true if email exists in the admins table
    } catch (PDOException $e) {
        echo "Error: Unable to verify admin status. " . $e->getMessage();
    }
}

// Fetch categories from the database
try {
    $stmt = $pdo->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: Unable to fetch categories. " . $e->getMessage();
    $categories = []; // Initialize as empty array in case of error
}

try {
    $stmt = $pdo->prepare("SELECT email, created_at, profile_picture, phone FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

try {
    // Check if the user is logged in by verifying the session variable
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && isset($user['profile_picture']) && !empty($user['profile_picture'])) {
            $profilePicture = $user['profile_picture'];
        } else {
            $profilePicture = 'https://i.top4top.io/p_3273sk4691.jpg'; // Default image URL
        }
    } else {
        $profilePicture = 'https://i.top4top.io/p_3273sk4691.jpg'; // Default image URL
    }
} catch (PDOException $e) {
    echo "Error: Unable to fetch profile picture. " . $e->getMessage();
    $profilePicture = 'https://i.top4top.io/p_3273sk4691.jpg'; // Default image URL in case of error
}

// Assuming you have a session variable for the cart
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Add this code after the initial user checks
$emailVerified = false; // Default to false
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $emailVerified = (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: Unable to verify email status. " . $e->getMessage();
    }
}

// Get unread message count
$unreadCount = 0;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE receiver_email = ? 
            AND read_status = 0
        ");
        $stmt->execute([$_SESSION['email']]);
        $unreadCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Handle error silently
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="logo (1).png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset and Variables */
        :root {
            --primary-blue: #0275d8;
            --primary-blue-dark: #025aa5;
            --primary-green: #218838;
            --hover-blue: #e7f1ff;
            --text-dark: #2c3e50;
            --text-light: #ffffff;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            --border-radius: 8px;
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
            text-decoration: none;
        }
    
        /* Global link styles */
        a {
            text-decoration: none;
        }

        /* Header specific styles */
        header a,
        .main-nav a,
        .categories-nav a,
        .side-menu a,
        .profile-pic,
        .messages-link,
        .nav-link,
        .bottom-action-btn,
        .side-menu-category a,
        .side-menu-subcategories a,
        .messages-nav-link,
        .contact-link,
        .logout-icon {
            text-decoration: none !important;
        }

        /* Remove any inline text-decoration styles */
        [style*="text-decoration"] {
            text-decoration: none !important;
        }
    
        /* Header */
        header {
            background: white;
            padding: 0.5rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Header layout adjustments */
        .header-top {
            display: grid;
            grid-template-columns: auto 1fr auto;
            grid-template-areas: "logo search toggle";
            gap: 1rem;
            align-items: center;
            padding: 0.8rem 0;
        }

        /* Logo and toggle container */
        .logo {
            grid-area: logo;
            justify-self: start;
        }

        .logo img {
            height: 35px;
            transition: transform 0.2s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .nav-toggle {
            grid-area: toggle;
            justify-self: end;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            z-index: 100; /* Ensure it's above other elements */
        }

        .nav-toggle:hover {
            background: var(--hover-blue);
            transform: scale(1.05);
        }

        .nav-toggle i {
            font-size: 1.2rem;
            color: var(--text-dark);
        }

        /* Search container adjustments */
        .search-container {
            grid-area: search;
            width: 100%;
            margin: 0;
        }
    
        .new-search-form {
            display: flex;
            align-items: center;
            background: #ffffff;
            border-radius: 50px;
            border: 2px solid #eef1f6;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
    
        .new-search-form:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(2, 117, 216, 0.15);
            transform: translateY(-1px);
        }
    
        .search-input {
            flex-grow: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            font-size: 0.95rem;
            color: #2d3748;
            outline: none;
            transition: all 0.3s ease;
        }
    
        .search-input::placeholder {
            color: #a0aec0;
            font-weight: 400;
        }
    
        .new-search-button {
            padding: 12px 24px;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .new-search-button:hover {
            background: var(--primary-blue-dark);
            transform: translateX(-2px);
        }

        .new-search-button i {
            font-size: 0.9rem;
        }

        /* Add a subtle gradient animation on focus */
        .new-search-form:focus-within::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(
                90deg,
                rgba(2, 117, 216, 0.1),
                rgba(2, 117, 216, 0.05),
                rgba(2, 117, 216, 0.1)
            );
            z-index: -1;
            animation: gradient 2s ease infinite;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
    
        /* Navigation */
        .main-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .main-nav li a {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            color: var(--text-dark);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }

        .main-nav li a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
        }

        /* Special Buttons */
        .build-pc-btn,
        .recycle-btn {
            background: var(--primary-green) !important;
            color: white !important;
        }
    
        .build-pc-btn:hover,
        .recycle-btn:hover {
            background: #1e7e34 !important;
            transform: translateY(-1px);
        }
    
        /* Profile Section */
        .profile-item .profile-pic {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            background: var(--hover-blue);
            border-radius: var(--border-radius);
        }

        .profile-pic img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Messages Badge */
        .messages-item .messages-link {
            position: relative;
            padding: 0.4rem 0.8rem;
        }

        .messages-unread-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        /* Categories Navigation */
        .categories-nav {
            display: flex;
            gap: 0.8rem;
            padding: 0.5rem 0;
            margin: 0;
            list-style: none;
            border-top: 1px solid #eee;
        }

        .category-item > a {
            color: var(--text-dark);
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }

        .category-item > a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            header {
                padding: 0.5rem 1rem;
            }
            
            .main-nav {
                gap: 0.5rem;
            }
            
            /* Adjust header layout for mobile */
            .header-top {
                grid-template-columns: auto 1fr auto;
                grid-template-areas: "toggle logo search";
            }
            
            /* Move toggle button to the left on mobile */
            .nav-toggle {
                grid-area: toggle;
                justify-self: start;
            }
            
            /* Center logo on mobile */
            .logo {
                grid-area: logo;
                justify-self: center;
            }
            
            /* Move search below on smaller screens */
            @media (max-width: 640px) {
                .header-top {
                    grid-template-columns: auto 1fr auto;
                    grid-template-areas: 
                        "toggle logo logo"
                        "search search search";
                    row-gap: 0.8rem;
                }
                
                .search-container {
                    grid-area: search;
                    width: 100%;
                }
            }
        }

        @media (min-width: 1024px) {
            .nav-toggle {
                display: flex; /* Show on all devices */
            }
            
            /* Side menu should still be available on desktop */
            .side-menu {
                display: block;
            }
        }

        /* Side Menu Styles - Updated for all devices */
        .side-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: min(400px, 100%); /* Use the smaller of 400px or 100% viewport width */
            height: 100vh;
            background: #ffffff;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            border-left: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 1002;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .side-menu.show {
            right: 0;
        }

        /* Close Button */
        .closebtn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            z-index: 2;
        }

        /* Side Menu Sections */
        .side-menu-top {
            padding: 20px;
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-bottom: 1px solid #eef1f6;
        }

        .side-menu-top .profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 15px;
        }

        .side-menu-top .profile img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-blue);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Navigation Icons */
        .side-menu-nav {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            padding: 15px;
            background: #ffffff;
        }

        .side-menu-nav a {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a5568;
            font-size: 1.1rem;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            aspect-ratio: 1;
        }

        .side-menu-nav a:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }

        /* Categories Section */
        .side-menu-middle {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 80px; /* Add padding to ensure visibility of last items */
        }

        .side-menu-categories {
            padding: 0;
        }

        .side-menu-category {
            position: relative;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .side-menu-category > a {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 500;
            color: #2d3748;
            transition: all 0.3s ease;
        }

        .side-menu-category > a:after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            transition: transform 0.3s ease;
        }

        .side-menu-category > a:hover {
            background: #f7fafc;
            padding-left: 25px;
        }

        /* Subcategories */
        .side-menu-subcategories {
            display: none;
            background: #f8f9fa;
            padding: 0;
        }

        .side-menu-subcategories a {
            padding: 12px 30px;
            color: #4a5568;
            display: block;
            border-top: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .side-menu-subcategories a:hover {
            background: #edf2f7;
            padding-left: 35px;
            color: var(--primary-blue);
        }

        /* Bottom Actions */
        .side-menu-bottom {
            padding: 15px;
            background: #ffffff;
            border-top: 1px solid #eef1f6;
        }

        .bottom-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .bottom-action-btn {
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .bottom-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Overlay */
        .menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .menu-overlay.show {
            display: block;
        }

        /* Messages Badge in Side Menu */
        .messages-nav-link {
            position: relative;
        }

        .messages-nav-link .messages-unread-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        /* Close button styles */
        .close-menu-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1003;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .close-menu-btn i {
            font-size: 1.2rem;
            color: #4a5568;
        }

        .close-menu-btn:hover {
            background: var(--hover-blue);
            transform: rotate(90deg);
        }

        /* Media Queries */
        @media (max-width: 480px) {
            .side-menu {
                width: 100%;
            }
            
            .side-menu-nav {
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
                padding: 10px;
            }
            
            .side-menu-nav a {
                font-size: 0.9rem;
                padding: 8px;
                border-radius: 8px;
                aspect-ratio: auto; /* Remove the square aspect ratio */
                height: 40px; /* Set a fixed height instead */
            }
            
            .side-menu-top {
                padding: 15px;
            }
            
            .side-menu-top .profile {
                padding-bottom: 10px;
            }
            
            .side-menu-top .profile img {
                width: 50px;
                height: 50px;
                border-width: 2px;
            }
            
            .side-menu-top .profile h2 {
                font-size: 1rem !important;
                margin-top: 0.5rem !important;
            }
            
            .side-menu-top .profile p {
                font-size: 0.75rem !important;
                margin-top: 0.1rem !important;
            }
            
            .side-menu-category > a {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .side-menu-subcategories a {
                padding: 8px 20px;
                font-size: 0.85rem;
            }
            
            .close-menu-btn {
                top: 8px;
                right: 8px;
                width: 32px;
                height: 32px;
            }
            
            .close-menu-btn i {
                font-size: 1rem;
            }
        }

        @media (min-width: 481px) and (max-width: 768px) {
            .side-menu {
                width: 320px;
            }
            
            .side-menu-nav {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                padding: 12px;
            }
            
            .side-menu-nav a {
                font-size: 0.95rem;
                padding: 10px;
                aspect-ratio: auto; /* Remove the square aspect ratio */
                height: 45px; /* Set a fixed height instead */
            }
            
            .side-menu-top {
                padding: 18px;
            }
            
            .side-menu-top .profile img {
                width: 60px;
                height: 60px;
            }
            
            .side-menu-top .profile h2 {
                font-size: 1.1rem !important;
                margin-top: 0.6rem !important;
            }
            
            .side-menu-top .profile p {
                font-size: 0.8rem !important;
            }
            
            .side-menu-category > a {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .side-menu-subcategories a {
                padding: 10px 22px;
                font-size: 0.85rem;
            }
        }

        @media (min-width: 1024px) {
            /* Hide side menu on desktop by default */
            .side-menu {
                display: none;
            }
            
            /* But allow it to be shown when toggled */
            .side-menu.show {
                display: flex;
            }
        }

        /* Cart Badge */
        .cart-link {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['loggedin']) && !$emailVerified): ?>
    <div style="background-color: #ff9800; color: white; text-align: center; padding: 10px; width: 100%;">
        Your email is not verified. Please <a href="profile.php" style="color: white; text-decoration: underline;">verify your email</a> to ensure full access to all features.
    </div>
<?php endif; ?>

<header>
    <div class="header-top">
        <div class="logo">
            <a href="index.php"><img src="logo (1) text.png" alt="EcoTech Logo"></a>
        </div>
        
        <div class="search-container">
            <form action="search.php" method="GET" class="new-search-form">
                <input type="text" name="query" class="search-input" placeholder="Search products...">
                <button type="submit" class="new-search-button">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div class="nav-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>


<div id="side-menu" class="side-menu">
    <button class="close-menu-btn" onclick="closeMenu()">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="side-menu-top">
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <div class="profile">
                <img src="<?php 
                echo isset($user['profile_picture']) && !empty($user['profile_picture'])
                    ? 'uploads/profiles/' . htmlspecialchars($user['profile_picture'])
                    : 'https://i.top4top.io/p_3273sk4691.jpg'; 
                ?>" alt="Profile Picture">
                <h2 style="font-size: 1.2rem; margin-top: 0.8rem; color: #2d3748;">
                    Welcome, <?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?>!
                </h2>
                <p style="font-size: 0.85rem; color: #718096; margin-top: 0.2rem;">
                    <?php echo htmlspecialchars($_SESSION['email']); ?>
                </p>
            </div>
        <?php else: ?>
            <div class="profile">
                <img src="https://i.top4top.io/p_3273sk4691.jpg" alt="Guest">
                <h2 style="font-size: 1.2rem; margin-top: 0.8rem; color: #2d3748;">Welcome, Guest!</h2>
                <p style="font-size: 0.85rem; color: #718096; margin-top: 0.2rem;">
                    <a href="login.php" style="color: var(--primary-blue); font-weight: 600;">Login</a> or 
                    <a href="sign-up.php" style="color: var(--primary-blue); font-weight: 600;">Sign Up</a>
                </p>
            </div>
        <?php endif; ?>

        <div class="side-menu-nav">
            <a href="index.php" title="Home"><i class="fas fa-home"></i></a>
            <a href="marketplace.php" title="Marketplace"><i class="fas fa-store"></i></a>
            <a href="cart.php" title="Cart" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="contact.php" class="contact-link" title="Contact"><i class="fas fa-envelope"></i></a>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <a href="profile.php" title="Profile"><i class="fas fa-user"></i></a>
                <a href="inbox.php" class="messages-nav-link" title="Messages">
                    <i class="fab fa-facebook-messenger"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="messages-unread-count"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($isAdmin): ?>
                    <a href="dashboard.php" title="Admin Dashboard"><i class="fas fa-tachometer-alt"></i></a>
                <?php endif; ?>
                <a href="logout.php" class="logout-icon" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
            <?php else: ?>
                <a href="login.php" title="Login"><i class="fas fa-sign-in-alt"></i></a>
                <a href="sign-up.php" title="Sign Up"><i class="fas fa-user-plus"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="side-menu-middle">
        <div class="side-menu-categories">
            <?php foreach ($categories as $category): ?>
                <div class="side-menu-category">
                    <a href="javascript:void(0)" onclick="toggleSubcategories(<?php echo $category['id']; ?>)">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                    <div id="subcategories-<?php echo $category['id']; ?>" class="side-menu-subcategories">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
                        $stmt->execute([$category['id']]);
                        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($subcategories as $subcategory): ?>
                            <a href="subcategory.php?id=<?php echo htmlspecialchars($subcategory['id']); ?>">
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="side-menu-bottom">
        <div class="bottom-actions">
            <a href="buildyourpc.php" class="bottom-action-btn build-pc-btn">
                <i class="fas fa-desktop"></i>
                Build PC
            </a>
            <a href="recycle.php" class="bottom-action-btn recycle-btn">
                <i class="fa fa-recycle"></i>
                Recycle
            </a>
        </div>
    </div>
</div>

<!-- Place the overlay div here, after the side menu -->
<div class="menu-overlay" onclick="closeMenu()"></div>

<script>
function toggleMenu() {
    const sideMenu = document.getElementById('side-menu');
    const overlay = document.querySelector('.menu-overlay');
    const body = document.body;
    
    if (sideMenu.classList.contains('show')) {
        closeMenu();
    } else {
        sideMenu.classList.add('show');
        overlay.classList.add('show');
        body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
    }
}

function closeMenu() {
    const sideMenu = document.getElementById('side-menu');
    const overlay = document.querySelector('.menu-overlay');
    const body = document.body;
    
    sideMenu.classList.remove('show');
    overlay.classList.remove('show');
    body.style.overflow = ''; // Restore scrolling
}

function toggleSubcategories(categoryId) {
    const subcategories = document.getElementById('subcategories-' + categoryId);
    const categoryLink = subcategories.previousElementSibling;
    
    // Toggle the arrow icon
    if (subcategories.style.display === 'block') {
        subcategories.style.display = 'none';
        categoryLink.style.fontWeight = '500';
        categoryLink.style.color = '#2d3748';
    } else {
        // Close all other subcategories first
        const allSubcategories = document.getElementsByClassName('side-menu-subcategories');
        for (let item of allSubcategories) {
            if (item.id !== 'subcategories-' + categoryId) {
                item.style.display = 'none';
                if (item.previousElementSibling) {
                    item.previousElementSibling.style.fontWeight = '500';
                    item.previousElementSibling.style.color = '#2d3748';
                }
            }
        }
        
        // Open this subcategory
        subcategories.style.display = 'block';
        categoryLink.style.fontWeight = '600';
        categoryLink.style.color = 'var(--primary-blue)';
    }
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const sideMenu = document.getElementById('side-menu');
    const menuToggle = document.querySelector('.nav-toggle');
    
    if (sideMenu.classList.contains('show') && 
        !sideMenu.contains(event.target) && 
        !menuToggle.contains(event.target)) {
        closeMenu();
    }
});

// Add swipe gesture support for mobile
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', e => {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', e => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const sideMenu = document.getElementById('side-menu');
    
    // Swipe left to close menu (if open)
    if (sideMenu.classList.contains('show') && touchEndX < touchStartX - 50) {
        closeMenu();
    }
    
    // Swipe right from edge to open menu (if closed)
    if (!sideMenu.classList.contains('show') && touchStartX < 30 && touchEndX > touchStartX + 50) {
        toggleMenu();
    }
}
</script>
<script>
    // Register Service Worker and handle push notifications
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('/lokpixpc/service-worker.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
                
                // Request notification permission
                return Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        return registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: 'YOUR_PUBLIC_VAPID_KEY_HERE' // You'll need to generate this
                        });
                    }
                });
            })
            .then(function(subscription) {
                if (subscription) {
                    // Send subscription to server
                    fetch('/lokpixpc/notification-handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            subscription: subscription.toJSON()
                        })
                    });
                }
            })
            .catch(function(error) {
                console.log('Service Worker registration failed:', error);
            });

        // Check for new messages periodically
        setInterval(function() {
            fetch('/lokpixpc/get_total_unread.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.unread_count > 0) {
                        // Update any UI elements showing unread count
                        const unreadBadge = document.querySelector('.unread-count');
                        if (unreadBadge) {
                            unreadBadge.textContent = data.unread_count;
                            unreadBadge.style.display = 'inline';
                        }
                    }
                });
        }, 30000); // Check every 30 seconds
    }
</script>
<script>
    // Request notification permission when page loads
    if ('Notification' in window) {
        Notification.requestPermission();
    }

    // Function to show notification
    function showNotification(title, message) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: 'logo (1) text.png'
            });
        }
    }

    // Check for new messages
    let lastMessageCount = 0;
    function checkNewMessages() {
        fetch('get_total_unread.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentCount = data.unread_count;
                    // If we have more messages than before, show notification
                    if (currentCount > lastMessageCount && lastMessageCount !== 0) {
                        const newMessages = currentCount - lastMessageCount;
                        showNotification(
                            'New Message - LokPixPC',
                            `You have ${newMessages} new message${newMessages > 1 ? 's' : ''}`
                        );
                    }
                    lastMessageCount = currentCount;

                    // Update UI
                    const unreadBadge = document.querySelector('.unread-count');
                    if (unreadBadge) {
                        if (currentCount > 0) {
                            unreadBadge.textContent = currentCount;
                            unreadBadge.style.display = 'inline';
                        } else {
                            unreadBadge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Check for new messages every 5 seconds
    setInterval(checkNewMessages, 5000);

    // Initial check
    checkNewMessages();
</script>
<script>
    // Check and update notification permission status
    function updateNotificationStatus() {
        const statusElement = document.getElementById('notification-status');
        if (!('Notification' in window)) {
            statusElement.textContent = 'Not Supported';
            statusElement.className = 'notification-status disabled';
            return;
        }

        switch(Notification.permission) {
            case 'granted':
                statusElement.textContent = 'Enabled';
                statusElement.className = 'notification-status enabled';
                break;
            case 'denied':
                statusElement.textContent = 'Blocked';
                statusElement.className = 'notification-status disabled';
                break;
            default:
                statusElement.textContent = 'Disabled';
                statusElement.className = 'notification-status disabled';
        }
    }

    // Toggle notifications
    function toggleNotifications() {
        if (!('Notification' in window)) {
            alert('This browser does not support notifications');
            return;
        }

        if (Notification.permission === 'denied') {
            alert('You have blocked notifications. Please enable them in your browser settings.');
            return;
        }

        if (Notification.permission === 'granted') {
            alert('Notifications are already enabled. To disable them, use your browser settings.');
            return;
        }

        Notification.requestPermission()
            .then(function(permission) {
                updateNotificationStatus();
                if (permission === 'granted') {
                    new Notification('Notifications Enabled', {
                        body: 'You will now receive notifications for new messages',
                        icon: 'logo (1) text.png'
                    });
                }
            });
    }

    // Update status when page loads
    document.addEventListener('DOMContentLoaded', updateNotificationStatus);
</script>
<script>
    // Function to update cart count
    function updateCartCount() {
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElements = document.querySelectorAll('.cart-count');
                    cartCountElements.forEach(element => {
                        if (data.count > 0) {
                            element.textContent = data.count;
                            element.style.display = 'inline';
                        } else {
                            element.style.display = 'none';
                        }
                    });
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
    }

    // Update cart count every 5 seconds
    setInterval(updateCartCount, 5000);
    
    // Initial update
    document.addEventListener('DOMContentLoaded', updateCartCount);
</script>
</body>
</html>