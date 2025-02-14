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
            border-radius: 20px;
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
            right: -300px;
            top: 0;
            width: 300px;
            height: 100vh; /* Use viewport height */
            background: #ffffff;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1001;
            display: flex;
            flex-direction: column;
        }
    
        /* Reorganize the layout to have three main sections */
        .side-menu-top {
            flex-shrink: 0; /* Don't allow shrinking */
        }
    
        .side-menu-middle {
            flex: 1; /* Take up remaining space */
            overflow-y: auto; /* Allow scrolling if needed */
        }
    
        .side-menu-bottom {
            flex-shrink: 0; /* Don't allow shrinking */
            padding: 1rem;
            border-top: 1px solid #eee;
            background: white;
        }
    
        /* Bottom Action Buttons */
        .bottom-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 1rem;
        }
    
        .bottom-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
    
        .build-pc-btn {
            background: var(--primary-green);
        }
    
        .recycle-btn {
            background: var(--primary-green);
        }
    
        .bottom-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    
        .bottom-action-btn i {
            margin-right: 0.5rem;
            font-size: 1.2em;
        }
    
        .side-menu.show {
            right: 0;
        }
    
        /* Profile Section */
        .profile {
            position: relative;
            padding: 2rem 1.5rem;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            margin-top: 0;
        }
    
        .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            object-fit: cover;
            margin-bottom: 1rem;
        }
    
        /* Navigation Icons */
        .side-menu-nav {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            border-bottom: 1px solid #eee;
        }
    
        .side-menu-nav a {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: var(--text-dark);
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
    
        .side-menu-nav a:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }
    
        /* Categories Section */
        .side-menu-categories {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }
    
        .side-menu-category > a {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
    
        .side-menu-category > a:hover {
            background: #f8f9fa;
            color: var(--primary-blue);
        }
    
        .side-menu-subcategories {
            background: #f8f9fa;
            padding: 0.5rem 0;
        }
    
        .side-menu-subcategories a {
            padding: 0.8rem 2rem;
            display: block;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }
    
        .side-menu-subcategories a:hover {
            background: white;
            color: var(--primary-blue);
            padding-left: 2.5rem;
        }
    
        /* Close Button */
        .closebtn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            color: white;
            transition: all 0.3s ease;
            z-index: 1002; /* Ensure it's above other elements */
            text-decoration: none;
            border: none;
        }
    
        .closebtn:hover {
            background: rgba(0, 0, 0, 0.3);
            transform: rotate(90deg);
        }
    
        /* Special Icons */
        .build-pc-icon, .recycle-icon {
            background: var(--primary-green) !important;
            color: white !important;
        }
    
        .logout-icon {
            background: #fee2e2 !important;
            color: #dc2626 !important;
        }
    
        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                flex-direction: column; /* Stack items vertically */
                padding: 0.5rem 1rem;
            }

            .logo {
                width: 100%; /* Full width container */
                display: flex;
                justify-content: space-between; /* Space between logo and toggle */
                align-items: center;
                margin-bottom: 0.5rem; /* Add space before search bar */
            }

            .logo img {
                height: 40px;
            }

            .search-container {
                width: 100%;
                order: 2; /* Move search below logo */
                margin: 0.5rem 0;
            }

    .nav-toggle {
        margin-left: auto; /* Push toggle button to the right */
        margin-top: 0; /* Remove top margin */
    }

    .main-nav {
        display: none; /* Hide main navigation on mobile */
    }

    .categories-nav {
        display: none; /* Hide categories on mobile */
    }

    .side-menu {
        width: 100%; /* Full width for side menu */
        right: -100%; /* Start hidden */
    }

    .side-menu.show {
        right: 0; /* Show side menu */
    }

    .profile img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 0.5rem;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        object-fit: cover;
    }
    
    .profile .profile-email {
        display: block;
        font-weight: 500;
        color: var(--text-dark);
        margin-top: 0.5rem;
    }

    .profile {
        padding: 2rem 1rem;
        text-align: center;
        background: var(--hover-blue);
        margin-bottom: 1rem;
    }
    .side-menu ul {
        padding: 0; /* Remove padding */
    }
    .search-container {
        width: 100%; /* Full width for search bar */
        margin-top: 1rem; /* Add margin for spacing */
    }
    .nav-toggle {
        display: inline-block;
        position:right; /* Show the toggle button on mobile */
        margin-top: 0.5rem; /* Add some space above the toggle */
    }
}
    
        /* Remove bullet points from all lists */
        ul {
            list-style: none;
        }
    
        /* Specifically for side menu */
        .side-menu ul {
            list-style: none;
            padding: 0;
        }
    
        .side-menu ul li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
        }
    
        .side-menu ul li a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
        }
    
        /* For subcategories */
        .subcategories-list {
            list-style: none;
        }

        .profile-item {
            display: flex;
            align-items: center;
            gap: 0.5rem; /* Space between image and text */
        }

        .profile-pic img {
            width: 25px; /* Adjust size as needed */
            height: 25px; /* Adjust size as needed */
            border-radius: 50%; /* Circular image */
            object-fit: cover; /* Ensure the image covers the area */
        }

        .profile-email {
            color: var(--text-dark); /* Match the text color */
            font-weight: 500; /* Adjust font weight */
        }

        /* Side Menu Categories Styles */
        .side-menu-categories {
            border-top: 1px solid #eee;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .side-menu-category {
            position: relative;
        }

        .side-menu-category > a {
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .side-menu-category > a:after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            transition: transform 0.3s ease;
        }

        .side-menu-category > a.active:after {
            transform: rotate(180deg);
        }

        .side-menu-subcategories {
            display: none;
            background: #f8f9fa;
            padding-left: 1.5rem;
        }

        .side-menu-subcategories.show {
            display: block;
        }

        .side-menu-subcategories a {
            padding: 0.6rem 1.5rem;
            display: block;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .side-menu-subcategories a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
        }

        /* Side Menu Layout */
        .side-menu-content {
            display: flex;
            height: calc(100% - 80px); /* Adjust based on profile section height */
        }

        .side-menu-categories {
            flex: 1;
            border-right: 1px solid #eee;
            overflow-y: auto;
            padding-bottom: 2rem;
        }

        /* Side Menu Navigation Icons */
        .side-menu-nav {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .side-menu-nav a {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.5rem 0;
            border-radius: 50%;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .side-menu-nav a:hover {
            background: var(--hover-blue);
            color: var(--primary-blue);
            transform: scale(1.1);
        }

        .side-menu-nav a i {
            font-size: 1.2rem;
        }

        .side-menu-nav .build-pc-icon {
            background: var(--primary-green);
            color: white;
        }

        .side-menu-nav .recycle-icon {
            background: var(--primary-green);
            color: white;
        }

        .side-menu-nav .logout-icon {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .side-menu-content {
                flex-direction: column-reverse;
            }

            .side-menu-nav {
                width: 100%;
                flex-direction: row;
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0.5rem;
            }

            .side-menu-categories {
                border-right: none;
                border-top: 1px solid #eee;
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
            <input type="text" name="query" class="search-input" placeholder="Search products...">
            <button type="submit" class="new-search-button">Search</button>
        </form>
    </div>
   
</header>

   
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
                     echo htmlspecialchars($user['profile_picture']) ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" alt="Profile Picture">
                    <span class="profile-email"><?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?></span>
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
        <li><a href="buildyourpc.php" style="background-color: #218838;"><i class="fas fa-desktop fa-sm"></i> Build Your PC</a></li>
        <li><a href="recycle.php" style="background-color: #218838;color:white;font-weight: bold;"><i class="fa fa-recycle"style="font-size:22px"></i> Recycle</a></li>
    </ul>
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
                    <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" alt="Profile Picture">
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
    <script>
        function toggleMenu() {
            const sideMenu = document.getElementById('side-menu');
            sideMenu.classList.toggle('show');
        }

        function closeMenu() {
            const sideMenu = document.getElementById('side-menu');
            sideMenu.classList.remove('show');
        }

        function updateCartCount() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartCountElements = document.querySelectorAll('#cart-count');
                    cartCountElements.forEach(element => {
                        element.textContent = data.cartCount;
                    });
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }

        // Update cart count immediately and then every 5 seconds
        updateCartCount();
        setInterval(updateCartCount, 5000);

        function toggleSubcategories(categoryId) {
            const subcategoriesDiv = document.getElementById(`subcategories-${categoryId}`);
            const categoryLink = subcategoriesDiv.previousElementSibling;
            
            // Toggle the show class on the subcategories
            subcategoriesDiv.classList.toggle('show');
            // Toggle the active class on the category link
            categoryLink.classList.toggle('active');
        }
    </script>
</body>
</html>