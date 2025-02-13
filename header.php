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
  
    <link rel="icon" type="image/x-icon" href="logo (2).png">
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
              flex-direction: row; /* Default to row for larger screens */
              align-items: center;
              justify-content: space-between; /* Space between logo and nav toggle */
            }
    
        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            flex-shrink: 0;
        }
    
        .logo img {
            height: 50px;
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
    
        /* Side Menu */
        .side-menu {
            position: fixed;
            right: -300px;
            top: 0;
            width: 300px;
            height: 100%;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1001;
        }
    
        .side-menu.show {
            right: 0;
        }
    
        .profile {
            padding: 2rem;
            text-align: center;
            background: var(--hover-blue);
        }
    
        .build-pc-button {
        background-color: #218838 !important;
        color: white !important;
        margin: 1rem;
        border-radius: 25px;
    }
    
    .build-pc-button:hover {
        background-color: #1a6e2e !important;
        color: white !important;
    }
        .closebtn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--hover-blue);
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
    
        .closebtn:hover {
            background: var(--primary-blue);
            color: var(--text-light);
        }
    
        /* Responsive Design */
       /* Responsive Design */
@media (max-width: 768px) {
    header {
        flex-direction: column; /* Stack items vertically on mobile */
        align-items: center; /* Center items horizontally */
    }

    .search-container {
        width: 100%; /* Full width for search bar */
        margin: 1rem 0; /* Add margin for spacing */
    }

    .new-search-form {
        width: 100%; /* Full width for search form */
    }

    .main-nav {
        display: none; /* Hide main navigation on mobile */
    }

    .categories-nav {
        display: none; /* Hide categories on mobile */
    }

    .nav-toggle {
        display: block; /* Show the toggle button */
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
        display: block;
        position:left; /* Show the toggle button on mobile */
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
        <a href="index.php"><img src="logo (2).png" alt="Logo"></a>
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
    
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <div class="profile">
            <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'https://i.top4top.io/p_3273sk4691.jpg'; ?>" alt="Profile Picture">
            <span class="profile-email"><?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?></span>
        </div>
    <?php endif; ?>

    <ul>
        <li><a href="index.php"><i class="fas fa-home fa-sm"></i> Home</a></li>
        <li><a href="about.php"><i class="fas fa-info-circle fa-sm"></i> About</a></li>
        <li><a href="contact.php"><i class="fas fa-envelope fa-sm"></i> Contact Us</a></li>
        
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <li><a href="profile.php"><i class="fas fa-user fa-sm"></i> Profile</a></li>
           
            <li><a href="cart.php"><i class="fas fa-shopping-cart fa-sm"></i> Cart (<span class="cart-count"><?php echo $cartCount; ?></span>)</a></li>
            <li><a href="logout.php" style="color:red;"><i class="fas fa-sign-out-alt fa-sm"></i> Logout</a></li>
        <?php else: ?>
            <li><a href="login.php"><i class="fas fa-sign-in-alt fa-sm"></i> Login</a></li>
            <li><a href="sign-up.php"><i class="fas fa-user-plus fa-sm"></i> Sign Up</a></li>
        <?php endif; ?>
        
        <li><a href="buildyourpc.php" class="build-pc-button">
            <i class="fas fa-desktop fa-sm"></i> Build Your PC
        </a></li>
        <li><a href="Recycle.php" class="build-pc-button">
            <i class="fa fa-recycle"></i> Recycle
        </a></li>
    </ul>
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
                    document.getElementById('cart-count').textContent = data.cartCount;
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }

        // Call updateCartCount every 5 seconds (5000 milliseconds)
        setInterval(updateCartCount, 5000);
    </script>
</body>
</html>