<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Fetch user email and phone
try {
    $stmt = $pdo->prepare("SELECT email, phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['userid']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_email = $user['email'];
    $user_phone = $user['phone'];
} catch (PDOException $e) {
    echo "Error: Unable to fetch user information. " . $e->getMessage();
    exit();
}

// Generate a secure token
$token = bin2hex(random_bytes(16)); // 32 characters long token

// Handle checkout form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $wilaya_id = (int)$_POST['wilaya'];
    $delivery_type = $_POST['delivery_type'];
    $token = $_POST['token']; // Get token from form

    if (empty($phone) || empty($address) || !$wilaya_id || empty($delivery_type)) {
        echo "<script>displayPopup('Please fill out all fields.');</script>";
        exit();
    }

    // Ensure that the token matches the one generated for this order
    if (empty($token) || $token !== $_SESSION['order_token']) {
        echo "<script>displayPopup('Invalid token.');</script>";
        exit();
    }

    // Retrieve cart items
    if (empty($_SESSION['cart'])) {
        echo "<script>displayPopup('Your cart is empty.');</script>";
        exit();
    }

    // Begin transaction
    try {
        $pdo->beginTransaction();

        // Calculate total price
        $total_price = 0;
        $product_stmt = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");
        $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, email, phone, address, wilaya_id, delivery_type, total_price, order_date, qrtoken) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");

        // Insert the order itself (without the cart items yet)
        $order_stmt->execute([$_SESSION['userid'], $user_email, $phone, $address, $wilaya_id, $delivery_type, $total_price, $token]);

        $order_id = $pdo->lastInsertId(); // Get last inserted order ID

        // Insert cart items into order_details table
        $order_details_stmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity) VALUES (?, ?, ?)");
        $product_update_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            // Fetch product details
            $product_stmt->execute([$product_id]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // Check if there's enough stock
                if ($product['stock'] < $quantity) {
                    echo "<script>displayPopup('Not enough stock for product ID $product_id.');</script>";
                    $pdo->rollBack();
                    exit();
                }

                $item_total = $product['price'] * $quantity;
                $total_price += $item_total;

                // Insert each cart item into order_details table
                $order_details_stmt->execute([$order_id, $product_id, $quantity]);

                // Update the product stock in the database
                $product_update_stmt->execute([$quantity, $product_id]);
            } else {
                echo "<script>displayPopup('Product not found in database.');</script>";
                $pdo->rollBack();
                exit();
            }
        }

        // Update the total price in the orders table
        $update_order_stmt = $pdo->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
        $update_order_stmt->execute([$total_price, $order_id]);

        // Insert a notification for all admins
        $admin_stmt = $pdo->prepare("SELECT id FROM admins");
        $admin_stmt->execute();
        $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (admin_id, message, is_read) VALUES (?, ?, 0)"); // Set is_read to 0 for new notifications
            $notification_message = "New order placed by user ID: " . $_SESSION['userid'] . " with total price: " . $total_price;
            $notification_stmt->execute([$admin['id'], $notification_message]);
        }

        // Commit transaction
        $pdo->commit();

        // Clear cart
        unset($_SESSION['cart']);

        // Send order details to Telegram
        $telegram_message = "New Order:\n\n";
        $telegram_message .= "Order ID: $order_id\n";
        $telegram_message .= "Email: $user_email\n";
        $telegram_message .= "Phone: $phone\n";
        $telegram_message .= "Address: $address\n";
        $telegram_message .= "Wilaya ID: $wilaya_id\n";
        $telegram_message .= "Delivery Type: $delivery_type\n";
        $telegram_message .= "Total Price: $total_price\n";
        $telegram_message .= "Verification Token: $token\n";

        // Send to Telegram
        $telegram_token = '7322742533:AAEEYMpmOGhkwuOyfU-6Y4c6UtjK09ti9vE'; // Your bot token
        $chat_id = '-1002458122628'; // Your chat ID
        $telegram_url = "https://api.telegram.org/bot$telegram_token/sendMessage";

        $data = [
            'chat_id' => $chat_id,
            'text' => $telegram_message
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($telegram_url, false, $context);

        if ($result === FALSE) {
            echo "<script>displayPopup('Error sending message to Telegram.');</script>";
        } else {
            echo "<script>displayPopup('Order placed successfully!');</script>";
        }

    } catch (PDOException $e) {
        // Rollback transaction in case of error
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Store the token in session for validation on form submission
$_SESSION['order_token'] = $token;

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Lokpix</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e5e9f0 100%);
            margin: 0;
            
            color: #1a1a1a;
            line-height: 1.6;
            min-height: 100vh;
        }

        main {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.04),
                0 8px 16px rgba(59, 130, 246, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        h1 {
            font-size: 32px;
            margin-bottom: 35px;
            color: #2d3748;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #edf2f7;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, transparent);
            border-radius: 3px;
        }

        form {
            margin-bottom: 35px;
            padding: 25px;
            border-radius: 16px;
            background: rgba(248, 250, 252, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        form:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.06);
        }

        .form-group {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
        }

        label {
            display: block;
            font-weight: 500;
            color: #4a5568;
            width: 140px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 20px;
            margin-top:15px;
        }

        label::before {
            content: '→';
            position: absolute;
            left: 0;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .form-group:hover label::before {
            opacity: 1;
            color: #3b82f6;
        }

        .form-group:hover label {
            color: #3b82f6;
            transform: translateX(5px);
        }

        input[type="text"], 
        input[type="email"],
        select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        input:hover,
        select:hover {
            border-color: #cbd5e1;
            background-color: #ffffff;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            background-color: #ffffff;
            transform: translateY(-1px);
        }

        .checkout-button {
            padding: 14px 28px;
            margin-top:50px;
            width: 100%;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }

        .checkout-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .checkout-button:hover::before {
            left: 100%;
        }

        .checkout-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        .popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .popup-logo {
            width: 80px;
            margin-bottom: 20px;
        }

        #popup-close {
            margin-top: 20px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            background: #3b82f6;
            color: #fff;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        #popup-close:hover {
            background: #2563eb;
        }

       
        .button-pressed {
            transform: scale(0.98) translateY(2px);
        }

        @media (max-width: 768px) {
            main {
                margin: 20px auto;
                padding: 25px;
                border-radius: 20px;
            }

            form {
                padding: 20px;
            }

            .form-group {
                flex-direction: column;
                align-items: stretch;
            }

            label {
                width: 100%;
                margin-bottom: 8px;
            }

            .checkout-button {
                width: 100 %;
                margin-top: 10 px;
                padding:20 px;
            }
        }
    </style>
</head>
<body>
    <main>
        <h1>Checkout</h1>
        <form action="" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user_email) ?>" disabled>

            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($user_phone) ?>" required>

            <label for="address">Address</label>
            <input type="text" name="address" id="address" required>

            <label for="wilaya">Wilaya</label>
            <select name="wilaya" id="wilaya" required>
                <option value="">Select Wilaya</option>
                <?php
                $stmt = $pdo->query("SELECT id, name FROM wilayas");
                while ($wilaya = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value=\"{$wilaya['id']}\">{$wilaya['name']}</option>";
                }
                ?>
            </select>

            <label for="delivery_type">Delivery Type</label>
            <select name="delivery_type" id="delivery_type" required>
                <option value="Standard">Standard</option>
                <option value="Express">Express</option>
            </select>

            <button type="submit" class="checkout-button">
                <svg id="cart" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M17,18C15.89,18 15,18.89 15,20A2,2 0 0,0 17,22A2,2 0 0,0 19,20C19,18.89 18.1,18 17,18M1,2V4H3L6.6,11.59L5.24,14.04C5.09,14.32 5,14.65 5,15A2,2 0 0,0 7,17H19V15H7.42A0.25,0.25 0 0,1 7.17,14.75C7.17,14.7 7.18,14.66 7.2,14.63L8.1,13H15.55C16.3,13 16.96,12.58 17.3,11.97L20.88,5.5C20.95,5.34 21,5.17 21,5A1,1 0 0,0 20,4H5.21L4.27,2M7,18C5.89,18 5,18.89 5,20A2,2 0 0,0 7,22A2,2 0 0,0 9,20C9,18.89 8.1,18 7,18Z"/>
                </svg>
                <span>Checkout</span>
                <svg id="check" width="24" height="24" viewBox="0 0 24 24">
                    <path stroke-width="2" fill="none" stroke="#ffffff" d="M 3,12 l 6,6 l 12,-12"/>
                </svg>
            </button>
        </form>
    </main>

    <div class="popup-overlay" id="popup-overlay">
        <div class="popup-content">
            <img src="logo.png" alt="Logo" class="popup-logo">
            <p id="popup-message"></p>
            <button id="popup-close">Close</button>
        </div>
    </div>

    <footer>
    <?php
include 'footer.php';
?>
    </footer>

    <script>
        function displayPopup(message) {
            const overlay = document.getElementById('popup-overlay');
            const messageElement = document.getElementById('popup-message');
            messageElement.textContent = message;
            overlay.style.display = 'block';
        }

        document.getElementById('popup-close').addEventListener('click', function() {
            document.getElementById('popup-overlay').style.display = 'none';
        });

        const btn = document.querySelector('.checkout-button');

        btn.addEventListener('mousedown', () => {
            btn.classList.add('button-pressed');
        });

        btn.addEventListener('mouseup', () => {
            btn.classList.remove('button-pressed');
        });

        btn.addEventListener('mouseleave', () => {
            btn.classList.remove('button-pressed');
        });

        btn.addEventListener('click', () => {
            document.documentElement.classList.toggle('checked-out');
        });
    </script>
</body>
</html>