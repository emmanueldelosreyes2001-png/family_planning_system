<?php
include 'config.php';
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, role, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $role, $hash);

        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                header("Location: index.php");
                exit;
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "Username not found.";
        }
        $stmt->close();
    } else {
        $message = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Family Planning System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 350px;
        }
        .login-container h1 {
            margin: 0 0 15px;
            color: #0f8f5f;
            text-align: center;
        }
        .login-container h2 {
            margin: 0 0 20px;
            font-size: 18px;
            color: #555;
            text-align: center;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .login-container button {
            width: 100%;
            padding: 10px 12px;
            background: #0f8f5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .login-container button:hover {
            background: #0d7a4a;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            color: #ef4444;
            font-size: 14px;
        }
        .login-container a {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #0f8f5f;
            text-decoration: none;
            font-size: 14px;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Family Planning System</h1>
        <h2>Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="message"><?php echo $message; ?></p>
        <a href="register.php">Don't have an account? Register</a>
    </div>
</body>
</html>