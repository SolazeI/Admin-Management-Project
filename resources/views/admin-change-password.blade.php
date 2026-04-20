<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>Change Password — Gerardo</title>
</head>
<body class="login-page">
    <form action="{{ url('/admin/password') }}" method="POST" class="login-form">
        @csrf

        <div class="login-logo">
            <img src="{{ asset('images/AdminLogo.png') }}" alt="Gerardo Logo">
        </div>

        @if (session('error'))
            <div class="login-error">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="login-success">{{ session('success') }}</div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="login-error">{{ $errors->first() }}</div>
        @endif

        <div class="password-wrapper">
            <input type="password" name="current_password" class="password-input"
                placeholder="Current Password" autocomplete="current-password" required>
        </div>

        <div class="password-wrapper">
            <input type="password" name="new_password" class="password-input"
                placeholder="New Password (min 12 chars)" autocomplete="new-password" required>
        </div>

        <div class="password-wrapper">
            <input type="password" name="new_password_confirmation" class="password-input"
                placeholder="Confirm New Password" autocomplete="new-password" required>
        </div>

        <button type="submit" class="login-submit">Update Password</button>

        <a href="{{ url('/admin') }}" class="login-submit"
            style="background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);">
            Back to Admin
        </a>
    </form>
</body>
</html>
