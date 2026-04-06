@extends('layouts.app')

@php
    $active = 'reports';
    $title = 'Reports';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">View and generate summaries for trips and fleet maintenance.</p>
        </div>
        <div class="header-actions">
            <input class="search-input" placeholder="Find Record">
            <button class="btn btn-filter">Filter</button>
        </div>
    </div>

    <div class="fleet-tabs" style="margin-bottom:16px;">
        <button class="btn btn-secondary" type="button" id="tabDriverBtn">Driver Records</button>
        <button class="btn btn-primary" type="button" id="tabMaintenanceBtn">Maintenance Record</button>
    </div>

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
            <div class="metric-label">Total Maintenance Cost</div>
            <div class="metric-value metric-red">₱{{ number_format($totalMaintenanceCost, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Trip Tax ({{ number_format(($taxRate ?? 0) * 100, 0) }}%)</div>
            <div class="metric-value metric-gold">₱{{ number_format($tripTax ?? 0, 0) }}</div>
        </div>
    </div>

    <div class="table-container" style="background:#fff; border-radius:14px; padding:16px; margin-bottom:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <h2 class="section-title" style="margin:0;">Net Profit (after tax)</h2>
            <div style="font-size:28px; font-weight:700;">₱{{ number_format($netProfit, 0) }}</div>
        </div>
    </div>

    <div id="driverRecordsSection" class="table-container" style="background:#fff; border-radius:14px; padding:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
            <h2 class="section-title" style="margin:0;">Driver Trip Records ({{ $driverTripRecords->count() }})</h2>
            <div style="font-size:12px; color:#666;">
                Trucks: <strong>{{ $truckCount }}</strong> • Trips: <strong>{{ $tripCount }}</strong>
            </div>
        </div>
        <table class="drivers-table">
            <thead>
                <tr>
                    <th>DRIVER</th>
                    <th>TRUCK (LATEST)</th>
                    <th>TOTAL TRIPS</th>
                    <th>EXPENSES</th>
                    <th>HAULING</th>
                    <th>ACTIONS</th>
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
                        <td>...</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#777;">No records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="maintenanceRecordsSection" class="table-container" style="background:#fff; border-radius:14px; padding:16px; display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
            <h2 class="section-title" style="margin:0;">Maintenance Records ({{ $maintenanceRecords->count() }})</h2>
            <div style="font-size:12px; color:#666;">Latest 50</div>
        </div>
        <table class="drivers-table">
            <thead>
                <tr>
                    <th>TRUCK</th>
                    <th>ISSUE</th>
                    <th>START DATE</th>
                    <th>STATUS</th>
                    <th>NOTES</th>
                    <th>COST</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($maintenanceRecords as $rec)
                    <tr>
                        <td>{{ $rec->truck->truck_code ?? '—' }}</td>
                        <td>{{ $rec->issue_description }}</td>
                        <td>{{ $rec->start_date ?? '—' }}</td>
                        <td>{{ $rec->status }}</td>
                        <td>{{ $rec->notes ?? '—' }}</td>
                        <td>₱{{ $rec->cost !== null ? number_format($rec->cost, 0) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#777;">No maintenance records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        (function() {
            var driverBtn = document.getElementById('tabDriverBtn');
            var maintenanceBtn = document.getElementById('tabMaintenanceBtn');
            var driverSection = document.getElementById('driverRecordsSection');
            var maintenanceSection = document.getElementById('maintenanceRecordsSection');

            function setTab(tab) {
                var isDriver = tab === 'driver';
                if (driverSection) driverSection.style.display = isDriver ? '' : 'none';
                if (maintenanceSection) maintenanceSection.style.display = isDriver ? 'none' : '';

                if (driverBtn) {
                    driverBtn.classList.toggle('btn-primary', isDriver);
                    driverBtn.classList.toggle('btn-secondary', !isDriver);
                }
                if (maintenanceBtn) {
                    maintenanceBtn.classList.toggle('btn-primary', !isDriver);
                    maintenanceBtn.classList.toggle('btn-secondary', isDriver);
                }
            }

            if (driverBtn) driverBtn.addEventListener('click', function() { setTab('driver'); });
            if (maintenanceBtn) maintenanceBtn.addEventListener('click', function() { setTab('maintenance'); });

            setTab('driver');
        })();
    </script>
@endsection

