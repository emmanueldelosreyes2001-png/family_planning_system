<?php
include 'config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);

    if ($username && $role && $password) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, role, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $role, $hash);
            if ($stmt->execute()) {
                $success = true;
                $message = "Registration successful! Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $message = "Registration failed.";
            }
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
    <title>Register - Family Planning System</title>
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
        .register-container {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 350px;
        }
        .register-container h1 {
            margin: 0 0 15px;
            color: #0f8f5f;
            text-align: center;
        }
        .register-container h2 {
            margin: 0 0 20px;
            font-size: 18px;
            color: #555;
            text-align: center;
        }
        .register-container input[type="text"],
        .register-container input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .register-container button {
            width: 100%;
            padding: 10px 12px;
            background: #0f8f5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .register-container button:hover {
            background: #0d7a4a;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: <?php echo $success ? '#22c55e' : '#ef4444'; ?>;
        }
        .register-container a {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #0f8f5f;
            text-decoration: none;
            font-size: 14px;
        }
        .register-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Family Planning System</h1>
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="role" placeholder="Role (e.g., Admin, Nurse)" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p class="message"><?php echo $message; ?></p>
        <a href="login.php">Already have an account? Login</a>
    </div>
</body>
</html>