<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Verify admin status
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$isAdmin = $stmt->fetchColumn() > 0;

if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE marketplace_items SET approved = 1 WHERE id = ?");
                $stmt->execute([$_POST['item_id']]);
                break;
            case 'reject':
                $stmt = $pdo->prepare("UPDATE marketplace_items SET approved = 0 WHERE id = ?");
                $stmt->execute([$_POST['item_id']]);
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM marketplace_items WHERE id = ?");
                $stmt->execute([$_POST['item_id']]);
                break;
        }
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$condition = isset($_GET['condition']) ? $_GET['condition'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare the base query
$query = "SELECT m.*, c.name as category_name, s.name as subcategory_name 
          FROM marketplace_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          LEFT JOIN subcategories s ON m.subcategory_id = s.id 
          WHERE 1=1";

$params = [];

// Add filters
if ($status !== 'all') {
    if ($status === 'pending') {
        $query .= " AND m.approved IS NULL";
    } else if ($status === 'approved') {
        $query .= " AND m.approved = 1";
    } else if ($status === 'rejected') {
        $query .= " AND m.approved = 0";
    }
}

if ($category !== 'all') {
    $query .= " AND m.category_id = ?";
    $params[] = $category;
}

if ($condition !== 'all') {
    $query .= " AND m.condition = ?";
    $params[] = $condition;
}

if (!empty($search)) {
    $query .= " AND (m.name LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY m.created_at DESC";

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Marketplace - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1, #3b82f6);
            --secondary-gradient: linear-gradient(135deg, #f43f5e, #ec4899);
            --surface-color: #ffffff;
            --background-color: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-primary);
            min-height: 100vh;
        }

        header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping for smaller screens */
        }

        header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        header a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        header a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .admin-container {
            max-width: 100%;
            margin: 2rem auto;
            padding: 0 1rem;
            animation: fadeIn 0.5s ease-out;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }

        .filters {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-form select,
        .filter-form input[type="text"] {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--text-primary);
            background-color: white;
            flex: 1;
            min-width: 200px;
        }

        .filter-form button {
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }

        .filter-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
        }

        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
            perspective: 1000px;
        }

        .card {
            background: var(--surface-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .card-image {
            position: relative;
            padding-top: 75%;
            overflow: hidden;
        }

        .card-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .card:hover .card-image img {
            transform: scale(1.05);
        }

        .card-content {
            padding: 1.5rem;
            position: relative;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .status-pending {
            background: rgba(234, 179, 8, 0.2);
            color: #854d0e;
        }

        .status-approved {
            background: rgba(34, 197, 94, 1);
            color: white;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 1);
            color: white;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .card-price {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1.25rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-status {
            margin-top: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        /* Modal Styles */
        .listing-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        .listing-modal-content {
            position: relative;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            margin: 2% auto;
            padding: 0;
            width: 95%;
            max-width: 1000px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow: hidden;
            animation: modalSlideIn 0.3s ease-out;
            display: grid;
            grid-template-columns: 45% 55%;
        }

        .listing-modal-image-section {
            position: relative;
            background: #000;
            height: 90vh;
            overflow: hidden;
            cursor: zoom-in;
        }

        .listing-modal-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.9;
        }

        .listing-modal-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, 
                rgba(0, 0, 0, 0.2) 0%,
                rgba(0, 0, 0, 0.4) 100%
            );
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem;
            color: white;
        }

        .listing-modal-price {
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 0.5rem;
        }

        .listing-modal-category {
            font-size: 1rem;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .listing-modal-details {
            padding: 2rem;
            overflow-y: auto;
            height: 90vh;
        }

        .listing-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .listing-modal-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
            margin-right: 2rem;
        }

        .listing-modal-close {
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
        }

        .listing-modal-close:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            transform: rotate(90deg);
        }

        .listing-modal-status {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }

        .listing-modal-status i {
            font-size: 1.25rem;
        }

        .status-badge-approved {
            background: rgba(34, 197, 94, 0.1);
            color: rgba(34, 197, 94, 1);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .status-badge-pending {
            background: rgba(234, 179, 8, 0.1);
            color: #854d0e;
            border: 1px solid rgba(234, 179, 8, 0.2);
        }

        .status-badge-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: rgba(239, 68, 68, 1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .listing-modal-info {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(99, 102, 241, 0.03);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(99, 102, 241, 0.06);
            transform: translateX(5px);
        }

        .listing-modal-description {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            line-height: 1.6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .listing-modal-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1.5rem;
            border-top: 1px solid rgba(99, 102, 241, 0.1);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-btn-approve {
            background: rgba(34, 197, 94, 1);
            color: white;
        }

        .modal-btn-approve:hover {
            background: rgba(34, 197, 94, 0.9);
            transform: translateY(-2px);
        }

        .modal-btn-reject {
            background: rgba(239, 68, 68, 1);
            color: white;
        }

        .modal-btn-reject:hover {
            background: rgba(239, 68, 68, 0.9);
            transform: translateY(-2px);
        }

        .modal-btn-delete {
            background: #374151;
            color: white;
        }

        .modal-btn-delete:hover {
            background: #1f2937;
            transform: translateY(-2px);
        }

        /* Image Preview Modal */
        .image-preview-modal {
            display: none;
            position: fixed;
            z-index: 1100;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
        }

        .image-preview-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-preview-img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 60px rgba(0, 0, 0, 0.5);
            cursor: zoom-out;
            transition: transform 0.3s ease;
        }

        .image-preview-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .image-preview-close:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: rotate(90deg);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .listing-modal-content {
                grid-template-columns: 1fr;
                max-height: 95vh;
                margin: 2.5vh auto;
            }

            .listing-modal-image-section {
                height: 40vh;
            }

            .listing-modal-details {
                height: auto;
                max-height: 55vh;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form select,
            .filter-form input[type="text"] {
                width: 100%;
            }

            .marketplace-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 1rem;">
        <h1 style="font-size: 2rem;">Manage Marketplace</h1>
        <a href="dashboard.php" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; color: white; background: rgba(255, 255, 255, 0.1); padding: 0.5rem 1rem; border-radius: 8px; backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <img src="back.png" alt="Back" style="width: 20px; height: 20px;">
            <span style="font-size: 16px;">Back to Dashboard</span>
        </a>
    </header>

    <div class="admin-container">
        <div class="filters">
            <form class="filter-form" method="GET">
                <select name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <select name="category">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="condition">
                    <option value="all">All Conditions</option>
                    <option value="New" <?php echo $condition === 'New' ? 'selected' : ''; ?>>New</option>
                    <option value="Like New" <?php echo $condition === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                    <option value="Very Good" <?php echo $condition === 'Very Good' ? 'selected' : ''; ?>>Very Good</option>
                    <option value="Good" <?php echo $condition === 'Good' ? 'selected' : ''; ?>>Good</option>
                    <option value="Acceptable" <?php echo $condition === 'Acceptable' ? 'selected' : ''; ?>>Acceptable</option>
                    <option value="For Parts" <?php echo $condition === 'For Parts' ? 'selected' : ''; ?>>For Parts</option>
                </select>

                <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
        </div>

        <div class="marketplace-grid">
            <?php foreach ($items as $item): ?>
                <div class="card" onclick="openListingModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                    <?php
                        $statusClass = '';
                        $statusText = '';
                        if ($item['approved'] === null) {
                            $statusClass = 'status-pending';
                            $statusText = 'Pending Review';
                        } elseif ($item['approved'] == 1) {
                            $statusClass = 'status-approved';
                            $statusText = 'Approved';
                        } else {
                            $statusClass = 'status-rejected';
                            $statusText = 'Rejected';
                        }
                    ?>
                    <div class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="card-info">
                            <div><?php echo htmlspecialchars($item['category_name']); ?> › <?php echo htmlspecialchars($item['subcategory_name']); ?></div>
                            <div>Condition: <?php echo htmlspecialchars($item['condition']); ?></div>
                        </div>
                        <div class="card-price">$<?php echo number_format($item['price'], 2); ?></div>
                        <div class="card-status">
                            Status: <?php echo $statusText; ?> • Click to view details
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="listingModal" class="listing-modal">
        <div class="listing-modal-content">
            <div class="listing-modal-image-section">
                <img class="listing-modal-image" src="" alt="">
                <div class="listing-modal-image-overlay">
                    <div class="listing-modal-price"></div>
                    <div class="listing-modal-category"></div>
                </div>
            </div>
            <div class="listing-modal-details">
                <div class="listing-modal-header">
                    <h2 class="listing-modal-title"></h2>
                    <span class="listing-modal-close">&times;</span>
                </div>
                <div class="listing-modal-status">
                    <i class="status-icon"></i>
                    <span class="status-text"></span>
                </div>
                <div class="listing-modal-info"></div>
                <div class="listing-modal-description"></div>
                <div class="listing-modal-actions">
                    <form method="POST" style="display: flex; gap: 1rem; width: 100%; justify-content: flex-end;">
                        <input type="hidden" name="item_id" value="">
                        <button type="submit" name="action" value="approve" class="modal-btn modal-btn-approve">
                            Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="modal-btn modal-btn-reject">
                            Reject
                        </button>
                        <button type="submit" name="action" value="delete" class="modal-btn modal-btn-delete" onclick="return confirm('Are you sure you want to delete this item?')">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="image-preview-modal">
        <div class="image-preview-content">
            <span class="image-preview-close">&times;</span>
            <img class="image-preview-img" src="" alt="">
        </div>
    </div>

    <script>
        function openListingModal(item) {
            const modal = document.getElementById('listingModal');
            modal.style.display = 'block';
            
            // Update modal content
            modal.querySelector('.listing-modal-title').textContent = item.name;
            modal.querySelector('.listing-modal-image').src = item.image_url;
            modal.querySelector('input[name="item_id"]').value = item.id;
            
            // Add click event for image preview
            modal.querySelector('.listing-modal-image-section').onclick = function() {
                openImagePreview(item.image_url);
            };
            
            // Update price and category in image overlay
            modal.querySelector('.listing-modal-price').textContent = '$' + parseFloat(item.price).toFixed(2);
            modal.querySelector('.listing-modal-category').textContent = `${item.category_name} › ${item.subcategory_name}`;
            
            // Update status badge with icon
            let statusClass = '';
            let statusText = '';
            let statusIcon = '';
            if (item.approved === 1) {
                statusClass = 'status-badge-approved';
                statusText = 'Approved';
                statusIcon = '✓';
            } else if (item.approved === 0) {
                statusClass = 'status-badge-rejected';
                statusText = 'Rejected';
                statusIcon = '×';
            } else {
                statusClass = 'status-badge-pending';
                statusText = 'Pending Review';
                statusIcon = '⋯';
            }
            const statusElement = modal.querySelector('.listing-modal-status');
            statusElement.className = 'listing-modal-status ' + statusClass;
            statusElement.querySelector('.status-icon').textContent = statusIcon;
            statusElement.querySelector('.status-text').textContent = statusText;
            
            // Update info section
            const infoHtml = `
                <div class="info-item">
                    <strong>Category:</strong> ${item.category_name} › ${item.subcategory_name}
                </div>
                <div class="info-item">
                    <strong>Condition:</strong> ${item.condition}
                </div>
                <div class="info-item">
                    <strong>Listed:</strong> ${new Date(item.created_at).toLocaleDateString()}
                </div>
            `;
            modal.querySelector('.listing-modal-info').innerHTML = infoHtml;
            
            // Update description
            modal.querySelector('.listing-modal-description').innerHTML = item.description;

            // Show/hide buttons based on status
            const approveBtn = modal.querySelector('.modal-btn-approve');
            const rejectBtn = modal.querySelector('.modal-btn-reject');
            
            if (item.approved === 1) {
                approveBtn.style.display = 'none';
                rejectBtn.style.display = 'inline-flex';
            } else if (item.approved === 0) {
                approveBtn.style.display = 'inline-flex';
                rejectBtn.style.display = 'none';
            } else {
                approveBtn.style.display = 'inline-flex';
                rejectBtn.style.display = 'inline-flex';
            }
        }

        // Image preview functionality
        function openImagePreview(imageSrc) {
            const previewModal = document.getElementById('imagePreviewModal');
            const previewImage = previewModal.querySelector('.image-preview-img');
            previewImage.src = imageSrc;
            previewModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Close image preview
        function closeImagePreview() {
            const previewModal = document.getElementById('imagePreviewModal');
            previewModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Event listeners for image preview modal
        document.addEventListener('DOMContentLoaded', function() {
            const imagePreviewModal = document.getElementById('imagePreviewModal');
            const closeButton = imagePreviewModal.querySelector('.image-preview-close');
            const previewImage = imagePreviewModal.querySelector('.image-preview-img');

            closeButton.addEventListener('click', closeImagePreview);
            previewImage.addEventListener('click', closeImagePreview);
            imagePreviewModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeImagePreview();
                }
            });
        });

        // Close listing modal when clicking the close button or outside the modal
        document.querySelector('.listing-modal-close').addEventListener('click', () => {
            document.getElementById('listingModal').style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            const modal = document.getElementById('listingModal');
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>