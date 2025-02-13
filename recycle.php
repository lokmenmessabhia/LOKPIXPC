<?php
session_start();
require_once 'db_connect.php';
include 'header.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['userid'])) die("Error: You must be logged in.");
    
    $user_id = $_SESSION['userid'];
    $data = [
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'subcategory_id' => $_POST['subcategory_id'] ?? '',
        'condition' => $_POST['condition'] ?? '',
        'pickup' => $_POST['pickup'] ?? ''
    ];

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $photo_name = time() . "_" . basename($_FILES['photo']['name']);
        $photo_path = $upload_dir . $photo_name;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            die("Error uploading photo.");
        }
    } else {
        die("Photo upload is required.");
    }

    try {
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO recycle_requests 
            (user_id, email, phone, category_id, subcategory_id, component_condition, photo, pickup_option)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $data['email'], $data['phone'], $data['category_id'], 
                       $data['subcategory_id'], $data['condition'], $photo_path, $data['pickup']]);

        // Send Telegram notification
        $telegram_config = [
            'bot_token' => "7322742533:AAEEYMpmOGhkwuOyfU-6Y4c6UtjK09ti9vE",
            'chat_id' => "-1002458122628"
        ];

        // Get additional exchange information
        $exchange_option = $_POST['exchange_option'] ?? 'no';
        $original_price = $_POST['original_price'] ?? '0';
        $store_component_id = $_POST['store_component'] ?? '';
        
        // Calculate trade value if exchange is selected
        $trade_value = 0;
        if ($exchange_option === 'yes' && $original_price) {
            $rates = [
                'Working' => 0.5,
                'Damaged' => 0.2,
                'Not Working' => 0.1
            ];
            $trade_value = floatval($original_price) * $rates[$data['condition']];
        }

        // Get store component details if selected
        $store_component_info = '';
        if ($store_component_id) {
            $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
            $stmt->execute([$store_component_id]);
            $component = $stmt->fetch();
            if ($component) {
                $final_price = $component['price'] - $trade_value;
                $store_component_info = "\n💱 *Selected Component:* {$component['name']}" .
                                      "\n💰 *Component Price:* \${$component['price']}" .
                                      "\n🔄 *Trade-in Value:* \${$trade_value}" .
                                      "\n💵 *Final Price:* \${$final_price}";
            }
        }

        $message = "♻️ *New Recycle Request*\n\n" .
                  "👤 *User ID:* $user_id\n" .
                  "📧 *Email:* {$data['email']}\n" .
                  "📞 *Phone:* {$data['phone']}\n" .
                  "📦 *Category ID:* {$data['category_id']}\n" .
                  "📂 *Subcategory ID:* {$data['subcategory_id']}\n" .
                  "✅ *Condition:* {$data['condition']}\n" .
                  "🚚 *Delivery Option:* {$data['pickup']}\n" .
                  "🔄 *Exchange Option:* $exchange_option" .
                  ($exchange_option === 'yes' ? "\n💰 *Original Price:* \$$original_price" : "") .
                  ($store_component_info ? $store_component_info : "");

        $photo_path_absolute = __DIR__ . '/' . $photo_path;
        if (!file_exists($photo_path_absolute)) die("Error: Uploaded photo not found.");

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.telegram.org/bot{$telegram_config['bot_token']}/sendPhoto",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $telegram_config['chat_id'],
                'photo' => new CURLFile($photo_path_absolute),
                'caption' => $message,
                'parse_mode' => 'Markdown'
            ]
        ]);

        if (!curl_exec($curl)) die("Error sending Telegram message: " . curl_error($curl));
        curl_close($curl);

        echo "<script>alert('Request submitted successfully!'); window.location.href='recycle.php';</script>";
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Fetch necessary data
try {
    $user = $pdo->query("SELECT email, phone FROM users WHERE id = {$_SESSION['userid']}")->fetch();
    $categories = $pdo->query("
        SELECT id, name 
        FROM categories 
        WHERE name IN ('PC Components', 'Networking', 'Peripherals')
        ORDER BY name
    ")->fetchAll();
    $subcategories = $pdo->query("
        SELECT s.id AS subcategory_id, s.name AS subcategory_name, 
               s.category_id, c.name AS category_name 
        FROM subcategories s 
        JOIN categories c ON s.category_id = c.id 
        WHERE c.name IN ('PC Components', 'Networking', 'Peripherals')
        ORDER BY c.name, s.name
    ")->fetchAll();
    $store_components = $pdo->query("
        SELECT p.*, c.name as category_name, s.name as subcategory_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN subcategories s ON p.subcategory_id = s.id
        WHERE c.name IN ('PC Components', 'Networking', 'Peripherals')
        ORDER BY c.name, s.name, p.name
    ")->fetchAll();
} catch (PDOException $e) {
    die("Error loading data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Your PC Components</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Improved CSS */
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; 
            background: #f4f4f4; 
            line-height: 1.6;
        }
        .container { 
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-wrapper {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        .form-section { 
            background: white; 
            padding: 30px;
            border-radius: 12px;
            flex: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .form-section h3 {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            color: #2c3e50;
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        input, select { 
            width: 100%; 
            padding: 10px 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        .submit-btn { 
            background: #28a745; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .submit-btn:hover {
            background: #218838;
        }
        .info-banner { 
            background: #fff3cd; 
            border-left: 5px solid #ffc107; 
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-banner h2 { 
            color: #856404; 
            margin-top: 0;
            font-size: 1.4rem;
        }
        .info-banner ul { 
            color: #856404; 
            margin-bottom: 0;
            padding-left: 20px;
        }
        .info-banner li {
            margin-bottom: 8px;
        }
        #exchangeDetails {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        #priceCalculation {
            margin-top: 15px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 6px;
        }
        #priceCalculation p {
            margin: 5px 0;
            font-weight: 500;
        }
        /* File input styling */
        input[type="file"] {
            padding: 8px;
            background: #f8f9fa;
        }
        /* Responsive design */
        @media (max-width: 768px) {
            .form-wrapper {
                flex-direction: column;
            }
            .form-section {
                margin-bottom: 20px;
            }
        }

        /* Popup/Modal Styles */
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
            z-index: 1000;
        }

        .popup-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .popup h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }

        .popup ul {
            margin-bottom: 20px;
            padding-left: 20px;
        }

        .popup li {
            margin-bottom: 10px;
            color: #555;
        }

        .checkbox-group {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .popup .confirm-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
        }

        .popup .cancel-btn {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .popup .confirm-btn:hover {
            background: #218838;
        }

        .popup .cancel-btn:hover {
            background: #5a6268;
        }

        .popup .form-group {
            margin-bottom: 15px;
        }

        .popup small {
            display: block;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="info-banner">
            <h2>⚠️ Important Information - Please Read</h2>
            <ul>
                <li>All items submitted for recycling will be properly disposed of or refurbished.</li>
                <li>Once submitted, items cannot be returned.</li>
                <li>Please ensure all personal data is backed up and removed from devices.</li>
                <li>Photos must clearly show the condition of the item.</li>
                <li>If choosing exchange option, trade-in values are final.</li>
            </ul>
        </div>

        <form id="recycleForm" action="recycle.php" method="POST" enctype="multipart/form-data" 
              onsubmit="return showVerificationPopup(event)">
            <div class="form-wrapper">
                <div class="form-section">
                    <h3>📝 Basic Information</h3>
                    <!-- Hidden user info -->
                    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                    
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category_id" required onchange="updateSubcategories(this.value)">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subcategory:</label>
                        <select name="subcategory_id" required>
                            <option value="">Select Subcategory</option>
                            <?php foreach ($subcategories as $sub): ?>
                                <option value="<?= $sub['subcategory_id'] ?>" 
                                        data-category="<?= $sub['category_id'] ?>">
                                    <?= htmlspecialchars($sub['subcategory_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Condition:</label>
                        <select name="condition" required>
                            <option value="Working">Working</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Not Working">Not Working</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Photo:</label>
                        <input type="file" name="photo" accept="image/*" required>
                    </div>

                    <div class="form-group">
                        <label>Delivery Option:</label>
                        <select name="pickup">
                            <option value="dropoff">Drop-off</option>
                            <option value="pickup">Request Pickup</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>💱 Exchange Options</h3>
                    <!-- Exchange section -->
                    <div class="form-group">
                        <label>Exchange Option:</label>
                        <select name="exchange_option" onchange="toggleExchange(this.value)">
                            <option value="no">No Exchange</option>
                            <option value="yes">Exchange with Store Component</option>
                        </select>
                    </div>

                    <div id="exchangeDetails" style="display:none">
                        <div class="form-group">
                            <label>Original Price:</label>
                            <input type="number" name="original_price" min="0" step="0.01" 
                                   onchange="calculatePrice()">
                        </div>

                        <div class="form-group">
                            <label>Store Component:</label>
                            <select name="store_component" onchange="calculatePrice()">
                                <option value="">Select Component</option>
                                <?php foreach ($store_components as $comp): ?>
                                    <option value="<?= $comp['id'] ?>" 
                                            data-price="<?= $comp['price'] ?>"
                                            data-subcategory="<?= $comp['subcategory_id'] ?>">
                                        <?= htmlspecialchars("{$comp['name']} - \${$comp['price']}") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="priceCalculation"></div>
                    </div>
                </div>
            </div>
            <button type="submit" class="submit-btn">Submit Request</button>
        </form>
    </div>

    <!-- Verification Popup -->
    <div id="verificationPopup" class="popup">
        <div class="popup-content">
            <h3>⚠️ Final Verification</h3>
            <p>Please confirm you understand:</p>
            <ul>
                <li>This item will be recycled and cannot be returned</li>
                <li>All personal data should be backed up and removed</li>
                <li>The trade-in value (if selected) is final</li>
                <li>Please verify your contact details below:</li>
            </ul>
            
            <div class="form-group">
                <label>Email:</label>
                <div><?= htmlspecialchars($user['email']) ?></div>
            </div>
            
            <div class="form-group">
                <label>Phone Number:</label>
                <input type="tel" id="confirmPhone" value="<?= htmlspecialchars($user['phone']) ?>" 
                       pattern="[0-9\+\-\(\)\s]+" title="Please enter a valid phone number">
                <small>You can update your phone number if needed</small>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="confirmCheck" required>
                <label for="confirmCheck">I understand and agree to proceed</label>
            </div>
            <button onclick="submitIfConfirmed()" class="confirm-btn">Confirm Submission</button>
            <button type="button" onclick="closePopup()" class="cancel-btn">Cancel</button>
        </div>
    </div>

    <script>
        // Add this function at the beginning of your script section
        function updateSubcategories(categoryId) {
            const subcategorySelect = document.querySelector('[name="subcategory_id"]');
            const options = subcategorySelect.getElementsByTagName('option');
            
            for (let option of options) {
                if (option.value === "") { // Skip the placeholder option
                    continue;
                }
                if (option.getAttribute('data-category') === categoryId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            // Reset subcategory selection
            subcategorySelect.value = '';
            
            // Also reset and hide store components when category changes
            updateStoreComponents('');
        }

        function toggleExchange(value) {
            document.getElementById('exchangeDetails').style.display = 
                value === 'yes' ? 'block' : 'none';
        }

        function calculatePrice() {
            const condition = document.querySelector('[name="condition"]').value;
            const originalPrice = parseFloat(document.querySelector('[name="original_price"]').value) || 0;
            const storeComponent = document.querySelector('[name="store_component"]');
            const storePrice = storeComponent.selectedOptions[0]?.dataset.price || 0;

            const rates = { 
                'Working': 0.5,     // 50% of original price
                'Damaged': 0.2,     // 20% of original price
                'Not Working': 0.1  // 10% of original price
            };
            const tradeValue = originalPrice * rates[condition];
            const difference = storePrice - tradeValue;

            document.getElementById('priceCalculation').innerHTML = `
                <p>Trade-in Value: $${tradeValue.toFixed(2)}</p>
                <p>Store Price: $${storePrice}</p>
                <p>Amount to Pay: $${difference.toFixed(2)}</p>
            `;
        }

        function showVerificationPopup(event) {
            event.preventDefault();
            document.getElementById('verificationPopup').style.display = 'flex';
            return false;
        }

        function closePopup() {
            document.getElementById('verificationPopup').style.display = 'none';
        }

        function submitIfConfirmed() {
            if (!document.getElementById('confirmCheck').checked) {
                alert('Please check the confirmation box to proceed');
                return;
            }
            
            // Update the hidden phone input with the new value
            const newPhone = document.getElementById('confirmPhone').value;
            if (!newPhone) {
                alert('Please provide a valid phone number');
                return;
            }
            
            // Update the hidden phone field in the main form
            document.querySelector('input[name="phone"]').value = newPhone;
            
            // Submit the form
            document.getElementById('recycleForm').submit();
        }

        // Add this new function
        function updateStoreComponents(subcategoryId) {
            const storeComponentSelect = document.querySelector('[name="store_component"]');
            const options = storeComponentSelect.getElementsByTagName('option');
            
            for (let option of options) {
                if (option.value === "") { // Skip the placeholder option
                    continue;
                }
                if (option.getAttribute('data-subcategory') === subcategoryId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            // Reset store component selection
            storeComponentSelect.value = '';
            // Clear price calculation
            document.getElementById('priceCalculation').innerHTML = '';
        }

        // Modify subcategory select to trigger store component update
        document.querySelector('[name="subcategory_id"]').addEventListener('change', function() {
            updateStoreComponents(this.value);
        });
    </script>
</body>
</html>