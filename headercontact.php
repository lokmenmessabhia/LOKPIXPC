
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokpix</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
</head>
<body>
    <header>
        <div class="logo">Lokpix</div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="sign-up.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
            <?php
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            echo '<p>You must be logged in to access this page. <a href="login.php">Log in here</a>.</p>';
        } else {
        ?>

        </nav>
    </header>