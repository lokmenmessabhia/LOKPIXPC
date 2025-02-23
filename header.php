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
    $stmt = $pdo->prepare("SELECT email , created_at, profile_picture, phone FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}


try {
    // Check if the user is logged in by verifying the session variable
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id']; // Assuming the user ID is stored in the session
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && isset($user['profile_picture']) && !empty($user['profile_picture'])) {
            $profilePicture = $user['profile_picture'];
        } else {
            $profilePicture = 'https://i.top4top.io/p_3273sk4691.jpg'; // Default image URL
        }
    } else {
        // If not logged in, use the default profile picture
        $profilePicture = 'https://i.top4top.io/p_3273sk4691.jpg'; // Default image URL
    }
} catch (PDOException $e) {
    echo "Error: Unable to fetch profile picture. " . $e->getMessage();
    $profilePicture = 'https://i.top4top.io/p_3273sk4691.jpg'; // Default image URL in case of error
}

// Assuming you have a session variable for the cart
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; // Count items in the cart

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

// Get unread message count
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread 
            FROM messages 
            WHERE receiver_email = ? 
            AND read_status = 0
        ");
        $stmt->execute([$_SESSION['email']]);
        $unread = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
    } catch (PDOException $e) {
        $unread = 0;
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
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
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
            text-decoration: none;
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
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
            box-shadow: var(--shadow);
        }

        .main-nav li a:hover {
            background: var(--hover-blue);
            transform: translateY(-2px);
        }

        /* Icons */
        .main-nav li a i, 
        .side-menu ul li a i {
            margin-right: 8px;
            font-size: 0.9em;
        }

        .nav-toggle {
            font-size: 1.5rem;
            cursor: pointer;
            display: none; /* Initially hidden, shown in mobile view */
            margin-left: auto; /* Push it to the right */
        }
    
        /* Build PC Button */
        a[href="buildyourpc.php"] {
            background: var(--primary-green) !important;
            color: var(--text-light) !important;
            font-weight: 600 !important;
            padding: 0.8rem 1.5rem !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    
        a[href="buildyourpc.php"]:hover {
            background: #1a6e2e !important;
            transform: translateY(-2px);
        }
    
        /* Categories */
        .categories-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            list-style: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            flex-wrap: wrap;
        }
    
        .category-item {
            position: relative;
            margin-bottom: 1rem;
        }
    
        .category-item:hover .subcategories-list {
            display: block;
        }
    
        .category-item > a {
            padding: 0.6rem 1.2rem;
            color: var(--primary-blue);
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: block;
            background: white;
            box-shadow: var(--shadow);
            
        }
    
        .category-item > a:hover {
            background: var(--hover-blue);
        }
    
        .subcategories-list {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 0.5rem 0;
            display: none;
            z-index: 100;
            margin-top: 0.1rem;
        }
    
        .subcategories-list li a {
            padding: 0.6rem 1.2rem;
            color: var(--text-dark);
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
        }
    
        .subcategories-list li a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
        }
    
        /* Side Menu Updated Styles */
        .side-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background: #ffffff;
            transition: 0.3s ease-in-out;
            z-index: 1001;
            overflow-y: auto;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .side-menu.active {
            right: 0;
            text-decoration: none;
        }

        .closebtn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--text-dark);
            font-size: 2rem;
            cursor: pointer;
            transition: transform 0.3s ease;
            z-index: 2;
        }

        .closebtn:hover {
            transform: rotate(90deg);
            color: var(--primary-blue);
        }

        /* Profile Section */
        .side-menu-top {
            padding: 2rem 1.5rem;
            background: var(--hover-blue);
            border-bottom: 1px solid #eee;
            
        }

        .profile {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .profile img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid var(--primary-blue);
            padding: 3px;
            margin-bottom: 1rem;
            background: white;
        }

        .profile h2 {
            color: var(--text-dark);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        /* Quick Navigation Icons */
        .side-menu-nav {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            padding: 1rem;
            background: white;
            text-decoration: none;
        }

        .side-menu-nav a {
            color: var(--text-dark);
            text-align: center;
            padding: 0.8rem;
            border-radius: 20px;
            background: var(--hover-blue);
            transition: all 0.3s ease;
        }

        .side-menu-nav a:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px);
        }

        .side-menu-nav .messages-nav-link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            background: var(--hover-blue);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .side-menu-nav .messages-nav-link:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px);
        }

        .side-menu-nav .messages-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .side-menu-nav .messages-unread-count {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #e74c3c;
            color: white;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .side-menu-nav .contact-link {
            color: var(--text-dark);
            background: var(--hover-blue);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .side-menu-nav .contact-link:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px);
        }

        /* Categories Section */
        .side-menu-middle {
            padding: 1.5rem;
            background: white;
            text-decoration: none;
        }

        .side-menu-categories {
            list-style: none;
            padding: 0;
        }

        .side-menu-category {
            margin-bottom: 0.5rem;
        }

        .side-menu-category > a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1.2rem;
            color: var(--text-dark);
            background: var(--hover-blue);
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
        }

        .side-menu-category > a::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            transition: transform 0.3s ease;
        }

        .side-menu-category > a.active {
            background: var(--primary-blue);
            color: white;
        }

        .side-menu-category > a.active::after {
            transform: rotate(180deg);
        }

        .side-menu-category > a:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateX(5px);
        }

        .side-menu-subcategories {
            display: none;
            padding: 0.5rem 0 0.5rem 1.5rem;
            margin: 0.3rem 0;
            border-left: 2px solid var(--hover-blue);
        }

        .side-menu-subcategories a {
            display: block;
            padding: 0.6rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .side-menu-subcategories a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
            transform: translateX(5px);
        }

        @media (max-width: 768px) {
            .side-menu-middle {
                padding: 0.8rem;
            }

            .side-menu-category > a {
                padding: 0.7rem 1rem;
            }

            .side-menu-subcategories {
                padding-left: 1rem;
            }

            .side-menu-subcategories a {
                padding: 0.5rem 0.8rem;
            }
        }

        /* Bottom Actions */
        .side-menu-bottom {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            background: white;
        }

        .bottom-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .bottom-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
        }

        .build-pc-btn {
            background: var(--primary-blue);
        }

        .recycle-btn {
            background: var(--primary-green);
        }

        .bottom-action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        /* Messages Section */
        .messages-section {
            margin-top: 1rem;
            padding: 1rem;
            border-top: 1px solid #eee;
            background: white;
        }

        .messages-section .nav-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--hover-blue);
            padding: 0.8rem;
            border-radius: 8px;
            color: var(--text-dark);
        }

        .messages-section .nav-link:hover {
            background: var(--primary-blue);
            color: white;
        }

        .header-unread-count {
            background: #e74c3c;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .messages-item {
            position: relative;
        }

        .messages-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            border-radius: 30px;
            background: var(--hover-blue);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .messages-link:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }

        .messages-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .messages-text {
            font-weight: 500;
        }

        .messages-unread-count {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #e74c3c;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .messages-link {
                padding: 1rem;
                justify-content: center;
            }

            .messages-unread-count {
                top: -6px;
                right: -8px;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 0.5rem 1rem;
            }

            .logo img {
                height: 40px;
            }

            .nav-toggle {
                display: block;
                font-size: 24px;
                margin-left: 1rem;
                color: var(--text-dark);
            }

            .main-nav {
                display: none;
            }

            .categories-nav {
                display: none;
            }

            /* Side Menu Mobile Styles */
            .side-menu {
                width: 100%;
                right: -100%;
                padding-top: 60px;
            }

            .side-menu.show {
                right: 0;
                width: 100%;
            }

            .side-menu-nav {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.8rem;
                padding: 1rem;
            }

            .side-menu-nav a {
                width: 100%;
                height: auto;
                padding: 0.8rem;
                border-radius: 12px;
                background: var(--hover-blue);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .side-menu-nav a i {
                font-size: 1.2rem;
            }

            .side-menu-categories {
                padding: 1rem;
            }

            .side-menu-category > a {
                padding: 1rem;
                font-size: 1.1rem;
            }

            .side-menu-subcategories {
                position: static;
                width: 100%;
                margin-left: 1rem;
                background: transparent;
                box-shadow: none;
            }

            .side-menu-subcategories a {
                padding: 0.8rem 1rem;
                font-size: 1rem;
            }

            .bottom-actions {
                grid-template-columns: 1fr;
                gap: 0.8rem;
                padding: 1rem;
            }

            .bottom-action-btn {
                padding: 1rem;
                font-size: 1.1rem;
            }

            .closebtn {
                top: 1rem;
                right: 1rem;
                font-size: 28px;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--hover-blue);
                border-radius: 50%;
            }

            /* Search Bar Mobile */
            .search-container {
                width: 100%;
                margin: 0.5rem 0;
            }

            .new-search-form {
                width: 100%;
                max-width: none;
            }

            .search-input {
                width: 100%;
            }

            /* Profile Section Mobile */
            .profile {
                padding: 1.5rem 1rem;
            }

            .profile img {
                width: 70px;
                height: 70px;
            }

            .profile h2 {
                font-size: 1.2rem;
            }

            /* Messages Section Mobile */
            .messages-section {
                padding: 1rem;
            }

            .messages-section .nav-link {
                padding: 1rem;
                font-size: 1.1rem;
            }

            /* Fix for scrolling */
            body.menu-open {
                overflow: hidden;
            }
        }
        .profile-item {
            position: relative;
        }

        .profile-pic {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            background: var(--hover-blue);
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }

        .profile-pic img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--primary-blue);
            padding: 2px;
            background: white;
        }

        .profile-email {
            font-weight: 500;
        }

        .main-nav li a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            color: var(--primary-blue);
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
            box-shadow: var(--shadow);
        }

        .side-menu-nav a {
            color: var(--text-dark);
            text-align: center;
            padding: 0.8rem;
            border-radius: 20px;
            background: var(--hover-blue);
            transition: all 0.3s ease;
        }

        .messages-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            border-radius: 30px;
            background: var(--hover-blue);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .category-item > a {
            padding: 0.6rem 1.2rem;
            color: var(--primary-blue);
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: block;
            background: white;
            box-shadow: var(--shadow);
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .profile-pic {
                border-radius: 15px;
                padding: 0.5rem;
            }

            .main-nav li a,
            .messages-link,
            .category-item > a {
                border-radius: 15px;
            }

            .side-menu-nav a {
                border-radius: 12px;
            }
        }

        /* Notification Settings */
        .notification-settings {
            padding: 1rem;
            background: white;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .notification-status {
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .notification-status.enabled {
            background: var(--primary-green);
            color: white;
        }

        .notification-status.disabled {
            background: #e74c3c;
            color: white;
        }

        .notification-status:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .notification-settings {
                margin: 0.5rem;
            }
        }
        /* Extra Small Devices (under 468px) */
        @media (max-width: 467px) {
            header {
                padding: 0.5rem;
                flex-wrap: wrap;
            }

            .logo {
                width: 100%;
                justify-content: space-between;
                margin-bottom: 0.5rem;
            }

            .logo img {
                height: 35px;
            }

            .nav-toggle {
                font-size: 20px;
            }

            .search-container {
                width: 100%;
                margin: 0;
            }

            .new-search-form {
                display: flex;
                width: 100%;
            }

            .search-input {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .new-search-button {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            /* Side Menu Adjustments */
            .side-menu {
                width: 100%;
            }

            .side-menu-nav {
                grid-template-columns: repeat(3, 1fr);
                padding: 0.5rem;
                gap: 0.5rem;
            }

            .side-menu-nav a {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .side-menu-nav a i {
                font-size: 1rem;
            }

            .side-menu-category > a {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }

            .side-menu-subcategories {
                padding-left: 0.8rem;
            }

            .side-menu-subcategories a {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }

            /* Profile Section */
            .profile {
                padding: 1rem 0.5rem;
            }

            .profile img {
                width: 50px;
                height: 50px;
            }

            .profile h2 {
                font-size: 1rem;
            }

            /* Bottom Actions */
            .bottom-actions {
                padding: 0.5rem;
                gap: 0.5rem;
            }

            .bottom-action-btn {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            /* Messages Section */
            .messages-link {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .messages-unread-count {
                min-width: 16px;
                height: 16px;
                font-size: 0.7rem;
                top: -5px;
                right: -5px;
            }

            /* Main Navigation */
            .main-nav li a {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .category-item > a {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }

            /* Profile Item */
            .profile-pic {
                padding: 0.4rem 0.8rem;
            }

            .profile-pic img {
                width: 18px;
                height: 18px;
            }

            .profile-email {
                font-size: 0.85rem;
            }

            /* Close Button */
            .closebtn {
                top: 0.5rem;
                right: 0.5rem;
                font-size: 24px;
                width: 35px;
                height: 35px;
            }
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
        <div class="logo">
            <a href="index.php"><img src="logo (1) text.png" alt="EcoTech Logo"></a>
            <span class="nav-toggle" onclick="toggleMenu()">☰</span>
        </div>
        
    <div class="search-container">
        <form action="search.php" method="GET" class="new-search-form">
            <button type="submit" class="new-search-button"><i class="fas fa-search"></i></button> <!-- Search icon button -->
            <input type="text" name="query" class="search-input" placeholder=" " aria-label="Search products..."> <!-- Placeholder is now empty -->
        </form>
    </div>
</header>

<nav>
    <ul class="main-nav">
                <li><a href="index.php"><i class="fas fa-home fa-sm"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle fa-sm"></i> About</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope fa-sm"></i> Contact Us</a></li>
                
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li class="profile-item">
                        <a href="profile.php" class="profile-pic">
                            <img src="<?php 
                            echo isset($user['profile_picture']) && !empty($user['profile_picture']) 
                                ? 'uploads/profiles/' . htmlspecialchars($user['profile_picture'])
                                : 'https://i.top4top.io/p_3273sk4691.jpg'; 
                            ?>" alt="Profile Picture">
                            <span class="profile-email"><?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?></span>
                        </a>
                    </li>
                    <li>  <a href="marketplace.php" class="nav-link">
                            <i class="fas fa-store"></i> Marketplace
                        </a>
                    </li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart fa-sm"></i> Cart (<span id="cart-count"><?php echo $cartCount; ?></span>)</a></li>
                    <li class="messages-item">
                        <a href="inbox.php" class="messages-link">
                            <div class="messages-icon">
                                <i class="fab fa-facebook-messenger"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="messages-unread-count"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="messages-text">Messages</span>
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li>
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-user-shield"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="logout.php" class="nav-link" style="color: red;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt fa-sm"></i> Login</a></li>
                    <li><a href="sign-up.php"><i class="fas fa-user-plus fa-sm"></i> Sign Up</a></li>
                <?php endif; ?>
                <li><a href="buildyourpc.php" style="background-color: #218838;"><i class="fas fa-desktop fa-sm"></i> Build Your PC</a></li>
                <li><a href="recycle.php" style="background-color: #218838;color:white;font-weight: bold;"><i class="fa fa-recycle"style="font-size:22px"></i> Recycle</a></li>
            </ul>
        </div>
    </div>
</header>

<nav>
    <ul class="categories-nav">
        <?php foreach ($categories as $category): ?>
            <li class="category-item">
                <a class="nav-link <?php echo (isset($_GET['id']) && $_GET['id'] == $category['id']) ? 'active' : ''; ?>" 
                   href="category.php?id=<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
                <ul class="subcategories-list">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
                    $stmt->execute([$category['id']]);
                    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($subcategories as $subcategory): ?>
                        <li><a href="subcategory.php?id=<?php echo htmlspecialchars($subcategory['id']); ?>">
                            <?php echo htmlspecialchars($subcategory['name']); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

<div id="side-menu" class="side-menu">
    <span class="closebtn" onclick="closeMenu()">×</span>
    
    <div class="side-menu-top">
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <div class="profile">
                <img src="<?php 
                echo isset($user['profile_picture']) && !empty($user['profile_picture'])
                    ? 'uploads/profiles/' . htmlspecialchars($user['profile_picture'])
                    : 'https://i.top4top.io/p_3273sk4691.jpg'; 
                ?>" alt="Profile Picture">
                <h2 style="font-size: 1.2rem; margin-top: 0.5rem;color:green;">Welcome, <?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?>!</h2>
            </div>
        <?php else: ?>
            <div class="profile">
                <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Welcome!</h2>
                <p style="font-size: 0.9rem; opacity: 0.9;color:green;">Please login or sign up to access all features</p>
            </div>
        <?php endif; ?>

        <div class="side-menu-nav">
            <a href="index.php"><i class="fas fa-home"></i></a>
            <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
            <a href="contact.php" class="contact-link"><i class="fas fa-envelope-open-text"></i></a>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <a href="profile.php"><i class="fas fa-user"></i></a>
                <a href="inbox.php" class="messages-nav-link">
                    <div class="messages-icon">
                        <i class="fab fa-facebook-messenger"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="messages-unread-count"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php if ($isAdmin): ?>
                    <a href="dashboard.php" style="text-decoration:none"><i class="fas fa-tachometer-alt"></i></a>
                <?php endif; ?>
                <a href="logout.php" class="logout-icon" style="text-decoration:none"><i class="fas fa-sign-out-alt"></i></a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i></a>
                <a href="sign-up.php"><i class="fas fa-user-plus"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="side-menu-middle">
        <div class="side-menu-categories">
            <?php foreach ($categories as $category): ?>
                <div class="side-menu-category">
                    <a href="javascript:void(0)" onclick="toggleSubcategories(<?php echo $category['id']; ?>)">
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    </a>
                    <div id="subcategories-<?php echo $category['id']; ?>" class="side-menu-subcategories">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
                        $stmt->execute([$category['id']]);
                        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($subcategories as $subcategory): ?>
                            <a href="subcategory.php?id=<?php echo htmlspecialchars($subcategory['id']); ?>">
                                <span><?php echo htmlspecialchars($subcategory['name']); ?></span>
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
<script>
    function toggleMenu() {
        const sideMenu = document.getElementById('side-menu');
        const body = document.body;
        
        if (sideMenu.classList.contains('show')) {
            sideMenu.style.right = '-100%';
            sideMenu.classList.remove('show');
            body.classList.remove('menu-open');
        } else {
            sideMenu.style.right = '0';
            sideMenu.classList.add('show');
            body.classList.add('menu-open');
        }
    }

    function closeMenu() {
        const sideMenu = document.getElementById('side-menu');
        const body = document.body;
        sideMenu.style.right = '-100%';
        sideMenu.classList.remove('show');
        body.classList.remove('menu-open');
    }

    function toggleSubcategories(categoryId) {
        const subcategories = document.getElementById('subcategories-' + categoryId);
        const categoryLink = subcategories.previousElementSibling;
        
        if (subcategories.style.display === 'block') {
            subcategories.style.display = 'none';
            categoryLink.classList.remove('active');
        } else {
            // Close all other subcategories
            const allSubcategories = document.getElementsByClassName('side-menu-subcategories');
            const allCategoryLinks = document.querySelectorAll('.side-menu-category > a');
            
            for (let link of allCategoryLinks) {
                link.classList.remove('active');
            }
            
            for (let item of allSubcategories) {
                item.style.display = 'none';
            }
            
            subcategories.style.display = 'block';
            categoryLink.classList.add('active');
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
</body>
</html>