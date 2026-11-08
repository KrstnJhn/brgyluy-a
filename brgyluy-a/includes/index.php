<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Available</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            color: #24243a;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        main {
            width: min(100%, 560px);
            padding: 3rem 2rem;
            border-radius: 16px;
            background: #fff;
            text-align: center;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
        }
        h1 {
            margin: 0;
            color: #667eea;
            font-size: clamp(4rem, 18vw, 7rem);
            line-height: 1;
        }
        h2 {
            margin: 1rem 0 0.75rem;
            font-size: clamp(1.5rem, 6vw, 2rem);
        }
        p {
            margin: 0 0 1.75rem;
            color: #626275;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            padding: 0.85rem 1.5rem;
            border-radius: 8px;
            background: #667eea;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .button:hover,
        .button:focus-visible {
            background: #5267d5;
        }
    </style>
</head>
<body>
    <main>
        <h1>404</h1>
        <h2>Page Not Available</h2>
        <p>The page you are looking is unavailable. You can register using the button below.</p>
        <a class="button" href="../register.php">Go to Registration</a>
    </main>
</body>
</html>
