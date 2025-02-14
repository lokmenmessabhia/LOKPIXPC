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
        body {
            line-height: 1.5;
            font-family: 'Poppins', sans-serif;
           
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .containerr {
    
    padding-left:200px;
   
    background: rgba(255, 255, 255, 0.95);
    border-radius: 24px;
    
    backdrop-filter: blur(10px);
  
}
        .row {
            display: flex;
            flex-wrap: wrap;
        }
        ul {
            list-style: none;

        }
        .footer {
            background-color: rgba(255, 255, 255, 0.95); 
            position: fix;/* Footer background */
       margin-top:40px;
            padding-top: 40px;
            padding-bottom: 0px;
        }
        .footer-col {
            width: 20%;
            padding: 0 10px;
        }
        .footer-col h4 {
            font-size: 16px;
            color: #2b6cb0; /* Heading color */
            text-transform: capitalize;
            margin-bottom: 20px;
            font-weight: 500;
            position: relative;
        }
        .footer-col h4::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            background-color: #2b6cb0; /* Line under heading */
            height: 2px;
            box-sizing: border-box;
            width: 50px;
        }
        .footer-col ul li:not(:last-child) {
            margin-bottom: 8px;
        }
        .footer-col ul li a {
            font-size: 14px;
            text-transform: capitalize;
            color: #2b6cb0; /* Link color */
            text-decoration: none;
            font-weight: 300;
            display: block;
            transition: all 0.3s ease;
        }
        .footer-col ul li a:hover {
            color: #2b6cb3; /* Link hover color */
            padding-left: 8px;
        }
        .footer-col .social-links a {
            display: inline-block;
            height: 35px;
            width: 35px;
            background-color: rgba(242, 242, 242, 0.2); /* Semi-transparent background */
            margin: 0 8px 8px 0;
            text-align: center;
            line-height: 35px;
            border-radius: 50%;
            color: #2b6cb0; /* Icon color */
            transition: all 0.5s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .footer-col .social-links a:hover {
            color: #2b6cc6; /* Icon hover color */
            background-color: #f2f2f2;
            transition: all 0.3s ease; /* Icon background hover */
            
        }
        
        @media (max-width: 767px) {
    .footer-col img {
        max-width: 50px; /* Reduce logo size */
        height: auto;
        margin-bottom: 20px;
        margin-right: 0; /* Center align logo */
        display: block;
        position: left;
        margin-left: 0;
        margin-right: 0; /* Center the logo horizontally */
    }

    .footer-col:first-child {
        text-align: center; /* Center text and logo */
        width: 100%; /* Ensure it spans the full width */
    }
}
        @media(max-width: 574px) {
            .footer-col {
                width: 100%;
            }
        }
        .build-pc-link {
    border-radius: 25px;
    padding: 10px 20px;
    text-align: center;
    display: inline-block;
    text-decoration: none;
    color: #2b6cb0; /* Button text color */
    transition: all 0.3s ease;
    background-color: rgba(242, 242, 242, 0.2);
}

.build-pc-link:hover {
    color: #2b6cb3; /* Hover text color */
    padding-left: 8px; /* Optional hover animation */
}

.footer-logo {
    max-width: 150px;
    height: auto;
    margin-bottom: 20px;
    margin-right: -600px;
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