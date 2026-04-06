<aside class="sidebar">
    <header class="sidebar-header">
        <a href="{{ url('/dashboard') }}" class="header-logo">
            <img src="{{ asset('images/AdminLogo.png') }}" alt="Company Logo" class="logo">
        </a>
    </header>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="{{ url('/dashboard') }}" class="nav-link {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">home</span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ url('/admin') }}" class="nav-link {{ ($active ?? '') === 'drivers' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">group</span>
                    <span class="nav-label">Driver Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ url('/fleet') }}" class="nav-link {{ ($active ?? '') === 'fleet' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span class="nav-label">Fleet Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ url('/trips') }}" class="nav-link {{ ($active ?? '') === 'trips' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span class="nav-label">Trip Tickets</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ url('/maintenance') }}" class="nav-link {{ ($active ?? '') === 'maintenance' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">build</span>
                    <span class="nav-label">Maintenance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ url('/reports') }}" class="nav-link {{ ($active ?? '') === 'reports' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">bar_chart</span>
                    <span class="nav-label">Reports</span>
                </a>
            </li>

            <li class="nav-item" style="margin-top: 10px;">
                <a href="{{ url('/admin/password') }}" class="nav-link {{ ($active ?? '') === 'password' ? 'active' : '' }}">
                    <span class="material-symbols-outlined">lock</span>
                    <span class="nav-label">Change Password</span>
                </a>
            </li>
            <li class="nav-item">
                <form action="{{ url('/admin/logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="nav-link" style="width:100%; border:none; background:none; text-align:left; cursor:pointer;">
                        <span class="material-symbols-outlined">logout</span>
                        <span class="nav-label">Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </nav>
</aside>

