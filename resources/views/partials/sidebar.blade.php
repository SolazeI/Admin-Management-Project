<aside class="sidebar">
    <header class="sidebar-header">
        <a href="{{ url('/dashboard') }}" class="header-logo">
            <img src="{{ asset('Images/AdminLogo.png') }}" alt="Gerardo Logo" class="logo">
        </a>
    </header>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="{{ url('/dashboard') }}" class="nav-link {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">home</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/admin') }}" class="nav-link {{ ($active ?? '') === 'drivers' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">group</span>
                    <span>Driver Management</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/fleet') }}" class="nav-link {{ ($active ?? '') === 'fleet' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span>Fleet Management</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/trips') }}" class="nav-link {{ ($active ?? '') === 'trips' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Trip Tickets</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/maintenance') }}" class="nav-link {{ ($active ?? '') === 'maintenance' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">build</span>
                    <span>Maintenance</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/reports') }}" class="nav-link {{ ($active ?? '') === 'reports' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">bar_chart</span>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/logs') }}" class="nav-link {{ ($active ?? '') === 'logs' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">history</span>
                    <span>Logs</span>
                </a>
            </li>
            <li style="margin-top:10px;">
                <a href="{{ url('/admin/password') }}" class="nav-link {{ ($active ?? '') === 'password' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">lock</span>
                    <span>Change Password</span>
                </a>
            </li>
            <li>
                <form action="{{ url('/admin/logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="nav-link">
                        <span class="material-symbols-outlined">logout</span>
                        <span>Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </nav>
</aside>
