<?php
// footer.php

// Include database connection (update path if necessary)
include 'db_connect.php'; // Ensure this file sets up a PDO instance in $pdo

// Fetch categories from the database
try {
    $stmt = $pdo->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: Unable to fetch categories. " . $e->getMessage();
    $categories = []; // Initialize as empty array in case of error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    /* Base Styles */
    body {
        line-height: 1.5;
        font-family: 'Poppins', sans-serif;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Footer Container */
    .footer {
        background-color: #ffffff; /* White background */
        color: #333333; /* Dark text for contrast */
        padding: 60px 20px 20px;
        margin-top: 40px;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05); /* Subtle shadow for depth */
    }

    .containerr {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 20px;
    }

    /* Footer Columns */
    .footer-col {
        flex: 1;
        min-width: 200px;
        padding: 10px;
    }

    .footer-col h4 {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50; /* Dark blue for headings */
        margin-bottom: 20px;
        position: relative;
    }

    .footer-col h4::before {
        content: '';
        position: absolute;
        left: 0;
        bottom: -10px;
        width: 50px;
        height: 2px;
        background-color: #3498db; /* Accent line under headings */
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
    }

    .footer-col ul li {
        margin-bottom: 10px;
    }

    .footer-col ul li a {
        color: #555555; /* Medium gray for links */
        text-decoration: none;
        font-size: 14px;
        font-weight: 400;
        transition: all 0.3s ease;
    }

    .footer-col ul li a:hover {
        color: #3498db; /* Accent color on hover */
        padding-left: 5px;
    }

    /* Social Links */
    .footer-col .social-links {
        display: flex;
        gap: 10px;
    }

    .footer-col .social-links a {
        display: inline-block;
        width: 40px;
        height: 40px;
        background-color: #f8f9fa; /* Light gray background */
        border-radius: 50%;
        text-align: center;
        line-height: 40px;
        color: #555555; /* Medium gray icons */
        transition: all 0.3s ease;
    }

    .footer-col .social-links a:hover {
        background-color: #3498db; /* Accent color on hover */
        color: #ffffff; /* White icons on hover */
        transform: translateY(-5px);
    }

    /* Logo Section */
    .footer-logo {
        max-width: 150px;
        height: auto;
        margin-bottom: 20px;
    }

    /* Build PC Link */
    .build-pc-link {
        display: inline-block;
        padding: 10px 20px;
        background-color: #3498db; /* Accent color */
        color: #ffffff; /* White text */
        border-radius: 25px;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .build-pc-link:hover {
        background-color: #2980b9; /* Darker accent color on hover */
        transform: translateY(-3px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .footer-col {
            flex: 1 1 45%; /* Two columns on tablets */
        }

        .footer-logo {
            max-wi dth: 120px;
        }
    }

    @media (max-width: 480px) {
        .footer-col {
            flex: 1 1 100%; /* Single column on mobile */
            text-align: center;
        }

        .footer-col h4::before {
            left: 50%;
            transform: translateX(-50%); /* Center the underline */
        }

        .footer-col .social-links {
            justify-content: center;
        }

        .footer-logo {
            max-width: 100px;
        }
    }
</style>
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<footer class="footer">
    <div class="containerr">
        <div class="row">
            <div class="footer-col">
                <img src="logo (1).png" alt="Company Logo" class="footer-logo">
            </div>
            <div class="footer-col">
                <h4>EcoTech</h4>
                <ul>
                    <li><a href="about.php">about us</a></li>
                    <li><a href="contact.php">Contact us</a></li>
                    <li><a href="privacy-policy.php">privacy policy</a></li>
                </ul>
            </div>
           
            <div class="footer-col">
                <h4>online shop</h4>
                <ul>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li><a href="category.php?id=<?php echo htmlspecialchars($category['id'])?>"><?= htmlspecialchars($category['name']) ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="#">No categories available</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>follow us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>
</body>
</html>