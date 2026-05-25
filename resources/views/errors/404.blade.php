<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>404 — Page Not Found</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f0f2f6;
        }
        .error-box {
            text-align: center;
            background: #fff;
            border-radius: 14px;
            padding: 48px 56px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            border: 1px solid #e2e8f0;
            max-width: 420px;
            width: 100%;
        }
        .error-code {
            font-size: 80px;
            font-weight: 700;
            color: #0f1a2e;
            line-height: 1;
            margin-bottom: 12px;
        }
        .error-icon {
            font-size: 48px;
            color: #2563eb;
            margin-bottom: 16px;
        }
        .error-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .error-msg {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 28px;
            line-height: 1.6;
        }
        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #0f1a2e;
            color: #fff;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            transition: background .2s;
        }
        .error-btn:hover { background: #1e2d45; }
        .error-btn .material-symbols-outlined { font-size: 17px; }
    </style>
</head>
<body>
    <div class="error-box">
        <span class="material-symbols-outlined error-icon">search_off</span>
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <p class="error-msg">The page you're looking for doesn't exist or may have been moved. Check the URL and try again.</p>
        <a href="{{ url('/dashboard') }}" class="error-btn">
            <span class="material-symbols-outlined">home</span>
            Back to Dashboard
        </a>
    </div>
</body>
</html>