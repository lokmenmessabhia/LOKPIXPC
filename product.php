<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

// Fetch product details
$product_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);


// Fetch random products excluding the current product
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT pi.image_url 
            FROM product_images pi 
            WHERE pi.product_id = p.id 
            LIMIT 1) as primary_image
    FROM products p 
    WHERE p.id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$product_id]);
$random_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch comments with user email
$stmt = $pdo->prepare("SELECT comments.comment, comments.created_at, users.email AS email 
                       FROM comments 
                       INNER JOIN users ON comments.user_id = users.id 
                       WHERE comments.product_id = ? 
                       ORDER BY comments.created_at DESC");

$stmt->execute([$product_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX request to add to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
    $quantity = (int)$_POST['quantity'];
    $response = ['status' => 'error', 'message' => 'Invalid quantity!'];

    if ($quantity > 0 && $quantity <= $product['stock']) {
        // Add to cart
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$product_id] = $quantity;

        $response = ['status' => 'success', 'message' => 'Product added to cart successfully!'];
    }

    echo json_encode($response);
    exit();
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        if (isset($_SESSION['email'])) {
            $user_email = $_SESSION['email'];
            $comment = htmlspecialchars(trim($_POST['comment']));

            if (!empty($comment)) {
                // Fetch the user's ID based on their email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$user_email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $stmt = $pdo->prepare("INSERT INTO comments (product_id, user_id, comment) VALUES (?, ?, ?)");
                    $stmt->execute([$product_id, $user['id'], $comment]);

                    header("Location: product.php?id=" . $product_id);
                    exit();
                } else {
                    echo "User not found.";
                }
            } else {
                echo "Comment cannot be empty.";
            }
        } else {
            echo "Session email not set.";
        }
    } else {
        echo "User not logged in.";
    }
}
// Fetch product images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch related products (optional)
$category_id = $product['category_id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
$stmt->execute([$category_id, $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Lokpix</title>
    <!-- <link rel="stylesheet" href="producs.css"> -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     <style>
        /* Product Container Styling */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fafafa;
            color: #1a1a1a;
        }

        main {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .product-page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            background: linear-gradient(to bottom right, #ffffff, #f8fafc);
            padding: 5rem;
            border-radius: 32px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.03),
                0 60px 120px rgba(0, 0, 0, 0.02);
            margin: 3rem auto;
            max-width: 1600px;
            position: relative;
            overflow: hidden;
        }

        .product-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.2), transparent);
        }

        .product-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .main-image {
            position: relative;
            overflow: hidden; /* Hide overflow for slider effect */
            height: 400px; /* Set a fixed height for the slider */
        }

        .main-image img {
            width: 100%; /* Full width for the primary image */
            height: 100%; /* Full height to match the container */
            object-fit: cover; /* Maintain aspect ratio while covering the area */
            transition: transform 0.5s ease; /* Smooth transition for image change */
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
        }

        .thumbnail {
            width: 100%; /* Full width for thumbnails */
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .thumbnail:hover {
            transform: scale(1.05); /* Slight zoom effect on hover */
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .product-info h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin: 0;
            color: #1a1a1a;
            line-height: 1.2;
        }

        .price {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .out-of-stock {
            color: red;
            font-weight: bold;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        }

        .quantity-selector button {
            width: 40px;
            height: 40px;
            border: none;
            background-color: #f3f4f6;
            border-radius: 12px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quantity-selector button:hover {
            background-color: #e5e7eb;
        }

        .quantity-selector input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1.1rem;
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, #007bff,rgb(141, 161, 182));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .add-to-cart-btn:hover {
            transform: scale(1.05); /* Slight scale effect on hover */
            box-shadow: 0 4px 20px rgba(0, 128, 0, 0.3); /* Shadow effect on hover */
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .product-page {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .main-image img {
                height: 300px; /* Adjust height for smaller screens */
            }
        }

        .comments-section {
            margin-top: 4rem;
            background: #f9f9f9; /* Light gray background */
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: background 0.3s ease; /* Smooth transition */
        }

        .comments-section:hover {
            background: #f0f0f0; /* Lighten background on hover */
        }

        .comments-section h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            background: linear-gradient(120deg, #1a1a1a, #404040);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .comments-section h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #007bff, transparent);
        }

        /* Individual Comment Styling */
        .comment {
            padding: 1.5rem;
            border-radius: 12px;
            background-color: #ffffff; /* White background for comments */
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease; /* Smooth transition */
        }

        .comment:hover {
            transform: translateY(-2px); /* Lift effect on hover */
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1); /* Shadow effect on hover */
        }

        .username {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #007bff;
        }

        .comment-date {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: normal;
        }

        .comment-text {
            color: #4a5568;
            line-height: 1.6;
            margin: 0;
            font-size: 1.1rem;
        }

        /* Comment Form Styling */
        #comment-form {
            margin-top: 3rem;
            background: rgba(248, 249, 250, 0.8);
            padding: 2rem;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 123, 255, 0.1);
        }

        #comment-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 1.2rem;
            border: 2px solid rgba(0, 123, 255, 0.1);
            border-radius: 12px;
            font-size: 1.1rem;
            font-family: inherit;
            background: white;
            color: #1a1a1a;
            transition: all 0.3s ease;
            resize: vertical;
            margin-bottom: 1.5rem;
        }

        #comment-form textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
        }

        #comment-form textarea::placeholder {
            color: #9ca3af;
        }

        .submit-comment-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .submit-comment-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .submit-comment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2);
        }

        .submit-comment-btn:hover::before {
            left: 100%;
        }

        /* No Comments State */
        .no-comments {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
            font-size: 1.1rem;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 16px;
            border: 1px dashed rgba(0, 123, 255, 0.2);
        }

        /* Login to Comment Link */
        .login-to-comment {
            display: inline-block;
            margin-top: 2rem;
            padding: 1rem 2rem;
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-to-comment:hover {
            background: rgba(0, 123, 255, 0.15);
            transform: translateY(-2px);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .comments-section {
                padding: 2rem;
            }
            
            .comment {
                padding: 1.5rem;
            }
            
            #comment-form {
                padding: 1.5rem;
            }
            
            .comments-section h2 {
                font-size: 1.8rem;
            }
        }

        .random-products-section {
            margin-top: 4rem;
        }

        .random-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .random-product {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .random-product:hover {
            transform: translateY(-5px);
        }

        .random-product img {
            width: 100%;
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            cursor: pointer;
        }

        .random-product-info {
            padding: 1rem;
        }

        @media (max-width: 968px) {
            .product-page {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .product-image {
                position: relative;
            }

            .product-image img {
                height: 400px;
            }
        }

        /* Random Products Styling */
        .random-products-section {
            margin-top: 4rem;
        }

        .random-products-section h2 {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .random-products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .random-product {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .random-product:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .random-product img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-bottom: 1px solid #f0f0f0;
        }

        .random-product-info {
            padding: 1.5rem;
        }

        .random-product-name {
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0 0 0.5rem 0;
        }

        .random-product-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }

        /* Comments Section Styling */
        .comments-section {
            margin-top: 4rem;
            background: #f9f9f9; /* Light gray background */
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: background 0.3s ease; /* Smooth transition */
        }

        .comments-section:hover {
            background: #f0f0f0; /* Lighten background on hover */
        }

        .comments-section h2 {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        #comment-form {
            margin-top: 2rem;
        }

        #comment-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            margin-bottom: 1rem;
        }

        #comment-form textarea:focus {
            outline: none;
            border-color: #1a1a1a;
        }

        .submit-comment-btn {
            background-color: #1a1a1a;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .submit-comment-btn:hover {
            background-color: #2c2c2c;
            transform: translateY(-2px);
        }

        .comment {
            padding: 1.5rem;
            border-radius: 12px;
            background-color: #ffffff; /* White background for comments */
            margin-bottom: 1.5rem;
        }

        .username {
            font-weight: 500;
            margin: 0 0 0.5rem 0;
            color: #1a1a1a;
        }

        .comment-date {
            font-size: 0.9rem;
            color: #6b7280;
            margin-left: 1rem;
        }

        .comment-text {
            margin: 0;
            line-height: 1.6;
            color: #4a4a4a;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .random-products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 968px) {
            .random-products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .random-products-grid {
                grid-template-columns: 1fr;
            }
            
            .comments-section {
                padding: 1.5rem;
            }
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .product-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .product-image {
            position: relative;
            aspect-ratio: 1;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
        }

        .product-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .product-images img {
            width: 100%;
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .product-images img:hover {
            transform: scale(1.05); /* Slight zoom effect on hover */
        }

        .product-images {
            margin-top: 2rem;
        }

        .main-image {
            margin-bottom: 1rem;
        }

        .main-image img {
            width: 100%; /* Full width for the primary image */
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-height: 400px; /* Set a maximum height to limit the size */
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
        }

        .thumbnail {
            width: 100%; /* Full width for thumbnails */
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .thumbnail:hover {
            transform: scale(1.05); /* Slight zoom effect on hover */
        }

        /* Slider Styling */
        .product-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .slider {
            position: relative;
            overflow: hidden; /* Hide overflow for slider effect */
            height: 500px; /* Set a fixed height for the slider */
        }

        .main-image {
            width: 90%; /* Full width for the primary image */
            height: 90%; /* Full height to match the container */
        }

        .main-image img {
            width: 100%; /* Full width for the primary image */
            height: 100%; /* Full height to match the container */
            object-fit: cover; /* Maintain aspect ratio while covering the area */
            transition: transform 0.5s ease; /* Smooth transition for image change */
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); /* Smaller thumbnails */
            gap: 1rem;
        }

        .thumbnail {
            width: 100%; /* Full width for thumbnails */
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
            max-height: 60px; /* Set a maximum height for thumbnails */
        }

        .thumbnail:hover {
            transform: scale(1.05); /* Slight zoom effect on hover */
        }

        /* Navigation Buttons */
        .prev, .next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.7);
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 18px;
            z-index: 10;
        }

        .prev {
            left: 10px;
        }

        .next {
            right: 10px;
        }

        .prev:hover, .next:hover {
            background-color: rgba(255, 255, 255, 1);
        }

        /* Updated Product Container Styling */
        .product-page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            background: white;
            padding: 4rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            margin-bottom: 4rem;
        }

        /* Enhanced Image Slider */
        .slider {
            position: relative;
            overflow: hidden;
            height: 600px;
            border-radius: 16px;
            background: #f8f9fa;
        }

        .main-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 12px;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Improved Navigation Buttons */
        .prev, .next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: white;
            border: none;
            cursor: pointer;
            padding: 16px;
            border-radius: 50%;
            font-size: 20px;
            z-index: 10;
            transition: all 0.3s ease;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .prev:hover, .next:hover {
            background-color: #f8f9fa;
            transform: translateY(-50%) scale(1.1);
        }

        /* Enhanced Thumbnail Grid */
        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .thumbnail {
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .thumbnail:hover {
            transform: scale(1.05);
            border-color: #007bff;
        }

        /* Product Info Section */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .product-info h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
            margin: 0;
        }

        .price {
            font-size: 2.8rem;
            font-weight: 600;
            color: #007bff;
        }

        /* Enhanced Add to Cart Section */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 12px;
            width: fit-content;
        }

        .quantity-selector button {
            width: 45px;
            height: 45px;
            border: none;
            background-color: white;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, #4CAF50, #388E3C); /* Green gradient background */
            border-radius: 20px; /* More rounded corners */
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition */
        }

        .add-to-cart-btn:hover {
            transform: scale(1.05); /* Slight scale effect on hover */
            box-shadow: 0 4px 20px rgba(0, 128, 0, 0.3); /* Shadow effect on hover */
        }

        /* Refined Product Container */
        .product-page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            background: linear-gradient(to bottom right, #ffffff, #f8fafc);
            padding: 5rem;
            border-radius: 32px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.03),
                0 60px 120px rgba(0, 0, 0, 0.02);
            margin: 3rem auto;
            max-width: 1600px;
            position: relative;
            overflow: hidden;
        }

        .product-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.2), transparent);
        }

        /* Enhanced Image Section */
        .slider {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 24px;
            box-shadow: 
                20px 20px 60px #d9d9d9,
                -20px -20px 60px #ffffff;
            padding: 1rem;
        }

        .main-image {
            position: relative;
            overflow: hidden;
        }

        .main-image img {
            transform-origin: center;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-image img:hover {
            transform: scale(1.05);
        }

        /* Stylish Navigation Buttons */
        .prev, .next {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 60px;
            height: 60px;
            font-size: 24px;
            color: #388E3C; /* Green color */
            transform: translateY(-50%) scale(0.9);
            transition: transform 0.3s ease; /* Smooth transition */
        }

        .prev:hover, .next:hover {
            background: #388E3C; /* Darker green on hover */
            color: white;
            transform: translateY(-50%) scale(1); /* Scale effect on hover */
        }

        /* Refined Product Info */
        .product-info {
            position: relative;
            padding: 2rem;
        }

        .product-info h1 {
            font-size: 3rem;
            background: linear-gradient(120deg, #1a1a1a, #404040);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .price {
            font-size: 2.8rem;
            color: #007bff;
            font-weight: 700;
            display: inline-block;
            position: relative;
        }

        .price::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #007bff, transparent);
        }

        /* Enhanced Add to Cart Section */
        .quantity-selector {
            background: linear-gradient(145deg, #f0f0f0, #ffffff);
            border-radius: 16px;
            padding: 0.8rem;
            box-shadow: 
                5px 5px 10px #d9d9d9,
                -5px -5px 10px #ffffff;
        }

        .quantity-selector button {
            background: white;
            color: #007bff;
            font-weight: 600;
            box-shadow: 
                3px 3px 6px #d9d9d9,
                -3px -3px 6px #ffffff;
        }

        .quantity-selector input {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
            width: 70px;
            text-align: center;
            border: none;
            background: transparent;
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, #4CAF50, #388E3C); /* Green gradient background */
            border-radius: 20px; /* More rounded corners */
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition */
        }

        .add-to-cart-btn:hover {
            transform: scale(1.05); /* Slight scale effect on hover */
            box-shadow: 0 4px 20px rgba(0, 128, 0, 0.3); /* Shadow effect on hover */
        }

        /* Thumbnail Grid Refinements */
        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: rgba(248, 249, 250, 0.5);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .thumbnail {
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease; /* Smooth transition */
        }

        .thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 123, 255, 0.2); /* Shadow effect on hover */
        }

        /* Responsive Design Improvements */
        @media (max-width: 1200px) {
            .product-page {
                padding: 3rem;
                gap: 3rem;
            }
            
            .product-info h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                font-size: 14px; /* Adjust base font size for better readability */
            }

            main {
                padding: 1rem; /* Reduce padding for main content */
            }

            .product-page {
                grid-template-columns: 1fr; /* Stack elements vertically */
                padding: 2rem; /* Reduce padding */
            }

            .slider {
                height: 300px; /* Adjust height for smaller screens */
            }

            .product-info {
                padding: 1rem; /* Reduce padding */
            }

            .thumbnail-grid {
                grid-template-columns: repeat(3, 1fr); /* Adjust thumbnail grid for smaller screens */
            }

            .add-to-cart-btn {
                font-size: 1rem; /* Adjust button font size */
                padding: 1rem; /* Adjust button padding */
            }

            .comments-section {
                padding: 1.5rem; /* Reduce padding */
            }

            .comment {
                padding: 1rem; /* Reduce comment padding */
            }

            .submit-comment-btn {
                font-size: 1rem; /* Adjust button font size */
                padding: 0.8rem; /* Adjust button padding */
            }

            .random-products-grid {
                grid-template-columns: repeat(2, 1fr); /* Show two random products per row */
            }

            .random-product {
                margin: 0; /* Remove margin for better fit */
            }

            .random-product img {
                height: auto; /* Maintain aspect ratio */
            }

            .product-images {
                flex-direction: column; /* Stack images vertically */
            }

            .main-image {
                height: 250px; /* Adjust height for main image */
            }

            .thumbnail-grid {
                grid-template-columns: repeat(2, 1fr); /* Show two thumbnails per row */
            }

            .username {
                font-size: 0.9rem; /* Adjust username font size */
            }

            .comment-date {
                font-size: 0.8rem; /* Adjust comment date font size */
            }

            .comment-text {
                font-size: 0.9rem; /* Adjust comment text font size */
            }
        }

        /* Out of Stock State */
        .out-of-stock {
            color: #dc3545;
            font-size: 1.2rem;
            font-weight: 600;
            padding: 1rem;
            border: 2px solid #dc3545;
            border-radius: 12px;
            text-align: center;
            background: rgba(220, 53, 69, 0.1);
        }

        /* Wishlist Heart Button Styling */
        .wishlist-heart {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
        }

        .wishlist-heart i {
            font-size: 22px;
            color: #e74c3c;
            transition: all 0.3s ease;
        }

        .wishlist-heart:hover {
            transform: scale(1.15);
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .wishlist-heart:hover i {
            transform: scale(1.1);
        }

        .wishlist-heart.in-wishlist {
            background: #e74c3c;
        }

        .wishlist-heart.in-wishlist i {
            color: white;
        }

        .wishlist-heart::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(231, 76, 60, 0.1);
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .wishlist-heart:hover::before {
            transform: scale(1.2);
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(1); }
            75% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .wishlist-heart.animate {
            animation: heartBeat 0.6s ease-in-out;
        }

        .product-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }

        .product-header h1 {
            margin: 0;
            font-size: 2em;
            color: #2c3e50;
            flex: 1;
        }

        .wishlist-heart {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            flex-shrink: 0;
        }

        .wishlist-heart i {
            font-size: 22px;
            color: #e74c3c;
            transition: all 0.3s ease;
        }

        .wishlist-heart:hover {
            transform: scale(1.15);
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .wishlist-heart:hover i {
            transform: scale(1.1);
        }

        .wishlist-heart.in-wishlist {
            background: #e74c3c;
        }

        .wishlist-heart.in-wishlist i {
            color: white;
        }

        .wishlist-heart::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(231, 76, 60, 0.1);
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .wishlist-heart:hover::before {
            transform: scale(1.2);
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(1); }
            75% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .wishlist-heart.animate {
            animation: heartBeat 0.6s ease-in-out;
        }

        .price {
            font-size: 1.5em;
            color: #e74c3c;
            font-weight: 600;
            margin: 15px 0;
        }

        .description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="product-page">
        <div class="product-images">
    <h3>Product Images</h3>
    <div class="slider">
        <div class="main-image">
            <img id="primary-image" src="uploads/products/<?php echo htmlspecialchars($product_images[0]['image_url']); ?>" alt="Primary Product Image">
        </div>
        <button class="prev" onclick="changeImage(-1)">&#10094;</button>
        <button class="next" onclick="changeImage(1)">&#10095;</button>
    </div>
    <div class="thumbnail-grid">
        <?php if (!empty($product_images)): ?>
            <?php foreach ($product_images as $index => $image): ?>
                <img class="thumbnail" src="uploads/products/<?php echo htmlspecialchars($image['image_url']); ?>" alt="Product Image" onclick="setPrimaryImage(this, <?php echo $index; ?>)">
            <?php endforeach; ?>
        <?php else: ?>
            <p>No images available for this product.</p>
        <?php endif; ?>
    </div>
</div>
            
            <div class="product-info">
                <div class="product-header">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <?php if (isset($_SESSION['user_id'])): 
                        // Check if product is in user's wishlist
                        $stmt = $pdo->prepare("SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?");
                        $stmt->execute([$_SESSION['user_id'], $product_id]);
                        $inWishlist = $stmt->fetchColumn();
                    ?>
                        <div class="wishlist-heart <?php echo $inWishlist ? 'active' : ''; ?>" 
                             data-product-id="<?php echo $product_id; ?>"
                             title="<?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                            <i class="fas fa-heart"></i>
                        </div>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="wishlist-heart"
                           title="Login to add to wishlist">
                            <i class="fas fa-heart"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <p class="price"><?php echo htmlspecialchars($product['price']); ?>   DZD  </p>
                <p><?php echo htmlspecialchars($product['description']); ?></p>

                <?php if ($product['stock'] == 0): ?>
                    <p class="out-of-stock">Currently Out of Stock</p>
                <?php else: ?>
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <form id="add-to-cart-form">
                            <div class="quantity-selector">
                                <button type="button" id="decrease-qty">-</button>
                                <input type="text" id="quantity" name="quantity" value="1">
                                <button type="button" id="increase-qty">+</button>
                            </div>
                            <input type="hidden" name="action" value="add_to_cart">
                            <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="add-to-cart-btn" style="display: inline-block; text-decoration: none; text-align: center;">
                            Login to Add to Cart
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h2>Comments</h2>
            
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <p class="username">
                            <?php echo htmlspecialchars($comment['email']); ?>
                            <span class="comment-date">
                                <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                            </span>
                        </p>
                        <p class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>

            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <form id="comment-form" action="product.php?id=<?php echo $product_id; ?>" method="POST">
                    <textarea 
                        name="comment" 
                        id="comment" 
                        placeholder="Share your thoughts about this product..."
                        required
                    ></textarea>
                    <button type="submit" class="submit-comment-btn">Post Comment</button>
                </form>
            <?php else: ?>
                <p>
                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                       class="submit-comment-btn">
                        Login to leave a comment
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Random Products Section -->
        <div class="random-products-section">
            <h2>You Might Also Like</h2>
            <div class="random-products-grid">
                <?php foreach ($random_products as $random_product): ?>
                    <a href="product.php?id=<?php echo $random_product['id']; ?>" class="random-product">
                        <?php if (!empty($random_product['primary_image'])): ?>
                            <img src="uploads/products/<?php echo htmlspecialchars($random_product['primary_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($random_product['name']); ?>">
                        <?php else: ?>
                            <img src="default-image.jpg" 
                                 alt="<?php echo htmlspecialchars($random_product['name']); ?>">
                        <?php endif; ?>
                        <div class="random-product-info">
                            <p class="random-product-name">
                                <?php echo htmlspecialchars($random_product['name']); ?>
                            </p>
                            <p class="random-product-price"><?php echo htmlspecialchars($random_product['price']); ?>   DZD
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="toast" id="toast"></div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let quantity = 1;
            const maxStock = <?php echo htmlspecialchars($product['stock']); ?>;

            document.getElementById('increase-qty').addEventListener('click', function() {
                if (quantity < maxStock) {
                    quantity++;
                    document.getElementById('quantity').value = quantity;
                }
            });

            document.getElementById('decrease-qty').addEventListener('click', function() {
                if (quantity > 1) {
                    quantity--;
                    document.getElementById('quantity').value = quantity;
                }
            });

          document.getElementById('add-to-cart-form').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);
    formData.append('quantity', document.getElementById('quantity').value);

    fetch('product.php?id=<?php echo $product_id; ?>', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(() => {
        alert('An error occurred.');
    });
});

            });
     
    </script>

    <script>
        let currentIndex = 0; // Track the current image index
        const images = <?php echo json_encode(array_column($product_images, 'image_url')); ?>; // Get image URLs

        function setPrimaryImage(thumbnail, index) {
            currentIndex = index; // Update current index
            const primaryImage = document.getElementById('primary-image');
            primaryImage.src = thumbnail.src; // Set the primary image to the clicked thumbnail
        }

        function changeImage(direction) {
            currentIndex += direction; // Update index based on direction
            if (currentIndex < 0) {
                currentIndex = images.length - 1; // Loop to last image
            } else if (currentIndex >= images.length) {
                currentIndex = 0; // Loop to first image
            }
            const primaryImage = document.getElementById('primary-image');
            primaryImage.src = 'uploads/products/' + images[currentIndex]; // Update primary image
        }
    </script>
    <script>
        const wishlistHeart = document.querySelector('.wishlist-heart');
        const toast = document.getElementById('toast');
            
        function showToast(message, success = true) {
            toast.textContent = message;
            toast.style.background = success ? '#2ecc71' : '#e74c3c';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        if (wishlistHeart && !wishlistHeart.tagName.toLowerCase() === 'a') {
            wishlistHeart.addEventListener('click', function() {
                const action = this.classList.contains('active') ? 'remove' : 'add';
                const productId = this.dataset.productId;

                // Add animation class
                this.classList.add('animate');
                setTimeout(() => this.classList.remove('animate'), 600);

                fetch('wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${action}&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('active');
                        this.title = this.classList.contains('active') ? 'Remove from Wishlist' : 'Add to Wishlist';
                        showToast(data.message);
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(error => {
                    showToast('An error occurred. Please try again.', false);
                });
            });
        }
    </script>
            <?php
include 'footer.php';
?>
    
</body>
</html>