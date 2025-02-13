<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db_connect.php';
include 'header.php';

$loggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

function fetchSubcategories($categoryId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchProductsBySubcategory($subcategoryIds) {
    global $pdo;
    $ids = implode(',', array_map('intval', $subcategoryIds));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE subcategory_id IN ($ids) AND category_id IN (SELECT id FROM categories WHERE name IN ('PC Components', 'Peripherals'))");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchWilayas() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM wilayas ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$categories = fetchCategories();
$wilayas = fetchWilayas();
$subcategoryOrder = [];
$requiredCategories = ['PC Components', 'Peripherals'];


foreach ($categories as $category) {
    if (in_array($category['name'], $requiredCategories)) {
        $subcategories = fetchSubcategories($category['id']);
        if (!empty($subcategories)) {
            $subcategoryOrder[$category['name']] = $subcategories;
        }
    }
}

if (empty($subcategoryOrder)) {
    echo "<p>No subcategories found for the selected categories.</p>";
} else {
    $productsBySubcategory = [];
    foreach ($subcategoryOrder as $subcategoryList) {
        foreach ($subcategoryList as $subcategory) {
            $productsBySubcategory[$subcategory['id']] = fetchProductsBySubcategory([$subcategory['id']]);
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8');
    $wilaya_id = filter_var($_POST['wilaya'], FILTER_SANITIZE_NUMBER_INT);
    $total_price = 0;

    try {
        $pdo->beginTransaction();

        $chosen_parts = [];
        if (isset($_POST['chosen_parts']) && !empty($_POST['chosen_parts'])) {
            $chosen_parts = json_decode($_POST['chosen_parts'], true);
            if (!is_array($chosen_parts)) {
                $chosen_parts = [];
            }
            foreach ($chosen_parts as $part_id) {
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$part_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($product) {
                    $total_price += floatval($product['price']);
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO buildyourpc_orders (user_email, phone, address, wilaya_id, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_email, $phone, $address, $wilaya_id, $total_price]);
        $order_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT name FROM wilayas WHERE id = ?");
        $stmt->execute([$wilaya_id]);
        $wilaya_name = $stmt->fetchColumn();

        $telegram_message = "New PC Build Order (#$order_id):\n";
        $telegram_message .= "=========================\n";
        $telegram_message .= "Customer Email: $user_email\n";
        $telegram_message .= "Phone: $phone\n";
        $telegram_message .= "Address: $address\n";
        $telegram_message .= "Wilaya: $wilaya_name\n";
        $telegram_message .= "Total Price: " . number_format($total_price, 2) . " DZD\n";
        $telegram_message .= "Selected Parts:\n";

        if (!empty($chosen_parts)) {
            foreach ($chosen_parts as $part_id) {
                $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
                $stmt->execute([$part_id]);
                $part = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($part) {
                    $telegram_message .= "- {$part['name']} (" . number_format($part['price'], 2) . " DZD)\n";
                }
            }
        } else {
            $telegram_message .= "No parts selected\n";
        }

        $telegram_token = '7322742533:AAEEYMpmOGhkwuOyfU-6Y4c6UtjK09ti9vE';
        $chat_id = '-1002458122628';
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

        $pdo->commit();
        echo "<script>displayPopup('Order placed successfully!');</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Order Error: " . $e->getMessage());
        echo "<script>displayPopup('Error placing order. Please try again.');</script>";
    }
} else {
    echo "<script>displayPopup('Please submit the form.');</script>";
}

?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f0f2f5 0%, #e5e9f0 100%);
        margin: 0;
       
        color: #1a1a1a;
        line-height: 1.6;
        min-height: 100vh;
    }

    .container {
        max-width: 1400px;
        margin: 40px auto;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .categories, .contact-form {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.04),
            0 8px 16px rgba(59, 130, 246, 0.03);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        padding: 40px;
    }

    .main-title {
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

    .main-title::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100px;
        height: 3px;
        background: linear-gradient(90deg, #3b82f6, transparent);
        border-radius: 3px;
    }

    .subcategory {
        margin-bottom: 25px;
        padding: 25px;
        border-radius: 16px;
        background: rgba(248, 250, 252, 0.8);
        border: 1px solid rgba(59, 130, 246, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .subcategory:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(59, 130, 246, 0.06);
    }

    .subcategory-title {
        font-size: 1.1rem;
        font-weight: 500;
        color: #4a5568;
        transition: all 0.3s ease;
        position: relative;
        padding-left: 20px;
        cursor: pointer;
    }

    .subcategory-title::before {
        content: '→';
        position: absolute;
        left: 0;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .subcategory:hover .subcategory-title::before {
        opacity: 1;
        color: #3b82f6;
    }

    .subcategory:hover .subcategory-title {
        color: #3b82f6;
        transform: translateX(5px);
    }

    .product-list select {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.9);
        margin-top: 15px;
    }

    .product-list select:hover {
        border-color: #cbd5e1;
        background-color: #ffffff;
    }

    .product-list select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        background-color: #ffffff;
    }

    .product-details {
        display: none;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        padding: 30px;
        border-radius: 20px;
        margin-top: 20px;
        border: 1px solid rgba(59, 130, 246, 0.15);
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .product-details.active {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
        align-items: start;
    }

    .product-details img {
        width: 100%;
        height: 250px;
        object-fit: contain;
        border-radius: 15px;
        padding: 15px;
        background: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }

    .product-details img:hover {
        transform: scale(1.02);
    }

    .product-details .product-info {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 10px;
    }

    .product-info p {
        margin: 0;
        line-height: 1.6;
    }

    .product-info p:first-child { /* Name */
        font-size: 1.4rem;
        font-weight: 600;
        color: #2d3748;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
    }

    .product-info p:nth-child(2) { /* Price */
        font-size: 1.2rem;
        font-weight: 500;
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
        padding: 8px 15px;
        border-radius: 8px;
        display: inline-block;
        width: fit-content;
    }

    .product-info p:nth-child(3) { /* Description */
        color: #4a5568;
        font-size: 1rem;
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #3b82f6;
    }

    .product-info button {
        margin-top: 10px;
        padding: 12px 25px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .product-info button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    @media (max-width: 768px) {
        .product-details.active {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .product-details img {
            height: 200px;
        }

        .product-info p:first-child {
            font-size: 1.2rem;
        }

        .product-info p:nth-child(2) {
            font-size: 1.1rem;
        }
    }

    .form-style {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-style input,
    .form-style select {
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.9);
    }

    .form-style input:hover,
    .form-style select:hover {
        border-color: #cbd5e1;
        background-color: #ffffff;
    }

    .form-style input:focus,
    .form-style select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        background-color: #ffffff;
    }

    .form-style button {
        padding: 14px 28px;
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

    .form-style button::before {
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

    .form-style button:hover::before {
        left: 100%;
    }

    .form-style button:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
    }

    @media (max-width: 968px) {
        .container {
            grid-template-columns: 1fr;
            margin: 20px auto;
            padding: 0;
        }

        .categories, .contact-form {
            padding: 25px;
            border-radius: 20px;
        }

        .main-title {
            font-size: 24px;
            margin-bottom: 25px;
        }

        .subcategory {
            padding: 20px;
        }

        .product-details img {
            height: 150px;
        }

        .form-style button {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 10px;
        }

        .product-details {
            padding: 15px;
        }

        .subcategory-title {
            font-size: 1rem;
        }
    }

    .input-container {
        flex: 1;
        min-width: 300px;
        width: 100%;
    }

    .description {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.5rem;
    }

    .order-summary {
        margin-top: 2rem;
        padding: 1.5rem;
        background: rgba(248, 250, 252, 0.8);
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.1);
    }

    .order-summary h3 {
        color: #2d3748;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .total-price {
        font-size: 1.125rem;
        color: #1a1a1a;
        font-weight: 500;
    }

    .submit-button {
        width: 100%;
        padding: 14px 28px;
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
        margin-top: 1.5rem;
    }

    .submit-button::before {
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

    .submit-button:hover::before {
        left: 100%;
    }

    .submit-button:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
    }

    @media (max-width: 768px) {
        .input-container {
            min-width: 100%;
        }
        
        .form-style input,
        .form-style select {
            width: 100%;
        }
    }

    .form-group {
        margin-bottom: 25px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .form-group label {
        width: 100%;
        font-weight: 500;
        color: #4a5568;
    }

    .form-style input,
    .form-style select {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.9);
    }

    .description {
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .input-container {
        flex: 1;
        min-width: 300px;
        width: 100%;
    }

    .contact-form {
        padding: 40px;
    }

    @media (max-width: 768px) {
        .contact-form {
            padding: 25px;
        }

        .form-style input,
        .form-style select {
            padding: 14px 18px;
            font-size: 0.95rem;
        }
    }

    /* Clean Select Container */
    .select-wrapper {
        position: relative;
        width: calc(100% - 36px);
    }

    /* Simple Arrow */
    .select-wrapper::after {
        content: '›';
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%) rotate(90deg);
        color: #3b82f6;
        font-size: 20px;
        font-weight: 500;
        pointer-events: none;
        transition: all 0.2s ease;
    }

    .select-wrapper:hover::after {
        color: #2563eb;
    }

    /* Clean Select Styling */
    select#wilaya {
        width: 100%;
        padding: 14px 45px 14px 20px;
        font-size: 0.95rem;
        color: #4a5568;
        background-color: white;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    select#wilaya:hover {
        border-color: #3b82f6;
    }

    select#wilaya:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Simple Option Styling */
    select#wilaya option {
        padding: 12px 20px;
        font-size: 0.95rem;
        background-color: white;
        color: #4a5568;
    }

    select#wilaya option:checked {
        background-color: #3b82f6;
        color: white;
    }

    /* Disabled Option */
    select#wilaya option[disabled] {
        color: #94a3b8;
        font-style: italic;
    }

    /* Minimal Scrollbar */
    select#wilaya {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    select#wilaya::-webkit-scrollbar {
        width: 6px;
    }

    select#wilaya::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    select#wilaya::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    select#wilaya::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Subtle Animation */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    select#wilaya option {
        animation: fadeIn 0.2s ease;
    }

    /* Mobile Optimization */
    @media (max-width: 768px) {
        .select-wrapper {
            width: 100%;
        }

        select#wilaya {
            font-size: 16px;
            padding: 16px 45px 16px 20px;
        }
    }

    /* Add these styles to improve readability of the numbered list */
    select#wilaya option {
        padding: 12px 20px;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    select#wilaya option:not([disabled]) {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
    }
    /* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
}

.modal-content {
    background-color: #fff;
    margin: 15% auto; /* 15% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
    max-width: 500px; /* Maximum width */
    text-align: center;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
}
</style>

