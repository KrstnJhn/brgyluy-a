<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brgy. Luy-A SK</title>
    <link rel="icon" type="image/png" href="brgyluy-a/assets/images/sk_logo.png">
    <link rel="stylesheet" href="brgyluy-a/assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            width: 100%;
        }
        .hero {
            text-align: center;
            color: #ffbb00;
            padding: 2rem 1rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 2.5rem);
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .hero p {
            font-size: clamp(1rem, 4vw, 1.2rem);
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.95;
        }
        .portal-container {
            justify-content: center;
            min-height: auto;
            padding-bottom: 1rem;
        }
        .portal-card {
            text-align: center;
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
            color: rgba(22, 21, 21, 0.9);
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

        /* Enhanced responsive styles for portrait zoom */
        @media (max-width: 640px) {
            body { 
                flex-direction: column; 
                padding: 10px;
            }
            .hero { 
                min-width: auto;
                padding: 2.5rem 1rem;
                margin-bottom: 4rem;
            }
            .footer-wrapper {
                position: relative;
                padding: 1rem 0;
            }
        }

        @media (max-width: 480px) {
            /* Portrait zoom effect */
            .hero h1 { 
                font-size: clamp(2.2rem, 8vw, 3rem); 
            }
            .hero p { 
                font-size: clamp(1.2rem, 5vw, 1.4rem); 
            }
            .hero > div {
                gap: 1rem;
                margin-bottom: 3rem;
            }
            /* Larger images */
            .hero img {
                max-width: clamp(150px, 40vw, 220px) !important;
                height: clamp(120px, 25vh, 140px) !important;
            }
            body { padding: 8px; }
        }

        @media (orientation: portrait) and (max-height: 700px) {
            /* Extra zoom for very narrow portrait screens */
            html {
                font-size: 115%;
            }
            .hero {
                padding: 2rem 0.5rem;
            }
            .portal-container {
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
            <div style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 3rem; flex-wrap: wrap;">
                <img src="brgyluy-a/assets/images/sk_logo.png" alt="SK Logo" style="max-width: clamp(150px, 30vw, 200px); height: clamp(100px, 20vh, 120px); object-fit: cover; border-radius: 12px;">
                <img src="brgyluy-a/assets/images/luy-a_seal.png" alt="LUY-A Logo" style="max-width: clamp(150px, 30vw, 200px); height: clamp(100px, 20vh, 120px); object-fit: cover; border-radius: 12px;"> 
            </div>
            <div style="display: flex; flex-direction: column; align-items: center;">
                <p style="font-size: 0.9rem; font-weight: 400; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">Warm Greeting and Heartfelt</p>
                <h1>Welcome to Brgy. Luy-A</h1>
                <p style="font-size: 0.9rem; font-weight: 400; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">Luy-A, Tukuran, Zamboanga Del Sur</p>
            </div>
                
                <p class="message" style="color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3); margin-top: 3rem;" >
                    We are truly honored to have you with us today. 
                    Your expertise and presence, add immense prestige to our event. 
                    Thank you for accepting the challenge of being our Guest Judge, 
                    we look forward to your fair and insightful perspective!
                </p>
        </section>


        <div class="portal-container">
            <div class="portal-card" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="brgyluy-a/judge/index.php" class="btn btn-primary">ACCESS SYSTEM</a>
            </div>
        </div>

    </div>
    <div class="footer-wrapper">
        <div class="footer">
           <a href="brgyluy-a/admin/log-in.php"> DevTools.</a> &copy; <?= date('Y') ?> Brgy. Luy-A SKystem. Developed by <a href="https://www.facebook.com/krstnjhngngra623/" target="_blank">Krstn Jhn Gngr</a>
        </div>
        
    </div>

</html>
