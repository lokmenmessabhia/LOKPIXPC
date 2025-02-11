<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct
include 'header.php';
// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle update and delete actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

// Add to cart
if ($data && isset($data['action']) && $data['action'] === 'add_to_cart') {
    if (isset($data['products']) && is_array($data['products'])) {
        foreach ($data['products'] as $product_id => $quantity) {
            if ($product_id && is_numeric($product_id) && is_numeric($quantity)) {
                // Fetch product stock
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $available_stock = $product['stock'];
                    // Update quantity or add new, ensuring it does not exceed available stock
                    if (isset($_SESSION['cart'][$product_id])) {
                        $new_quantity = $_SESSION['cart'][$product_id] + $quantity;
                        if ($new_quantity > $available_stock) {
                            $_SESSION['cart'][$product_id] = $available_stock; // Set to max available stock
                        } else {
                            $_SESSION['cart'][$product_id] = $new_quantity; // Increment quantity
                        }
                    } else {
                        if ($quantity > $available_stock) {
                            $_SESSION['cart'][$product_id] = $available_stock; // Set to max available stock
                        } else {
                            $_SESSION['cart'][$product_id] = $quantity; // Add new product with quantity
                        }
                    }
                }
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
}


    // Update cart quantities
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if (is_numeric($product_id) && is_numeric($quantity)) {
                $_SESSION['cart'][$product_id] = max(0, (int)$quantity);
            }
        }
    }

    // Remove item from cart
    if (isset($_POST['remove_item'])) {
        $product_id = $_POST['remove_item'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
    }

    echo "Cart updated"; // Response for AJAX
    exit();
}

// Fetch cart items
$cart_items = [];
$total_price = 0;

function getProductImageUrl($photo) {
    $photo_path = "uploads/products/" . htmlspecialchars($photo);
    return file_exists($photo_path) ? $photo_path : 'path/to/default-image.jpg'; // Default image if not found
}

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Fetch the primary image details from the product_images table
        $image_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1");
        $image_stmt->execute([$product_id]);
        $image = $image_stmt->fetch(PDO::FETCH_ASSOC);

        // Check if an image was found
        if ($image) {
            $product['image_url'] = "uploads/products/" . htmlspecialchars($image['image_url']);
        } else {
            $product['image_url'] = 'path/to/default-image.jpg'; // Default image if not found
        }

        $item_total = $product['price'] * $quantity; // Use actual quantity from session
        $total_price += $item_total;

        // Set the image path and other product details
        $product['photo'] = $product['image_url']; // Use the formatted image URL
        $product['quantity'] = $quantity; // Set quantity for display
        $product['total'] = $item_total;
        $cart_items[] = $product;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Montserrat', sans-serif;
            background-color: #f5f5f5;
        }

        main {
            flex: 1;
            padding: 20px;
            margin-bottom: 200px; /* Increased margin to ensure content isn't hidden */
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Cart specific styles */
        .cart-container {
            margin-bottom: 200px; /* Extra space for cart content */
        }

        .cart-item {
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Footer styles */
        .footer {
            position: relative; /* Changed from fixed */
            bottom: 0;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            padding-top: 40px;
            margin-top: auto; /* Push footer to bottom */
            clear: both; /* Ensure footer clears all content */
        }

        .containerr {
            max-width: 1200px;
            margin: auto;
            padding: 0 20px;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            main {
                padding: 15px;
                margin-bottom: 150px; /* Adjusted for mobile */
            }

            .cart-container {
                margin-bottom: 150px;
            }

            .cart-item {
                padding: 10px;
                margin-bottom: 15px;
            }

            .footer {
                padding-top: 30px;
            }
        }

        @media (max-width: 480px) {
            main {
                margin-bottom: 120px;
            }

            .cart-container {
                margin-bottom: 120px;
            }
        }

        /* Total price and checkout button styles */
        .total {
            margin-bottom: 30px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .actions {
            margin-bottom: 30px;
        }

        /* Ensure proper spacing for empty cart message */
        .empty-cart-message {
            margin-bottom: 200px;
            text-align: center;
            padding: 20px;
        }

        /* Additional Styles */
        .cart-item { display: flex; align-items: center; border-bottom: 1px solid #ddd; padding: 10px 0; }
        .cart-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; margin-right: 15px; }
        .cart-item .info { flex: 1; }
        .cart-item .actions { display: flex; align-items: center; }
        .cart-item .actions button { border: none; background: none; cursor: pointer; margin-left: 10px; }
        .cart-item .actions img { width: 20px; height: 20px; }
        .total { font-size: 1.5rem; font-weight: bold; margin-top: 20px; }
        /* Basic reset */
* {
    font-family: montserrat, sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: montserrat, sans-serif;
}

main {
    padding: 20px;
    max-width: 1200px;
    margin: auto;
}

h1 {
    margin-bottom: 20px;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
}

thead th {
    background-color: #007bff;
    color: #fff;
    padding: 12px;
    text-align: left;
    font-size: 1.1rem;
}

tbody td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    font-size: 1rem;
    color: #333;
}

tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

tbody tr:hover {
    background-color: #f1f1f1;
}

/* Input Styling */
input[type="number"] {
    width: 60px;
    text-align: center;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* Actions */
.actions {
    margin-top: 20px;
    text-align: right;
}

.checkout-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    font-size: 1rem;
}

.checkout-button:hover {
    background-color: #0056b3;
}

footer {
    background-color: #333; /* Adjust to your preferred color */
    
    position:fixed;
    
    bottom: 0;
    width: 100%;
     /* Ensure it stays on top of other elements if needed */
}
/* Total */
.total {
    margin-top: 20px;
    font-size: 1.2em;
    font-weight: bold;
    color: #333;
}

/* Basic reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Montserrat', sans-serif;
    background-color: #f5f5f5;
    color: #333;
}

main {
    padding: 20px;
    max-width: 1200px;
    margin: auto;
    flex:1;
}


/* Ticket-like Container */
.ticket-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.ticket-container h2 {
    font-size: 2rem;
    margin-bottom: 10px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

thead th {
    background-color: #007bff;
    color: #fff;
    padding: 12px;
    text-align: left;
    font-size: 1.1rem;
}

tbody td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    font-size: 1rem;
}

/* Form Elements */
input[type="number"] {
    width: 60px;
    text-align: center;
    border: 1px solid #007bff;
    border-radius: 5px;
    padding: 5px;
    font-size: 1rem;
}

.actions {
    margin-top: 20px;
}

/* Button Styling */
button, .checkout-button {
    font-family: 'Montserrat', sans-serif;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

button:hover, .checkout-button:hover {
    background-color: #0056b3;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Additional Button Styles */
button.secondary {
    background-color: #6c757d;
    color: #fff;
}

button.secondary:hover {
    background-color: #5a6268;
}

button.success {
    background-color: #28a745;
    color: #fff;
}

button.success:hover {
    background-color: #218838;
}

button.danger {
    background-color: #dc3545;
    color: #fff;
}

button.danger:hover {
    background-color: #c82333;
}



/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    position: relative;
    text-align: center;
    max-width: 500px;
    margin: 0 auto;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.modal-logo {
    width: 100px;
    height: auto;
    margin-bottom: 25px;
}

#close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 1.8rem;
    color: #007bff;
    cursor: pointer;
}

#popup-message {
    font-size: 1.4rem;
    color: #333;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .ticket-container, .modal-content {
        padding: 15px;
    }

    table, input[type="number"] {
        font-size: 0.9rem;
    }

    button {
        font-size: 0.9rem;
        padding: 8px 16px;
    }

   
}
.total {
    margin-top: 20px;
    font-size: 1.5em;
    font-weight: bold;
    color: #28a745; /* Green color for emphasis */
    padding: 10px;
    border: 2px solid #28a745; /* Border to highlight the total */
    border-radius: 8px; /* Rounded corners for a modern look */
    background-color: #e9f7ef; /* Light green background */
    text-align: center; /* Center align text */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    display: inline-block; /* Fits the content and aligns well */
}
.product-image {
    width: 60px; /* Adjust size as needed */
    height: auto;
    border-radius: 5px;
}

/* Trash Icon Styling */
.trash-icon {
    color: #dc3545; /* Red color for trash icon */
    font-size: 1.2em; /* Adjust size as needed */
    cursor: pointer;
    transition: color 0.3s ease;
}

.trash-icon:hover {
    color: #c82333; /* Darker red on hover */
}

/* Add this to your existing CSS */
body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    margin: 0;
    padding: 0;
}

