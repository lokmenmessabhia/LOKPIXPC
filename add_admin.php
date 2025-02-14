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
      /* Modern CSS Reset */
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

/* Header Styles */
header {
    background: var(--primary-gradient);
    color: white;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
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

/* Main Content Styles */
.main-content {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 2rem;
    animation: fadeIn 0.5s ease-out;
}

/* Form Styles */
form {
    background: var(--surface-color);
    padding: 2.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
    margin-bottom: 2.5rem;
    transition: transform 0.3s ease;
}

form:hover {
    transform: translateY(-5px);
}

.input-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 600;
    font-size: 0.95rem;
}

input, select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #f8fafc;
}

input:focus, select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    background-color: white;
}

/* Button Styles */
.button {
    background: var(--primary-gradient);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
}

.button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
}

/* Delete Button Styles */
button.delete {
    background-color: #3b82f6; /* Exact match to 'Add Admin' button */
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    font-size: 16px;
    font-weight: bold;
}

button.delete:hover {
    background-color: #2563eb; /* Darker shade on hover */
    transform: translateY(-2px);
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--surface-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
}

th, td {
    padding: 1.25rem;
    text-align: left;
}

th {
    background-color: #f1f5f9;
    font-weight: 600;
    color: var(--text-primary);
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

tr:not(:last-child) td {
    border-bottom: 1px solid #e2e8f0;
}

tr td {
    transition: all 0.3s ease;
}

tr:hover td {
    background-color: #f8fafc;
}

/* Success/Error Messages */
p[style*="color: green"] {
    background: linear-gradient(to right, #dcfce7, #bbf7d0);
    color: #166534 !important;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    animation: slideIn 0.5s ease-out;
}

p[style*="color: red"] {
    background: linear-gradient(to right, #fee2e2, #fecaca);
    color: #991b1b !important;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    animation: slideIn 0.5s ease-out;
}

/* Footer Styles */
footer {
    text-align: center;
    padding: 2.5rem;
    color: var(--text-secondary);
    background-color: var(--surface-color);
    border-top: 1px solid #e2e8f0;
    margin-top: 4rem;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    header {
        padding: 1.5rem;
    }
    
    .main-content {
        padding: 0 1rem;
    }
    
    form {
        padding: 1.5rem;
    }
    
    table {
        display: block;
        overflow-x: auto;
    }
    
    .button {
        width: 100%;
    }
}

/* Glass Morphism Effects */
.glass-effect {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

.actions {
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.actions button {
    padding: 10px 20px;
    flex: none;
    background-color: #ff3366;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    font-size: 16px;
    font-weight: bold;
}

.actions button:hover {
    background-color: #ff0033;
    transform: translateY(-2px);
}

td.actions {
    background: transparent;
    box-shadow: none;
}
    </style>
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 1rem;">
        <h1 style="font-size: 2rem;">Manage Admins</h1>
        <a href="dashboard.php" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; color: white; background: rgba(255, 255, 255, 0.1); padding: 0.5rem 1rem; border-radius: 8px; backdrop-filter: blur(10px); transition: all 0.3s ease;">
            <img src="back.png" alt="Back" style="width: 20px; height: 20px;">
            <span style="font-size: 16px;">Back to Dashboard</span>
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
                        <td class="actions">
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