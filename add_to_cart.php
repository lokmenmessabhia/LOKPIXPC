<?php
session_start();

// Initialize cart from cookie if session cart is empty
if (!isset($_SESSION['cart']) && isset($_COOKIE['cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['cart'], true) ?? [];
}

include 'db_connect.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $stock = (int)$product['stock'];

            if ($quantity <= $stock) {
                // Load existing cart from session
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                // Add/update cart in session
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                
                // Always save cart to cookies with secure settings
                $cart_json = json_encode($_SESSION['cart']);
                setcookie('cart', $cart_json, [
                    'expires' => time() + (30 * 24 * 60 * 60), // 30 days expiration
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                // Redirect to cart page
                header("Location: cart.php");
                exit();
            } else {
                echo "❌ Not enough stock available!";
            }
        } else {
            echo "❌ Product not found!";
        }
    } else {
        echo "❌ Quantity must be greater than 0!";
    }
}
?>
