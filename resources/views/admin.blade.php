<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body class="admin-page">
    <aside class="sidebar">

        <header class="sidebar-header">
            <a href="#" class="header-logo">
                <img src="{{ asset('images/AdminLogo.png') }}" alt="Company Logo" class="logo">
            </a>
        </header>

        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class = "nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> home </span>
                        <span class="nav-label"> Dashboard </span>
                    </a>
                </li>
                <li class = "nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> person </span>
                        <span class="nav-label"> Fleet Management </span>
                    </a>
                </li>
                <li class = "nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> local_shipping </span>
                        <span class="nav-label"> Driver Management </span>
                    </a>
                </li>
                <li class = "nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> box </span>
                        <span class="nav-label"> Trip & Tickets </span>
                    </a>
                </li>
                <li class = "nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> build </span>
                        <span class="nav-label"> Maintenance </span>
                    </a>
                </li>
                <li class = "nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> assignment </span>
                        <span class="nav-label"> Reports </span>
                    </a>
                </li>
                <li class = "nav-item settings"> <!-- added 'settings' class -->
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined"> settings </span>
                        <span class="nav-label"> Settings </span>
                    </a>
                </li>
            </ul>
        </nav>
</body>

</html>