main {
    flex: 1;
    padding: 20px;
    padding-bottom: 100px; /* Add padding to prevent footer overlap */
    margin-bottom: 40px; /* Add margin to create space between content and footer */
}

.footer {
    background-color: rgba(255, 255, 255, 0.95);
    margin-top: auto; /* Push footer to bottom */
    width: 100%;
    position: relative; /* Change from fixed to relative */
    bottom: 0;
    padding-top: 40px;
    padding-bottom: 0px;
}

/* Mobile responsiveness for cart with footer */
@media(max-width: 768px) {
    main {
        padding-bottom: 120px; /* Increase padding for mobile */
    }

    .footer {
        padding-top: 30px;
    }

    .containerr {
        padding-left: 20px;
        padding-right: 20px;
    }
}

@media(max-width: 480px) {
    main {
        padding-bottom: 150px; /* Even more padding for smaller screens */
    }
}
    </style>
</head>
<body>
    
    <main>
        <h1>Shopping Cart</h1>
        <form id="cart-form" action="cart.php" method="POST">
        <?php if (!empty($cart_items)) : ?>
    <?php foreach ($cart_items as $item) : ?>
        <div class="cart-item">
            <!-- Product Image -->
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            <div class="info">
                <h2><?php echo htmlspecialchars($item['name']); ?></h2>
                <p>Price: <?php echo htmlspecialchars($item['price']); ?>   DZD</p>
                <p>
                    Quantity:
                    <input type="number" name="quantity[<?php echo htmlspecialchars($item['id']); ?>]" 
                           value="<?php echo htmlspecialchars($item['quantity']); ?>" 
                           min="1" class="quantity-input">
                </p>
            </div>
            <div class="actions">
                <button type="button" class="remove-item" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                    <img src="https://img.icons8.com/ios/50/000000/trash.png" alt="Remove">
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="total">Total Price: <?php echo number_format($total_price, 2); ?>   DZD</div>
    <div class="actions">
        <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>
    </div>
<?php else : ?>
    <p>Your cart is empty.</p>
<?php endif; ?>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cartForm = document.getElementById('cart-form');
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const removeButtons = document.querySelectorAll('.remove-item');

            // Update quantities
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const formData = new FormData(cartForm);
                    formData.append('update_cart', true);

                    fetch('cart.php', { method: 'POST', body: formData })
                        .then(response => response.text())
                        .then(() => location.reload())
                        .catch(error => console.error('Error:', error));
                });
            });

            // Remove items
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const formData = new FormData(cartForm);
                    formData.append('remove_item', productId);

                    fetch('cart.php', { method: 'POST', body: formData })
                        .then(response => response.text())
                        .then(() => location.reload())
                        .catch(error => console.error('Error:', error));
                });
            });
        });
    </script>
    <?php
include 'footer.php';
?>
</body>
</html>