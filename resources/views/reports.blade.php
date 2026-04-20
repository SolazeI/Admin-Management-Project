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
            <button class="btn btn-filter">
                <span class="material-symbols-outlined">filter_alt</span>
                Filter
            </button>
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
        (function () {
            var dBtn = document.getElementById('tabDriverBtn');
            var mBtn = document.getElementById('tabMaintenanceBtn');
            var dSec = document.getElementById('driverRecordsSection');
            var mSec = document.getElementById('maintenanceRecordsSection');

            function setTab(tab) {
                var isDriver = (tab === 'driver');
                dSec.style.display = isDriver ? '' : 'none';
                mSec.style.display = isDriver ? 'none' : '';
                dBtn.className = isDriver ? 'btn btn-primary' : 'btn btn-secondary';
                mBtn.className = isDriver ? 'btn btn-secondary' : 'btn btn-primary';
            }

            dBtn.addEventListener('click', function () { setTab('driver'); });
            mBtn.addEventListener('click', function () { setTab('maintenance'); });

            setTab('driver');
        })();
    </script>
@endsection