<div class="container">
    <div class="categories">
        <div class="main-title">EcoTech PC Builder</div>
        <?php foreach ($subcategoryOrder as $categoryName => $subcategories): ?>
            <form>
                <?php foreach ($subcategories as $subcategory): ?>
                    <div class="subcategory">
                        <div class="subcategory-title" onclick="toggleProductList('<?php echo htmlspecialchars($subcategory['id']); ?>')">
                            <?php echo htmlspecialchars($subcategory['name']); ?>
                        </div>
                        <ul id="product-list-<?php echo htmlspecialchars($subcategory['id']); ?>" class="product-list" style="display: none;">
                            <?php if (!empty($productsBySubcategory[$subcategory['id']])): ?>
                                <select onchange="showProductDetails(this, '<?php echo htmlspecialchars($subcategory['id']); ?>')">
                                    <option value="">Select a product</option>
                                    <?php foreach ($productsBySubcategory[$subcategory['id']] as $product): ?>
                                        <option value="<?php echo htmlspecialchars($product['id']); ?>" 
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                                data-price="<?php echo htmlspecialchars($product['price']); ?>   DZD" 
                                                data-description="<?php echo htmlspecialchars($product['description']); ?>" 
                                                data-image="<?php 
                                                    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = :product_id");
                                                    $stmt->execute(['product_id' => $product['id']]);
                                                    $product_image = $stmt->fetchColumn();
                                                    echo htmlspecialchars($product_image ? 'uploads/products/' . $product_image : ''); 
                                                ?>">
                                            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo htmlspecialchars($product['price']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <li>No products available in this subcategory.</li>
                            <?php endif; ?>
                        </ul>
                        <div id="product-details-<?php echo htmlspecialchars($subcategory['id']); ?>" class="product-details" style="margin-top: 20px; display: none;">
                            <h2>Product Details</h2>
                            <img id="product-image-<?php echo htmlspecialchars($subcategory['id']); ?>" src="" alt="" style="max-width: 200px; display: none;">
                            <p id="product-name-<?php echo htmlspecialchars($subcategory['id']); ?>">haha</p>
                            <p id="product-price-<?php echo htmlspecialchars($subcategory['id']); ?>"></p>
                            <p id="product-description-<?php echo htmlspecialchars($subcategory['id']); ?>"></p>
                            <button type="button" id="product-details-button-<?php echo htmlspecialchars($subcategory['id']); ?>" style="display: none;" onclick="redirectToProduct('<?php echo htmlspecialchars($subcategory['id']); ?>')">Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        <?php endforeach; ?>
    </div>

    <div class="contact-form">
        <form method="POST" action="" class="form-style" id="orderForm">
            <h2 class="main-title">EcoTech Contact Information</h2>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-container">
                    <input type="email" id="email" name="email" required style="width: calc(100% - 36px);">
                   
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <div class="input-container">
                    <input type="tel" id="phone" name="phone" required style="width: calc(100% - 36px);">
                    
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <div class="input-container">
                    <input type="text" id="address" name="address" required style="width: calc(100% - 36px);">
                    
                </div>
            </div>

            <div class="form-group">
                <label for="wilaya">Wilaya</label>
                <div class="input-container">
                    <div class="select-wrapper">
                        <select name="wilaya" id="wilaya" required>
                            <option value="" disabled selected>Choose your Wilaya</option>
                            <?php 
                            $stmt = $pdo->query("SELECT * FROM wilayas ORDER BY ID");
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . htmlspecialchars($row['id']) . '">' 
                                     . htmlspecialchars($row['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                </div>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="total-price">
                    Total Price: <span id="total-price">$0.00</span>
                </div>
                <input type="hidden" name="total_price" id="hidden-total-price" value="0">
                <input type="hidden" name="chosen_parts" id="hidden-chosen-parts" value="">
            </div>

            <button type="submit" class="submit-button">Place Order</button>
                    <!-- Popup Modal -->
<div id="orderConfirmationModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <p>Order has been sent! Please wait for confirmation or a call.</p>
    </div>
</div>
        </form>

    </div>
</div>

<script>
let selectedProducts = new Map();
let totalPrice = 0;

function toggleProductList(subcategoryId) {
    var productList = document.getElementById('product-list-' + subcategoryId);
    productList.style.display = productList.style.display === 'none' ? 'block' : 'none';
}

function showProductDetails(select, subcategoryId) {
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption.value === "") {
        if (selectedProducts.has(subcategoryId)) {
            totalPrice -= parseFloat(selectedProducts.get(subcategoryId).price);
            selectedProducts.delete(subcategoryId);
        }
        clearProductDetails(subcategoryId);
    } else {
        const productId = selectedOption.value;
        const name = selectedOption.getAttribute('data-name');
        const price = selectedOption.getAttribute('data-price');
        const description = selectedOption.getAttribute('data-description');
        const image = selectedOption.getAttribute('data-image');
        const productDetails = document.getElementById('product-details-' + subcategoryId);

        if (selectedProducts.has(subcategoryId)) {
            totalPrice -= parseFloat(selectedProducts.get(subcategoryId).price);
        }

        selectedProducts.set(subcategoryId, {
            id: productId,
            name: name,
            price: price,
            description: description
        });
        totalPrice += parseFloat(price);

        productDetails.innerHTML = `
            <img id="product-image-${subcategoryId}" src="${image}" alt="${name}">
            <div class="product-info">
                <p id="product-name-${subcategoryId}">Name: ${name}</p>
                <p id="product-price-${subcategoryId}">Price:${price}</p>
                <p id="product-description-${subcategoryId}">Description: ${description}</p>
                <button type="button" id="product-details-button-${subcategoryId}" onclick="redirectToProduct('${subcategoryId}')">Details</button>
            </div>
        `;

        productDetails.style.display = 'grid';
        productDetails.classList.add('active');
    }

    updateTotalPrice();
}

function updateTotalPrice() {
    let total = 0;
    let chosenProductIds = [];
    
    selectedProducts.forEach(product => {
        total += parseFloat(product.price);
        chosenProductIds.push(product.id);
    });
    
    document.getElementById('total-price').textContent = `${total.toFixed(2)}   DZD`;
    document.getElementById('hidden-total-price').value = total.toFixed(2);
    document.getElementById('hidden-chosen-parts').value = JSON.stringify(chosenProductIds);
    
}

document.getElementById('orderForm').addEventListener('submit', function(e) {
    if (selectedProducts.size === 0) {
        e.preventDefault();
        alert('Please select at least one product before submitting the order.');
        return false;
    }
    
});

function redirectToProduct(subcategoryId) {
    const select = document.querySelector(`#product-list-${subcategoryId} select`);
    const productId = select.value;
    if (productId) {
        window.location.href = 'product.php?id=' + productId;
    }
}

function clearProductDetails(subcategoryId) {
    const productDetails = document.getElementById('product-details-' + subcategoryId);
    const imgElement = document.getElementById('product-image-' + subcategoryId);
    imgElement.style.display = 'none';
    productDetails.style.display = 'none';
    document.getElementById('product-name-' + subcategoryId).textContent = '';
    document.getElementById('product-price-' + subcategoryId).textContent = '';
    document.getElementById('product-description-' + subcategoryId).textContent = '';
    document.getElementById('product-details-button-' + subcategoryId).style.display = 'none';
}

function resetSelections() {
    selectedProducts.clear();
    totalPrice = 0;
    updateTotalPrice();

    const productDetailsSections = document.querySelectorAll('[id^="product-details-"]');
    productDetailsSections.forEach(section => {
        section.style.display = 'none';
    });
    const productImageSections = document.querySelectorAll('[id^="product-image-"]');
    productImageSections.forEach(image => {
        image.style.display = 'none';
    });
}
// Get the modal
const modal = document.getElementById('orderConfirmationModal');

// Get the close button
const closeBtn = document.querySelector('.close');

// Function to display the modal
function displayPopup(message) {
    const modalContent = modal.querySelector('p');
    modalContent.textContent = message; // Update the message if needed
    modal.style.display = 'block';
}

// Function to close the modal
function closeModal() {
    modal.style.display = 'none';
}

// Close the modal when the close button is clicked
closeBtn.addEventListener('click', closeModal);

// Close the modal when clicking outside the modal
window.addEventListener('click', (event) => {
    if (event.target === modal) {
        closeModal();
    }
});
</script>
<?php
include 'footer.php';
?>