<?php
require_once '../includes/db.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($admin = $result->fetch_assoc()) {
        if ($password === $admin['password']) {
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: index.php');
        exit;
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brgy. Luy-a Admin</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        * { box-sizing: border-box; }
        body.login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: clamp(10px, 5vw, 20px);
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .login-container {
            background: white;
            padding: clamp(1.5rem, 8vw, 2.5rem);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.6s ease-out;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: clamp(1.2rem, 5vw, 1.8rem);
            color: #333;
            font-size: clamp(1.6rem, 7vw, 2rem);
        }
        .login-container label {
            display: block;
            margin-bottom: 0.6rem;
            color: #555;
            font-weight: bold;
            font-size: clamp(1rem, 4vw, 1.1rem);
        }
        .login-container input {
            width: 100%;
            padding: clamp(12px, 5vw, 16px);
            margin-bottom: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: clamp(16px, 5vw, 18px);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .login-container input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        .login-container button {
            width: 100%;
            padding: clamp(14px, 6vw, 18px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: clamp(16px, 5vw, 18px);
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .login-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .login-container .error {
            background: #fee;
            color: #c33;
            padding: clamp(10px, 3vw, 12px);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: clamp(0.95rem, 4vw, 1.05rem);
            border: 1px solid #fcc;
        }

        /* Portrait zoom enhancements */
        @media (max-width: 480px) {
            body.login-page {
                padding: clamp(8px, 4vw, 15px);
            }
            .login-container {
                padding: clamp(1.8rem, 12vw, 3rem);
                max-width: 95vw;
            }
            .login-container h2 {
                font-size: clamp(1.9rem, 10vw, 2.4rem);
            }
        }

        @media (max-width: 360px) {
            html { font-size: 112%; }
        }

        @media (orientation: portrait) and (max-height: 680px) {
            .login-container {
                padding: clamp(2rem, 14vw, 3.2rem);
            }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Footer compatibility */
        .footer-wrapper {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            text-align: center;
            color: white;
            z-index: 10;
        }
        @media (max-width: 640px) {
            .footer-wrapper {
                position: relative;
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>

    <div class="footer-wrapper">
        <div class="footer">
            &copy; <?= date('Y') ?> Brgy. Luy-A SKystem. Developed by <a href="https://www.facebook.com/krstnjhngngra623/" target="_blank">Krstn Jhn Gngr</a>
        </div>
    </div>
</body>
</html>
