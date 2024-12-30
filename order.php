<?php
include 'db_connect.php'; // Ensure this path is correct
session_start();

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (empty($cart)) {
    echo "Your cart is empty.";
    exit;
}

// Handle order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $user_id = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
    $total_price = 0;

    foreach ($cart as $product_id => $quantity) {
        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Calculate total price
            $total_price += $product['price'] * $quantity;

            // Update stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $product_id]);
        }
    }

    // Insert order into database (you need to create the orders table)
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, $total_price]);
    $order_id = $pdo->lastInsertId();

    // Add order details
    foreach ($cart as $product_id => $quantity) {
        $stmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $quantity]);
    }

    // Clear cart
    unset($_SESSION['cart']);

    // Call the Yalidine API (pseudo-code, adjust as needed)
    $yalidine_api_url = 'https://api.yalidine.dz/create_order';
    $data = [
        'order_id' => $order_id,
        'total_price' => $total_price,
        'delivery_address' => $_POST['delivery_address']
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($yalidine_api_url, false, $context);

    if ($result === FALSE) {
        echo "Failed to communicate with delivery service.";
    } else {
        echo "Order placed successfully. Delivery scheduled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order - Lokpix</title>
    <link rel="stylesheet" href="order.css"> <!-- Ensure this path is correct -->
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <h2>Your Cart</h2>
        <form action="order.php" method="post">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total_price = 0; ?>
                    <?php foreach ($cart as $product_id => $quantity): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo htmlspecialchars($product['price']); ?></td>
                            <td><?php echo htmlspecialchars($quantity); ?></td>
                            <td>$<?php echo htmlspecialchars($product['price'] * $quantity); ?></td>
                        </tr>
                        <?php $total_price += $product['price'] * $quantity; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total Price</th>
                        <th>$<?php echo $total_price; ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="delivery-address">
                <label for="delivery-address">Delivery Address</label>
                <input type="text" id="delivery-address" name="delivery_address" required>
            </div>

            <button type="submit" name="place_order">Place Order</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>
