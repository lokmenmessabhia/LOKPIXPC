<?php
session_start();
include 'db_connect.php';

// Handle form submission for adding admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'admin'; // Default to 'admin'

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert into admins table
            $stmt_admin = $pdo->prepare("INSERT INTO admins (email, password, role) VALUES (?, ?, ?)");
            $stmt_admin->execute([$email, $hashed_password, $role]);

            // Insert into users table
            $stmt_user = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt_user->execute([$email, $hashed_password]);

            // Commit transaction
            $pdo->commit();

            $success = "Admin added successfully and also added to users table.";
        } catch (PDOException $e) {
            // Rollback transaction in case of an error
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    $admin_id = $_POST['admin_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $success = "Admin deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all admins
$stmt = $pdo->prepare("SELECT * FROM admins");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - EcoTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --success: #4cc9f0;
            --success-dark: #4895ef;
            --danger: #f72585;
            --warning: #f8961e;
            --text: #2b2d42;
            --text-light: #6c757d;
            --bg: #f8f9fa;
            --bg-card: #ffffff;
            --border: #e9ecef;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        /* Top Navigation */
        .top-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 1.75rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: var(--shadow-sm);
        }

        /* Header Styles */
        header {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        header a {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: var(--transition);
        }

        header a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Success/Error Messages */
        .success-message {
            background: linear-gradient(to right, #dcfce7, #bbf7d0);
            color: #166534;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            animation: slideIn 0.5s ease-out;
        }

        .error-message {
            background: linear-gradient(to right, #fee2e2, #fecaca);
            color: #991b1b;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            animation: slideIn 0.5s ease-out;
        }

        /* Form Styles */
        form {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        form:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        button.button {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }

        button.button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }

        button.delete {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        button.delete:hover {
            background: #e5156c;
            transform: translateY(-2px);
        }

        /* Table Styles */
        h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text);
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--bg-card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        th {
            background-color: #f8f9fa;
            color: var(--text);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(67, 97, 238, 0.05);
        }

        td.actions {
            text-align: center;
        }

        /* Footer Styles */
        footer {
            text-align: center;
            padding: 2rem;
            background-color: var(--bg-card);
            color: var(--text-light);
            border-top: 1px solid var(--border);
            margin-top: 3rem;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }
            
            form, .card {
                padding: 1.5rem;
            }
            
            th, td {
                padding: 1rem;
            }
            
            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            header a {
                width: 100%;
                justify-content: center;
            }
        }

        /* Add these styles to match dashboard navigation */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }

        .nav-brand h1 {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .nav-menu a {
            color: var(--text);
            text-decoration: none;
            padding: 0.6rem 0.9rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
            white-space: nowrap;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: var(--bg);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-end {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: max-content;
        }
        
        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            background-color: var(--bg);
        }
        
        .back-button:hover {
            background-color: var(--primary-light);
            color: white;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text);
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }
    </style>
</head>
<body>
    <!-- Replace the header with top navigation like in dashboard.php -->
    <div class="top-nav">
        <div class="nav-brand">
            <h1>EcoTech Admin</h1>
        </div>
        <div class="nav-menu">
            <a href="add_admin.php" class="active">Manage Admins</a>
            <!-- Add other menu items as needed -->
        </div>
        <div class="nav-end">
            <a href="dashboard.php" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <main class="main-content" style="margin-top: 4.5rem;">
        <h1 class="page-title">Manage Admins</h1>
        
        <?php if (isset($success)) : ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif (isset($error)) : ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="add_admin.php" method="post">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Super Admin</option>
                </select>
            </div>
            <button type="submit" name="add_admin" class="button">Add Admin</button>
        </form>

        <h2>Existing Admins (Total: <?php echo count($admins); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['id']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo htmlspecialchars($admin['role']); ?></td>
                        <td class="actions">
                            <form action="add_admin.php" method="post" style="display: inline; margin: 0; padding: 0; box-shadow: none; background: none; transform: none;">
                                <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin['id']); ?>">
                                <button type="submit" name="delete_admin" class="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>