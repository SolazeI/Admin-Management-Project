<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>Admin - Change Password</title>
</head>

<body class="login-page">
    <form action="{{ url('/admin/password') }}" method="POST" class="login-form">
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
            <input type="password" name="current_password" class="password-input" placeholder="Current Password"
                autocomplete="current-password" required>
        </div>

        <div class="password-wrapper">
            <input type="password" name="new_password" class="password-input" placeholder="New Password (min 12 chars)"
                autocomplete="new-password" required>
        </div>

        <div class="password-wrapper">
            <input type="password" name="new_password_confirmation" class="password-input"
                placeholder="Confirm New Password" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-primary login-submit">Update Password</button>
        <a href="{{ url('/admin') }}" class="btn btn-secondary login-submit"
            style="text-align:center; display:block; margin-top:10px;">
            Back to Admin
        </a>
    </form>
</body>

</html>

