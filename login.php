<?php
session_start();
include 'db_connect.php';

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
       if (password_verify($password, $user['password'])) {
            // User login successful
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user';
            header("Location: index.php");
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
    <title>Login - EcoTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Import Poppins font */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;  /* Add Poppins as primary font */
        }

        body {
            font-family: 'Poppins', sans-serif;  /* Add Poppins as body font */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #87CEEB, #4682B4);
        }

        .wrapper {
            width: 380px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(70, 130, 180, 0.2);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            padding: 30px;
        }

        .wrapper:hover {
            transform: translateY(-5px);
        }

        .title {
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            color: #4682B4;
        }

        .field {
            margin-bottom: 20px;
            position: relative;
        }

        .field input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        .field input:focus {
            border-color: #87CEEB;
            box-shadow: 0 0 10px rgba(135, 206, 235, 0.2);
        }

        .field label {
            position: absolute;
            top: -10px;
            left: 10px;
            background: white;
            padding: 0 5px;
            font-size: 14px;
            color: #666;
        }

        .content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 15px 0;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #87CEEB, #4682B4);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
            background: linear-gradient(-135deg, #87CEEB, #4682B4);
            transform: translateY(-2px);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .signup-link a {
            color: #4682B4;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            color: #ff3333;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        input[type="checkbox"] {
            accent-color: #4682B4;
        }

        @media (max-width: 480px) {
            .wrapper {
                width: 90%;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title">Welcome Back</div>
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
</body>
</html>
