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
    <title>Manage Admins - Lokpix</title>
    <link rel="stylesheet" href="add_admins.css">
    <style>
        /* Apply theme colors dynamically */
        a:hover {
            color: <?php echo htmlspecialchars($theme['hover_color']); ?>;
        }
        .button {
            background-color: <?php echo htmlspecialchars($theme['button_color']); ?>;
        }
    </style>
</head>
<body>
    <header>
        <h1>Manage Admins</h1>
        <a href="dashboard.php" style="text-decoration: none;">
            <img src="back.png" alt="Back" style="width: 30px; height: 30px; vertical-align: middle;">
            <span style="color: #fff; font-size: 18px; vertical-align: middle;">Back to Dashboard</span>
        </a>
    </header>

    <main class="main-content">
        <?php if (isset($success)) : ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php elseif (isset($error)) : ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
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
                        <td>
                            <form action="add_admin.php" method="post" style="display: inline;">
                                <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin['id']); ?>">
                                <button type="submit" name="delete_admin" class="delete button">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>
