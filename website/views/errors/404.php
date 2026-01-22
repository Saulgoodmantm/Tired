<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | TiredOfDoinTM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0a0a0f;
            --text-primary: #e4e4e7;
            --text-secondary: #a1a1aa;
            --violet-500: #7c6bf0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .error-container {
            max-width: 500px;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 300;
            color: var(--violet-500);
            text-shadow:
                0 0 20px rgba(124, 107, 240, 0.5),
                0 0 40px rgba(124, 107, 240, 0.3);
            line-height: 1;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 1rem;
        }

        p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #6a54e0, var(--violet-500));
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(124, 107, 240, 0.4);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="/" class="btn">Go Home</a>
    </div>
</body>
</html>
