@extends('layouts.app')
@php
    $active = 'reports';
    $title  = 'Reports';
@endphp
@section('content')
    <div class="content-header app-divider">
        <div id="reportServerError" style="
            display:none; margin-bottom:12px; padding:12px 16px;
            background:#fef2f2; border:1px solid #fca5a5; border-radius:10px;
            align-items:flex-start; gap:10px;">
            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; flex-shrink:0; margin-top:1px;">error</span>
            <div style="flex:1;">
                <p id="reportServerErrorText" style="margin:0; font-size:13px; color:#dc2626; font-weight:600;"></p>
                <p id="reportServerErrorSub"  style="margin:4px 0 0; font-size:12px; color:#b91c1c;"></p>
            </div>
            <button onclick="hideReportServerError()" style="background:none; border:none; cursor:pointer; padding:0; color:#dc2626;">
                <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            </button>
        </div>
        <div class="header-text">
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">View and generate summaries for trips and fleet maintenance.</p>
        </div>
        <div class="header-actions">
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" id="reportSearch" placeholder="Search by name, license, truck, issue…" autocomplete="off">
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
                        @foreach (['Available','Covering'] as $st)
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

    @if (session('success'))
        <div class="notice-line" style="border-left-color:#059669; background:#f0fdf4; color:#065f46; margin-bottom:14px;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="notice-line" style="border-left-color:#dc2626; background:#fef2f2; color:#991b1b; margin-bottom:14px;">
            {{ session('error') }}
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="notice-line" style="border-left-color:#dc2626; background:#fef2f2; color:#991b1b; margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Tab Switcher --}}
    <div style="display:flex; gap:8px; margin-bottom:18px;">
        <button class="btn btn-primary" type="button" id="tabDriverBtn">Driver Records</button>
        <button class="btn btn-secondary" type="button" id="tabMaintenanceBtn">Maintenance Records</button>
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
                        <tr data-driver-id="{{ $driver->id }}">
                            <td>{{ $driver->full_name }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(['-',' '], '', $driver->status ?? 'inactive')) }}">
                                    {{ $driver->status ?? 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $driver->assigned_truck ?? '—' }}</td>
                            <td>{{ $driver->total_trips_count }}</td>
                            <td>{{ ($driver->total_revenue ?? 0) > 0 ? '₱' . number_format($driver->total_revenue, 0) : '—' }}</td>
                            <td style="display:flex; gap:6px;">
                                <button type="button"
                                    onclick="openDriverPanel({{ $driver->id }})"
                                    style="font-size:12px; gap:4px; display:inline-flex; align-items:center; padding:6px 12px;
                                        background:#f1f5f9; color:#0f1a2e; border-radius:6px; border:1px solid #e2e8f0;
                                        font-family:'Poppins',sans-serif; font-weight:500; cursor:pointer;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">visibility</span>
                                    View
                                </button>
                                <button type="button"
                                    onclick="window.open('{{ route('reports.export.driver', $driver->id) }}', '_blank')"
                                    style="font-size:12px; gap:4px; display:inline-flex; align-items:center; padding:6px 12px;
                                        background:#0f1a2e; color:#fff; border-radius:6px; border:none;
                                        font-family:'Poppins',sans-serif; font-weight:500; cursor:pointer;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">picture_as_pdf</span>
                                    Export
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="no-data">No driver records found yet.</td></tr>
                    @endforelse
                    <tr class="report-empty-state-row" style="display:none;">
                        <td colspan="6" class="no-data" id="driverEmptyStateMsg"></td>
                    </tr>
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
                        <tr data-record-id="{{ $rec->id }}">
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
                        <tr><td colspan="6" class="no-data">No maintenance records found yet.</td></tr>
                    @endforelse
                    <tr class="report-empty-state-row" style="display:none;">
                        <td colspan="6" class="no-data" id="maintenanceEmptyStateMsg"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Driver Info Modal ── --}}
    <div id="driverModalOverlay" onclick="closeDriverPanel()" style="
        display:none; position:fixed; inset:0; background:rgba(15,26,46,.5);
        z-index:900; backdrop-filter:blur(3px);"></div>

    <div id="driverModal" style="
        display:none; position:fixed; top:50%; left:50%; z-index:901;
        transform:translate(-50%, -50%);
        width:min(680px, calc(100vw - 32px));
        max-height:calc(100vh - 48px);
        background:#fff; border-radius:14px;
        box-shadow:0 20px 60px rgba(0,0,0,.22);
        flex-direction:column; overflow:hidden;">

        <div style="padding:20px 24px 16px; border-bottom:1px solid #e2e8f0;
            display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
            <div>
                <div style="font-size:15px; font-weight:700; color:#0f1a2e;" id="panelDriverName">Driver Report</div>
                <div style="font-size:11px; color:#64748b; margin-top:2px;" id="panelRefNo"></div>
            </div>
            <button onclick="closeDriverPanel()" style="
                background:none; border:none; cursor:pointer; padding:4px;
                color:#64748b; display:flex; align-items:center; border-radius:6px;">
                <span class="material-symbols-outlined" style="font-size:22px;">close</span>
            </button>
        </div>

        <div style="flex:1; overflow-y:auto; padding:20px 24px;" id="panelBody">
            <div id="panelLoader" style="display:flex; align-items:center; justify-content:center;
                height:140px; color:#64748b; font-size:13px;">
                <span class="material-symbols-outlined"
                    style="font-size:20px; margin-right:8px; animation:spin 1s linear infinite;">
                    progress_activity
                </span>
                Loading driver info…
            </div>
            <div id="panelContent" style="display:none;">
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; margin-bottom:20px;"
                    id="panelInfoCards"></div>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; margin-bottom:20px;"
                    id="panelStats"></div>
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;
                    color:#64748b; border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:12px;">
                    Trip History
                </div>
                <div id="panelTripTable"></div>
            </div>
        </div>

        <div style="padding:14px 24px; border-top:1px solid #e2e8f0;
            display:flex; gap:8px; flex-shrink:0; background:#fafafa; border-radius:0 0 14px 14px;">
            <button id="panelExportBtn" type="button" onclick="exportPanelDriver()"
                style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px;
                    padding:9px 16px; background:#0f1a2e; color:#fff; border:none; border-radius:8px;
                    font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer;">
                <span class="material-symbols-outlined" style="font-size:16px;">picture_as_pdf</span>
                Export Report
            </button>
            <button type="button" onclick="closeDriverPanel()"
                style="padding:9px 20px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;
                    border-radius:8px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer;">
                Close
            </button>
        </div>
    </div>

    <script>
        window.exportDriverBase      = "{{ route('reports.export.driver', '') }}";
        window.driverInfoBase        = "{{ route('reports.driver.info', ['driver' => '__ID__']) }}";
        window.exportMaintenanceBase = "{{ route('reports.export.maintenance') }}";
    </script>
    <script src="{{ asset('js/reports.js') }}"></script>

    <div id="printContainer" style="display:none;"></div>

    <style>
        @keyframes spin { to { transform:rotate(360deg); } }

        #driverModal { display:none; }
        #driverModal.modal-open { display:flex !important; }

        .panel-info-card {
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius:8px;
            padding:10px 12px;
        }
        .panel-info-label {
            font-size:10px;
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:.5px;
            color:#94a3b8;
            margin-bottom:3px;
        }
        .panel-info-value {
            font-size:13px;
            font-weight:600;
            color:#1e293b;
        }
        .panel-stat-card {
            background:#0f1a2e;
            border-radius:8px;
            padding:12px;
            text-align:center;
        }
        .panel-stat-label {
            font-size:10px;
            color:#94a3b8;
            text-transform:uppercase;
            letter-spacing:.5px;
            margin-bottom:4px;
        }
        .panel-stat-value {
            font-size:18px;
            font-weight:700;
            color:#fff;
        }
        .panel-trip-table {
            width:100%;
            border-collapse:collapse;
            font-size:11px;
        }
        .panel-trip-table thead tr {
            background:#f1f5f9;
        }
        .panel-trip-table thead th {
            padding:7px 8px;
            text-align:left;
            font-weight:600;
            color:#475569;
            font-size:10px;
            text-transform:uppercase;
            letter-spacing:.4px;
        }
        .panel-trip-table tbody td {
            padding:7px 8px;
            border-bottom:1px solid #f1f5f9;
            color:#1e293b;
        }
        .panel-trip-table tbody tr:last-child td { border-bottom:none; }
        .panel-trip-table tfoot td {
            padding:8px;
            font-weight:700;
            border-top:2px solid #e2e8f0;
            color:#0f1a2e;
        }

        @media print {
            body > *:not(#printContainer) { display:none !important; }
            #printContainer { display:block !important; }
            @page { margin:24mm 20mm; size:A4; }
            body { background:#fff; }
        }
    </style>
@endsection