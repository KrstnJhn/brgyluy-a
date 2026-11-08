<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Closed - Brgy. Luy-A SK</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            position: relative;
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
        body::before {
            position: fixed;
            z-index: 0;
            inset: 0;
            background: url("assets/images/sk_logo.png") center / min(100%, 500px) auto no-repeat;
            content: "";
            opacity: 0.12;
            pointer-events: none;
        }
        main {
            position: relative;
            z-index: 1;
            isolation: isolate;
            overflow: hidden;
            width: min(100%, 560px);
            padding: 3rem 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
        }
        main::before {
            position: absolute;
            z-index: -1;
            inset: 0;
            content: "";
        }
        h1 {
            margin: 0;
            color: #ffbb00;
            font-size: clamp(2.5rem, 12vw, 4rem);
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            line-height: 1.1;
        }
        p {
            margin: 1rem 0 0;
            color: #ffff;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <main>
        <h1>Registration Closed</h1>
        <p>Luy-A Online Event Registration is currently unavailable. Please check back later.</p>
    </main>
</body>
</html>
