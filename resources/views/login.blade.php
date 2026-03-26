<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>Admin Login</title>
</head>

<body class="login-page">
    <form action="{{ url('/admin/login') }}" method="POST" class="login-form">
        @csrf
        <div class="login-logo">
            <img src="{{ asset('images/AdminLogo.png') }}" alt="Company Logo" class="logo">
        </div>

        @if (session('error'))
            <div class="login-error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="login-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="login-error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="password-wrapper">
            <input id="password" type="password" name="password" class="password-input" placeholder="Enter Password"
                autocomplete="current-password" required>
            <button type="button" class="toggle-password material-symbols-outlined"
                aria-label="Show password">visibility</button>
        </div>

        <button type="submit" class="btn btn-primary login-submit" style="display:block; margin:0 auto;">
            Login
        </button>

        <script>
            document.addEventListener('click', function(e) {
                if (!e.target.matches('.toggle-password')) return;
                var btn = e.target;
                var input = btn.previousElementSibling;
                var isPwd = input && input.type === 'password';
                if (!input) return;
                input.type = isPwd ? 'text' : 'password';
                btn.textContent = isPwd ? 'visibility_off' : 'visibility';
                btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
            });
        </script>
    </form>

</body>

</html>

