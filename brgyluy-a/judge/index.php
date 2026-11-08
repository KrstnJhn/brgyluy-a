<?php
require_once '../includes/db.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password, full_name FROM judges WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Plaintext comparison (as requested)
        if ($password === $row['password']) {
            $_SESSION['judge_id'] = $row['id'];
            $_SESSION['judge_name'] = $row['full_name'];
            header('Location: dashboard2.php');
            exit;
        }
    }
    $error = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Login</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
</xai:function_call name="edit_file">
<parameter name="path">judge/login.php
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: clamp(1.2rem, 4vw, 1.5rem);
            color: #333;
            font-size: clamp(1.5rem, 6vw, 1.8rem);
        }
        .error {
            color: #e74c3c;
            background: #fadbd8;
            padding: clamp(8px, 2vw, 10px);
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: clamp(0.9rem, 3.5vw, 1rem);
        }
        .form-group {
            margin-bottom: clamp(0.8rem, 3vw, 1rem);
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: bold;
            font-size: clamp(0.9rem, 4vw, 1.1rem);
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: clamp(10px, 4vw, 14px);
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: clamp(16px, 5vw, 18px);
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: clamp(12px, 5vw, 16px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: clamp(16px, 5vw, 18px);
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        /* Enhanced responsive styles for portrait zoom */
        @media (max-width: 640px) {
            body {
                padding: clamp(5px, 3vw, 10px);
                flex-direction: column;
            }
            .login-container {
                margin-bottom: 2rem;
                max-width: 95vw;
            }
            .footer-wrapper {
                position: relative !important;
                padding: 1rem 1rem;
                margin-top: auto;
                width: 100%;
                order: 2;
            }
            body > * {
                flex-shrink: 0;
            }
        }

        @media (max-width: 480px) {
            /* Portrait zoom effect */
            .login-container {
                margin: 10px;
                padding: clamp(1.5rem, 8vw, 2.5rem);
            }
            h2 {
                font-size: clamp(1.8rem, 9vw, 2.2rem);
            }
        }

        @media (max-width: 360px) {
            /* Extra zoom for very small portrait */
            html {
                font-size: 110%;
            }
        }

        @media (orientation: portrait) and (max-height: 700px) {
            .login-container {
                padding: clamp(1.8rem, 10vw, 2.8rem);
            }
        }

        .footer-wrapper {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 0.4rem 0;
            text-align: center;
            color: white;
        }
        .footer {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer a {
                color: #ffbb00;
                text-decoration: none;
                font-weight: 600;
            }
            .footer a:hover {
                text-decoration: underline;
            }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: clamp(8px, 3vw, 12px);
            top: 75%;
            transform: translateY(-50%);
            font-size: clamp(22px, 8vw, 30px);
            background: none;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
            z-index: 2;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Welcome, Judge!</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div style="position: relative;">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required style="padding-right: 40px;">
                <span class="password-toggle" onclick="togglePassword('password')" title="Show password">👁️</span>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
    
    <div class="footer-wrapper">
        <div class="footer">
            &copy; <?= date('Y') ?> Brgy. Luy-A SKystem. Developed by <a href="https://www.facebook.com/krstnjhngngra623/" target="_blank">Krstn Jhn Gngr</a>
        </div>
    </div>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = passwordInput.nextElementSibling;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
        toggleIcon.title = 'Hide password';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
        toggleIcon.title = 'Show password';
    }
}
</script>
</body>

</html>
