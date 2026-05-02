@extends('layouts.app')

@php
    $active = 'reports';
    $title  = 'Reports';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">View and generate summaries for trips and fleet maintenance.</p>
        </div>
        <div class="header-actions">
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" placeholder="Find Record">
            </div>
            <div style="position:relative;">
                <button class="btn btn-filter" id="reportFilterBtn">
                    <span class="material-symbols-outlined">filter_alt</span>
                    Filter
                </button>
                <div id="reportFilterPanel" style="
                    display:none; position:absolute; right:0; top:calc(100% + 6px);
                    background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                    box-shadow:0 8px 24px rgba(0,0,0,.12); padding:14px 16px;
                    min-width:180px; z-index:500;">
                    {{-- Driver statuses --}}
                    <div id="driverFilterOptions">
                        @foreach (['Available','On-Leave','Covering','Inactive'] as $st)
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#1e293b; cursor:pointer; padding:4px 0;">
                                <input type="checkbox" class="driver-report-filter" value="{{ $st }}" checked
                                    style="accent-color:#0f1a2e; width:14px; height:14px; cursor:pointer;">
                                {{ $st }}
                            </label>
                        @endforeach
                    </div>

                    {{-- Maintenance statuses --}}
                    <div id="maintFilterOptions" style="display:none;">
                        @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#1e293b; cursor:pointer; padding:4px 0;">
                                <input type="checkbox" class="maint-report-filter" value="{{ strtolower(str_replace(['-',' '], '', $st)) }}" checked
                                    style="accent-color:#0f1a2e; width:14px; height:14px; cursor:pointer;">
                                {{ $st }}
                            </label>
                        @endforeach
                    </div>

                    <button onclick="clearReportFilters()" style="
                        margin-top:10px; width:100%; padding:6px; border-radius:6px;
                        border:1px solid #e2e8f0; background:#f8fafc; font-size:12px;
                        font-family:'Poppins',sans-serif; font-weight:600; color:#64748b;
                        cursor:pointer;">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Switcher --}}
    <div style="display:flex; gap:8px; margin-bottom:18px;">
        <button class="btn btn-primary" type="button" id="tabDriverBtn">Driver Records</button>
        <button class="btn btn-secondary" type="button" id="tabMaintenanceBtn">Maintenance Record</button>
    </div>

    {{-- Financial Summary --}}
    <div class="metric-grid metric-grid-reports">
        <div class="metric-card">
            <div class="metric-label">Total Revenue</div>
            <div class="metric-value metric-green">₱{{ number_format($totalRevenue, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Driver Expenses</div>
            <div class="metric-value metric-blue">₱{{ number_format($driverExpenses, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Maintenance Cost</div>
            <div class="metric-value metric-red">₱{{ number_format($totalMaintenanceCost, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Trip Tax ({{ number_format(($taxRate ?? 0) * 100, 0) }}%)</div>
            <div class="metric-value metric-gold">₱{{ number_format($tripTax ?? 0, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Net Profit</div>
            <div class="metric-value {{ $netProfit >= 0 ? 'metric-green' : 'metric-red' }}">
                ₱{{ number_format($netProfit, 0) }}
            </div>
        </div>
    </div>

    <div style="display:flex; gap:10px; font-size:12px; color:#64748b; margin-bottom:16px;">
        <span>Total Trucks: <strong>{{ $truckCount }}</strong></span>
        <span>•</span>
        <span>Total Trips: <strong>{{ $tripCount }}</strong></span>
    </div>

    {{-- Driver Records Section --}}
    <div id="driverRecordsSection" class="drivers-section">
        <h2 class="section-title">Driver Trip Records ({{ $driverTripRecords->count() }})</h2>
        <div class="table-container">
            <table class="drivers-table">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Latest Truck</th>
                        <th>Total Trips</th>
                        <th>Expenses</th>
                        <th>Hauling</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($driverTripRecords as $driver)
                        <tr>
                            <td>{{ $driver->full_name }}</td>
                            <td>—</td>
                            <td>{{ $driver->total_trips_count }}</td>
                            <td class="metric-red">₱0</td>
                            <td class="metric-blue">₱0</td>
                            <td>
                                <button class="action-btn">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="no-data">No driver records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Maintenance Records Section --}}
    <div id="maintenanceRecordsSection" class="drivers-section" style="display:none;">
        <h2 class="section-title">Maintenance Records ({{ $maintenanceRecords->count() }})</h2>
        <div class="table-container">
            <table class="drivers-table">
                <thead>
                    <tr>
                        <th>Truck</th>
                        <th>Issue</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maintenanceRecords as $rec)
                        <tr>
                            <td style="font-weight:600;">{{ $rec->truck->truck_code ?? '—' }}</td>
                            <td>{{ $rec->issue_description }}</td>
                            <td>{{ $rec->start_date ?? '—' }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(['-',' '],'', $rec->status)) }}">
                                    {{ $rec->status }}
                                </span>
                            </td>
                            <td>{{ $rec->notes ?? '—' }}</td>
                            <td>{{ $rec->cost !== null ? '₱'.number_format($rec->cost, 0) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="no-data">No maintenance records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // ── Filter logic ─────────────────────────────────────
        function applyReportFilters() {
            var currentTab = window._reportTab || 'driver';

            if (currentTab === 'driver') {
                const checked = Array.from(document.querySelectorAll('.driver-report-filter:checked'))
                    .map(cb => cb.value);

                fetch('/drivers/filter-status?statuses[]=' + checked.join('&statuses[]='))
                    .then(r => r.json())
                    .then(drivers => {
                        const tbody = document.querySelector('#driverRecordsSection .drivers-table tbody');
                        tbody.innerHTML = '';

                        if (!drivers.length) {
                            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No driver records found.</td></tr>';
                            return;
                        }

                        drivers.forEach(driver => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${driver.full_name}</td>
                                    <td>${driver.assigned_truck ?? '—'}</td>
                                    <td>${driver.total_trips_count ?? 0}</td>
                                    <td class="metric-red">₱0</td>
                                    <td class="metric-blue">₱0</td>
                                    <td>
                                        <button class="action-btn">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>
                                    </td>
                                </tr>`;
                        });
                    })
                    .catch(err => console.error('Filter error:', err));

            } else {
                const checked = Array.from(document.querySelectorAll('.maint-report-filter:checked'))
                    .map(cb => cb.value);

                document.querySelectorAll('#maintenanceRecordsSection .drivers-table tbody tr').forEach(row => {
                    const badge = row.querySelector('.status-badge');
                    if (!badge) { row.style.display = ''; return; }
                    const status = Array.from(badge.classList)
                        .find(c => c.startsWith('status-') && c !== 'status-badge')
                        ?.replace('status-', '') ?? '';
                    row.style.display = checked.includes(status) ? '' : 'none';
                });
            }
        }

        function clearReportFilters() {
            document.querySelectorAll('.driver-report-filter, .maint-report-filter').forEach(cb => cb.checked = true);
            applyReportFilters();
        }

        // ── Tab switching ────────────────────────────────────
        function setTab(tab) {
            window._reportTab = tab;
            var isDriver = (tab === 'driver');

            document.getElementById('driverRecordsSection').style.display      = isDriver ? '' : 'none';
            document.getElementById('maintenanceRecordsSection').style.display  = isDriver ? 'none' : '';
            document.getElementById('tabDriverBtn').className   = isDriver ? 'btn btn-primary' : 'btn btn-secondary';
            document.getElementById('tabMaintenanceBtn').className = isDriver ? 'btn btn-secondary' : 'btn btn-primary';
            document.getElementById('driverFilterOptions').style.display = isDriver ? '' : 'none';
            document.getElementById('maintFilterOptions').style.display  = isDriver ? 'none' : '';

            clearReportFilters();
        }

        document.getElementById('tabDriverBtn').addEventListener('click', function () { setTab('driver'); });
        document.getElementById('tabMaintenanceBtn').addEventListener('click', function () { setTab('maintenance'); });

        // ── Filter panel toggle ──────────────────────────────
        const filterBtn   = document.getElementById('reportFilterBtn');
        const filterPanel = document.getElementById('reportFilterPanel');

        filterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            filterPanel.style.display = filterPanel.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', (e) => {
            if (!filterPanel.contains(e.target) && e.target !== filterBtn) {
                filterPanel.style.display = 'none';
            }
        });

        document.querySelectorAll('.driver-report-filter, .maint-report-filter').forEach(cb => {
            cb.addEventListener('change', applyReportFilters);
        });

        // ── Init ─────────────────────────────────────────────
        setTab('driver');
    </script>
@endsection
