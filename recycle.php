<?php
session_start();
require_once 'db_connect.php';
include 'header.php';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['userid'])) {
        die("Error: You must be logged in.");
    }
    $user_id = $_SESSION['userid'];

    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $subcategory_id = $_POST['subcategory_id'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $pickup = $_POST['pickup'] ?? '';

    $photo_path = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $photo_name = time() . "_" . basename($_FILES['photo']['name']);
        $photo_path = $upload_dir . $photo_name;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            die("Error uploading photo.");
        }
    } else {
        die("Photo upload is required.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO recycle_requests (user_id, email, phone, category_id, subcategory_id, component_condition, photo, pickup_option) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $email, $phone, $category_id, $subcategory_id, $condition, $photo_path, $pickup]);

        // Send Telegram Message After Successful Insert
        // Telegram Bot Config
$telegram_bot_token = "7322742533:AAEEYMpmOGhkwuOyfU-6Y4c6UtjK09ti9vE";
$chat_id = "-1002458122628";

// Format the message
$message = "♻️ *New Recycle Request*\n\n"
    . "👤 *User ID:* $user_id\n"
    . "📧 *Email:* $email\n"
    . "📞 *Phone:* $phone\n"
    . "📦 *Category ID:* $category_id\n"
    . "📂 *Subcategory ID:* $subcategory_id\n"
    . "✅ *Condition:* $condition\n"
    . "🚚 *Pickup Option:* $pickup\n";

// Absolute path to the uploaded photo
$photo_path_absolute = __DIR__ . '/' . $photo_path;

// Ensure the file exists before sending
if (!file_exists($photo_path_absolute)) {
    die("Error: Uploaded photo not found.");
}

// Telegram API URL
$telegram_url = "https://api.telegram.org/bot$telegram_bot_token/sendPhoto";

// Prepare cURL request
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $telegram_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'chat_id' => $chat_id,
        'photo' => new CURLFile($photo_path_absolute),
        'caption' => $message,
        'parse_mode' => 'Markdown'
    ],
]);

// Execute request
$response = curl_exec($curl);
if (!$response) {
    die("Error sending Telegram message: " . curl_error($curl));
}
curl_close($curl);


        echo "<script>alert('Your request has been submitted successfully!'); window.location.href='recycle.php';</script>";
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading categories: " . $e->getMessage();
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

// Get subcategories with their categories for the dropdown
try {
    $stmt = $pdo->query("
        SELECT s.id AS subcategory_id, s.name AS subcategory_name, s.category_id, c.name AS category_name 
        FROM subcategories s 
        JOIN categories c ON s.category_id = c.id 
        ORDER BY c.name, s.name
    ");
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading subcategories: " . $e->getMessage();
}

// Prepare subcategories for JavaScript
$subcategories_json = json_encode($subcategories);

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Your PC Components</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            text-align: center;
        }
        .info, .recycle-form {
            display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 40px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
            background: white;
            padding: 20px;
            margin: 20px auto;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .recycle-form input, .recycle-form select {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .recycle-form button {
            background: #28a745;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            border-radius: 5px;
        }
        .recycle-form button:hover {
            background: #218838;
        }
        .popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.popup-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    text-align: center;
}

.popup-content h3 {
    margin-bottom: 10px;
}

.popup-content input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
}

.popup-buttons {
    margin-top: 15px;
}

.confirm-btn, .cancel-btn {
    padding: 10px 15px;
    margin: 5px;
    border: none;
    cursor: pointer;
}

.confirm-btn {
    background: green;
    color: white;
}

