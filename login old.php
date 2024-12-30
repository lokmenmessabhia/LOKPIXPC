<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

// Initialize variables
$email = '';
$password = '';
$error = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check for user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (!$user['email_verified']) {
            $error = "Please verify your email before logging in.";
        } elseif (password_verify($password, $user['password'])) {
            // User login successful
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user'; // Set role to distinguish user
            header("Location: index.php"); // Redirect to home page
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        // Check for admin
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login successful
            $_SESSION['loggedin'] = true;
            $_SESSION['admin_id'] = $admin['id']; // Corrected session variable name
            $_SESSION['role'] = 'admin'; // Set role to distinguish admin
            header("Location: dashboard.php"); // Redirect to admin dashboard
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lokpix</title>
    <link rel="stylesheet" href="stylelog.css"> <!-- Ensure this path is correct -->
    <style>
        /* Additional styling to make it similar to the signup page */
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        header {
            background-color: #007bff;
            padding: 20px;
            text-align: center;
            width: 100%;
        }

        header .logo {
            font-size: 28px;
            color: #fff;
            font-weight: bold;
        }

        .wrapper {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 400px;
            box-sizing: border-box;
            margin: 20px;
        }

        .title {
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }

        .field {
            margin-bottom: 15px;
            position: relative;
        }

        .field input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .field label {
            position: absolute;
            top: -8px;
            left: 15px;
            font-size: 14px;
            color: #888;
            background-color: #fff;
            padding: 0 5px;
        }

        .field input:focus {
            border-color: #007bff;
        }

        .field input[type="submit"] {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .field input[type="submit"]:hover {
            background-color: #0056b3;
        }


        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }

        .content {
            margin-bottom: 20px;
        }

        .checkbox {
            display: flex;
            align-items: center;
        }

        .checkbox input {
            margin-right: 10px;
        }

        .signup-link {
            text-align: center;
        }

        .signup-link a {
            color: #007bff;
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>
    <header>
        <div class="logo"><img src="logo.png"></div>
    </header>

    <main>
        <div class="wrapper">
            <div class="title">Login</div>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="login.php" method="post">
                <div class="field">
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" placeholder=" ">
                    <label for="email">Email</label>
                </div>
                <div class="field">
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label for="password">Password</label>
                </div>
                <div class="content">
                    <div class="checkbox">
                        <input type="checkbox" id="remember-me" name="remember-me">
                        <label for="remember-me">Remember me</label>
                    </div>
                </div>
                <div class="field">
                    <input type="submit" value="Login">
                </div>
                <div class="signup-link">
                    Don't have an account? <a href="sign-up.php">Sign Up</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Lokpix. All rights reserved.</p>
    </footer>
</body>
</html>
