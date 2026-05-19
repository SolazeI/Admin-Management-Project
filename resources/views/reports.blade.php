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
                    <div id="driverFilterOptions">
                        @foreach (['Available','On-Leave','Covering','Inactive'] as $st)
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#1e293b; cursor:pointer; padding:4px 0;">
                                <input type="checkbox" class="driver-report-filter" value="{{ $st }}" checked
                                    style="accent-color:#0f1a2e; width:14px; height:14px; cursor:pointer;">
                                {{ $st }}
                            </label>
                        @endforeach
                    </div>
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
            <div class="metric-label">Completed Trips</div>
            <div class="metric-value metric-blue">{{ number_format($completedTripCount, 0) }}</div>
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
                        <th>Status</th>
                        <th>Latest Truck</th>
                        <th>Total Trips</th>
                        <th>Revenue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($driverTripRecords as $driver)
                        <tr>
                            <td>{{ $driver->full_name }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(['-',' '], '', $driver->status ?? 'inactive')) }}">
                                    {{ $driver->status ?? 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $driver->assigned_truck ?? '—' }}</td>
                            <td>{{ $driver->total_trips_count }}</td>
                            <td>{{ ($driver->total_revenue ?? 0) > 0 ? '₱' . number_format($driver->total_revenue, 0) : '—' }}</td>
                            <td>
                                <button type="button"
                                    onclick="window.open('{{ route('reports.export.driver', $driver->id) }}', '_blank')"
                                    style="font-size:12px; gap:4px; display:inline-flex; align-items:center; padding:6px 12px;
                                        background:#0f1a2e; color:#fff; border-radius:6px; border:none;
                                        font-family:'Poppins',sans-serif; font-weight:500; cursor:pointer;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">picture_as_pdf</span>
                                    Export Report
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
        <h2 class="section-title" style="display:flex; align-items:center; justify-content:space-between;">
            Maintenance Records ({{ $maintenanceRecords->count() }})
            <a id="maintenanceExportBtn"
            href="{{ route('reports.export.maintenance') }}"
            target="_blank"
            class="btn btn-secondary"
            style="font-size:12px; display:inline-flex; align-items:center; gap:6px; padding:6px 14px;">
                <span class="material-symbols-outlined" style="font-size:16px;">picture_as_pdf</span>
                Export Report
            </a>
        </h2>
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
        window.exportDriverBase      = "{{ route('reports.export.driver', '') }}";
        window.exportMaintenanceBase = "{{ route('reports.export.maintenance') }}";
    </script>
    <script src="{{ asset('js/reports.js') }}"></script>

    <div id="printContainer" style="display:none;"></div>
    <style>
        @media print {
            body > *:not(#printContainer) { display: none !important; }
            #printContainer { display: block !important; }
            @page { margin: 24mm 20mm; size: A4; }
            body { background: #fff; }
            .print-report {
                font-family: 'Poppins', sans-serif;
                color: #1e293b;
                max-width: 680px;
                margin: 0 auto;
            }
            .print-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                border-bottom: 2px solid #0f1a2e;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .print-logo {
                font-size: 18px;
                font-weight: 700;
                color: #0f1a2e;
                letter-spacing: .5px;
            }
            .print-meta {
                font-size: 11px;
                color: #64748b;
            }
            .print-title {
                font-size: 15px;
                font-weight: 600;
                color: #0f1a2e;
                margin-bottom: 16px;
                text-transform: uppercase;
                letter-spacing: .5px;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
            }
            .print-table tr:nth-child(even) { background: #f8fafc; }
            .print-table td {
                padding: 9px 12px;
                font-size: 12px;
                border-bottom: 1px solid #e2e8f0;
            }
            .print-table td.label {
                width: 38%;
                font-weight: 600;
                color: #475569;
            }
            .print-table td.value {
                color: #1e293b;
            }
            .print-footer {
                margin-top: 32px;
                font-size: 10px;
                color: #94a3b8;
                text-align: center;
                border-top: 1px solid #e2e8f0;
                padding-top: 10px;
            }
        }
    </style>
@endsection