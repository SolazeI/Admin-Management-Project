<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>Login — Gerardo</title>
</head>
<body class="login-page">
    <form action="{{ url('/admin/login') }}" method="POST" class="login-form">
        @csrf

        <div class="login-logo">
            <img src="{{ asset('Images/AdminLogo.png') }}" alt="Gerardo Logo">
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
            <input id="password" type="password" name="password" class="password-input"
                placeholder="Enter Password" autocomplete="current-password" required>
            <button type="button" class="toggle-password material-symbols-outlined"
                aria-label="Show password">visibility</button>
        </div>

        <button type="submit" class="login-submit">Login</button>
    </form>

    <script>
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.toggle-password')) return;
            var btn = e.target;
            var input = btn.previousElementSibling;
            if (!input) return;
            var isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            btn.textContent = isPwd ? 'visibility_off' : 'visibility';
            btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
