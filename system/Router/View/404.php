<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0f17;
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        .stars {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(2px 2px at 20% 30%, #fff, transparent),
                radial-gradient(1px 1px at 60% 70%, #fff, transparent),
                radial-gradient(1px 1px at 80% 10%, #fff, transparent),
                radial-gradient(2px 2px at 40% 80%, #fff, transparent),
                radial-gradient(1px 1px at 90% 50%, #fff, transparent),
                radial-gradient(1px 1px at 10% 60%, #fff, transparent);
            background-size: 200% 200%;
            animation: drift 60s linear infinite;
            opacity: 0.6;
        }

        @keyframes drift {
            from { background-position: 0 0; }
            to { background-position: 200% 200%; }
        }

        .container {
            text-align: center;
            z-index: 1;
            padding: 2rem;
        }

        .error-code {
            font-size: clamp(8rem, 25vw, 16rem);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 50%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.05em;
            animation: glow 3s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 20px rgba(102, 126, 234, 0.3)); }
            to { filter: drop-shadow(0 0 40px rgba(245, 87, 108, 0.5)); }
        }

        .title {
            font-size: clamp(1.5rem, 4vw, 2.25rem);
            font-weight: 600;
            margin-top: 1rem;
            color: #f1f1f1;
        }

        .message {
            font-size: 1.05rem;
            color: #a0a0b0;
            margin-top: 1rem;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .home-btn {
            display: inline-block;
            margin-top: 2.5rem;
            padding: 0.85rem 2.25rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.35);
        }

        .home-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="container">
        <div class="error-code">404</div>
        <h1 class="title">Lost in space</h1>
        <p class="message">The page you're looking for has drifted off into the void. Let's get you back to familiar ground.</p>
        <a href="/" class="home-btn">Take me home</a>
    </div>
</body>
</html>
