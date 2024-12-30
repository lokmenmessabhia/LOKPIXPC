<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $quantity <= $product['stock']) {
            // Add product to cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }

            // Redirect to cart page or show a success message
            header("Location: cart.php");
            exit();
        } else {
            echo "Invalid quantity or product not found!";
        }
    } else {
        echo "Quantity must be greater than 0!";
    }
}
?>