.cancel-btn {
    background: red;
    color: white;
}
/* Exchange Component Section Styles */
.exchange-section {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.exchange-section h2 {
    text-align: center;
    font-size: 24px;
    color: #333;
    margin-bottom: 15px;
}

.exchange-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.exchange-form label {
    font-size: 16px;
    font-weight: bold;
    color: #555;
}

.exchange-form input, 
.exchange-form select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

.exchange-form button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 12px;
    font-size: 18px;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.exchange-form button:hover {
    background-color: #0056b3;
}

.price-difference {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    margin-top: 10px;
    color: #28a745;
}

/* Responsive Design */
@media (max-width: 600px) {
    .exchange-section {
        padding: 15px;
    }

    .exchange-form input, 
    .exchange-form select {
        font-size: 14px;
    }

    .exchange-form button {
        font-size: 16px;
    }
}

    </style>
</head>
<body>
<section class="info">
    <h2>How It Works</h2>
    <p>Fill out the form below with details about your old PC components. You can choose to drop them off or request a pickup.</p>
</section>

<section class="recycle-form">
    <h2>Submit Your Component</h2>
    <form id="recycleForm" action="recycle.php" method="POST" enctype="multipart/form-data" onsubmit="return showVerificationPopup(event)">
        <label for="email">Email</label>
        <input type="hidden" name="email" id="email" value="<?= htmlspecialchars($user_email) ?>" readonly>

        <label for="phone">Phone</label>
        <input type="hidden"name="phone" id="phone" value="<?= htmlspecialchars($user_phone) ?>" required>

        <label for="category_id">Category:</label>
       <?php $recyclable_categories = [
    "PC Components",
    "Peripherals",
    "Networking",
    "Tools"
];
?>
<select id="category_id" name="category_id" required>
    <option value="">Select a category</option>
    <?php foreach ($categories as $category): 
        if (in_array($category['name'], $recyclable_categories)): ?>
            <option value="<?php echo $category['id']; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </option>
    <?php endif; endforeach; ?>
</select>

        <label for="subcategory_id">Subcategory:</label>
        <select id="subcategory_id" name="subcategory_id" required>
            <option value="">Select a subcategory</option>
        </select>
        <label for="condition">Condition:</label>
        <select id="condition" name="condition" required>
        <option value="Working">Working</option>
    <option value="Damaged">Damaged</option>
    <option value="Not Working">Not Working</option>
        </select>

        <label for="photo">Upload Photo:</label>
        <input type="file" id="photo" name="photo" accept="image/*" required>

        <label for="pickup">Pickup or Drop-off:</label>
        <select id="pickup" name="pickup">
            <option value="dropoff">Drop-off</option>
            <option value="pickup">Request Pickup</option>
        </select>

        <button type="submit">Submit</button>
    </form>
</section>

<!-- Popup for verification -->
<div id="verificationPopup" class="popup">
    <div class="popup-content">
        <h3>Email & Phone Verification</h3>
        <p>Please verify your email and phone number before submitting.</p>

        <!-- Email Field (Disabled) -->
        <label for="popupEmail">Email:</label>
        <input type="email" id="popupEmail" value="<?= htmlspecialchars($user_email) ?>" readonly>

        <!-- Phone Field (Editable) -->
        <label for="popupPhone">Phone:</label>
        <input type="text" id="popupPhone" name="popupPhone" value="<?= htmlspecialchars($user_phone) ?>" required>

        <!-- Buttons -->
        <div class="popup-buttons">
            <button class="confirm-btn" onclick="submitForm()">Confirm</button>
            <button class="cancel-btn" onclick="closePopup()">Cancel</button>
        </div>
    </div>
</div>


<script>
    const subcategories = <?php echo $subcategories_json; ?>;

    document.getElementById('category_id').addEventListener('change', function() {
        const selectedCategoryId = this.value;
        const subcategorySelect = document.getElementById('subcategory_id');
        subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';

        subcategories.forEach(subcategory => {
            if (subcategory.category_id == selectedCategoryId) {
                const option = document.createElement('option');
                option.value = subcategory.subcategory_id;
                option.textContent = subcategory.category_name + ' - ' + subcategory.subcategory_name;
                subcategorySelect.appendChild(option);
            }
        });
    });

    function showVerificationPopup(event) {
    event.preventDefault();
    
    // Show the popup
    document.getElementById('verificationPopup').style.display = 'flex';

    // Copy the phone number from the main form to the popup input
    let phoneField = document.getElementById('phone');
    let popupPhoneField = document.getElementById('popupPhone');

    if (phoneField && popupPhoneField) {
        popupPhoneField.value = phoneField.value;
    }
}

function submitForm() {
    // Copy the updated phone number from the popup back to the main form
    let popupPhoneField = document.getElementById('popupPhone');
    let phoneField = document.getElementById('phone');

    if (popupPhoneField && phoneField) {
        phoneField.value = popupPhoneField.value;
    }

    // Submit the form
    document.getElementById('recycleForm').submit();
}

function closePopup() {
    // Hide the popup
    document.getElementById('verificationPopup').style.display = 'none';
}

</script>

</body>
</html>