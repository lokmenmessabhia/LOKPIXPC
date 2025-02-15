<?php
session_start();
include 'db_connect.php';
include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => 'Invalid request'];
    $user_id = $_SESSION['user_id'];
    
    if (isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)");
                    $stmt->execute([$user_id, $product_id]);
                    $response = ['success' => true, 'message' => 'Product added to wishlist'];
                    break;
                    
                case 'remove':
                    $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    $response = ['success' => true, 'message' => 'Product removed from wishlist'];
                    break;
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch wishlist items with product details
$stmt = $pdo->prepare("
    SELECT p.*, 
           pi.image_url as primary_image,
           w.created_at as added_date
    FROM wishlists w 
    JOIN products p ON w.product_id = p.id 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    WHERE w.user_id = ?
    GROUP BY p.id
    ORDER BY w.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - LokPix PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .wishlist-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .wishlist-header h1 {
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .wishlist-header p {
            color: #666;
            font-size: 1.1em;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .wishlist-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .wishlist-item:hover {
            transform: translateY(-10px);
        }

        .product-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .wishlist-item:hover .product-image img {
            transform: scale(1.1);
        }

        .product-info {
            padding: 20px;
        }

        .product-info h3 {
            margin: 0 0 10px;
            font-size: 1.3em;
            color: #2c3e50;
        }

        .price {
            font-size: 1.2em;
            color: #2ecc71;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .stock {
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .in-stock {
            color: #2ecc71;
        }

        .out-of-stock {
            color: #e74c3c;
        }

        .added-date {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .remove-button {
            background: #f1f1f1;
            color: #666;
        }

        .remove-button:hover {
            background: #e74c3c;
            color: white;
        }

        .cart-button {
            background: #3498db;
            color: white;
        }

        .cart-button:hover {
            background: #2980b9;
        }

        .cart-button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .empty-wishlist {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-wishlist i {
            font-size: 3em;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-wishlist p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .shop-button {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .shop-button:hover {
            background: #2980b9;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #2ecc71;
            color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .toast.show {
            display: block;
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .wishlist-header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="wishlist-container">
        <div class="wishlist-header">
            <h1>My Wishlist</h1>
            <p>Keep track of all your favorite products</p>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart-broken"></i>
                <p>Your wishlist is empty</p>
                <a href="index.php" class="shop-button">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-item" data-product-id="<?php echo $item['id']; ?>">
                        <div class="product-image">
                            <a href="product.php?id=<?php echo $item['id']; ?>">
                                <img src="<?php echo !empty($item['primary_image']) ? 'uploads/products/' . htmlspecialchars($item['primary_image']) : 'placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </a>
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="price"><?php echo number_format($item['price'], 2); ?> DZD</p>
                            <p class="added-date">Added on <?php echo date('F j, Y', strtotime($item['added_date'])); ?></p>
                            <?php if ($item['stock'] > 0): ?>
                                <p class="stock in-stock">In Stock</p>
                            <?php else: ?>
                                <p class="stock out-of-stock">Out of Stock</p>
                            <?php endif; ?>
                            <div class="action-buttons">
                                <button class="action-button remove-button" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                                <button class="action-button cart-button" 
                                        onclick="moveToCart(<?php echo $item['id']; ?>)"
                                        <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i> Move to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        function showToast(message, success = true) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = success ? '#2ecc71' : '#e74c3c';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function removeFromWishlist(productId) {
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
                    item.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => {
                        item.remove();
                        if (document.querySelectorAll('.wishlist-item').length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                    showToast(data.message);
                } else {
                    showToast(data.message, false);
                }
            })
            .catch(error => {
                showToast('An error occurred. Please try again.', false);
            });
        }

        function moveToCart(productId) {
            // First add to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // If successfully added to cart, remove from wishlist
                    return fetch('wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=remove&product_id=${productId}`
                    }).then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    });
                } else {
                    throw new Error(data.message || 'Failed to add to cart');
                }
            })
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
                    item.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => {
                        item.remove();
                        if (document.querySelectorAll('.wishlist-item').length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                    showToast('Product moved to cart successfully');
                } else {
                    showToast(data.message, false);
                }
            })
            .catch(error => {
                showToast(error.message || 'An error occurred. Please try again.', false);
                console.error('Error:', error);
            });
        }

        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateY(20px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
