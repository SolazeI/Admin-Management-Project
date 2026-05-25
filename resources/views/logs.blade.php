@extends('layouts.app')
@php
    $active = 'logs';
    $title  = 'Activity Logs';
@endphp
@section('content')

{{-- ── Page Header ──────────────────────────────────────────────────────────── --}}
<div class="content-header app-divider">
    <div class="header-text">
        <h1 class="page-title">Activity Logs</h1>
        <p class="page-subtitle">Track every action performed — drivers, fleet, trips, maintenance, and logins.</p>
    </div>
    <div class="header-actions">

        {{-- Search --}}
        <div class="search-wrapper">
            <span class="material-symbols-outlined search-icon">search</span>
            <input class="search-input" id="logSearch"
                placeholder="Search by name, truck, action…"
                autocomplete="off"
                value="{{ request('q') }}">
        </div>

        {{-- Filter --}}
        <div style="position:relative;">
            <button class="btn btn-filter" id="logFilterBtn">
                <span class="material-symbols-outlined">filter_alt</span>
                Filter
                @if(request('subject_type') || request('action') || request('date_from') || request('date_to'))
                    <span class="filter-dot"></span>
                @endif
            </button>

            <div id="logFilterPanel" class="log-filter-panel" style="display:none;">

                <p class="filter-section-label">Module</p>
                @foreach ([
                    'driver'             => 'Drivers',
                    'truck'              => 'Fleet',
                    'trip_ticket'        => 'Trip Tickets',
                    'maintenance_record' => 'Maintenance',
                    'report_compilation' => 'Reports',
                    'admin'              => 'Admin / Auth',
                    'admin_settings'     => 'Settings',
                ] as $value => $label)
                    <label class="filter-checkbox-label">
                        <input type="checkbox" class="log-subject-filter" value="{{ $value }}" checked>
                        {{ $label }}
                    </label>
                @endforeach

                <div class="filter-divider"></div>

                <p class="filter-section-label">Action</p>
                @foreach ([
                    'created'          => 'Created',
                    'updated'          => 'Updated',
                    'deleted'          => 'Deleted',
                    'archived'         => 'Archived',
                    'restored'         => 'Restored',
                    'status_changed'   => 'Status Changed',
                    'compiled'         => 'Report Compiled',
                    'login'            => 'Login',
                    'login_failed'     => 'Login Failed',
                    'logout'           => 'Logout',
                    'password_changed' => 'Password Changed',
                ] as $value => $label)
                    <label class="filter-checkbox-label">
                        <input type="checkbox" class="log-action-filter" value="{{ $value }}" checked>
                        {{ $label }}
                    </label>
                @endforeach

                <div class="filter-divider"></div>

                <p class="filter-section-label">Date Range</p>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <div>
                        <label class="filter-date-label">From</label>
                        <input type="date" id="filterDateFrom" class="search-input filter-date-input"
                            value="{{ request('date_from') }}">
                    </div>
                    <div>
                        <label class="filter-date-label">To</label>
                        <input type="date" id="filterDateTo" class="search-input filter-date-input"
                            value="{{ request('date_to') }}">
                    </div>
                </div>

                <div style="display:flex; gap:6px; margin-top:14px;">
                    <button onclick="applyLogFilters()" class="filter-btn-apply">Apply</button>
                    <button onclick="clearLogFilters()" class="filter-btn-clear">Clear</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Logs Table ───────────────────────────────────────────────────────────── --}}
<div class="drivers-section">
    <h2 class="section-title">
        Logs
        <span class="log-count-badge">{{ $logs->total() }} total</span>
    </h2>

    <div class="table-container">
        <table class="drivers-table" id="logsTable">
            <thead>
                <tr>
                    <th style="width:150px;">Date & Time</th>
                    <th style="width:140px;">Module</th>
                    <th style="width:130px;">Action</th>
                    <th style="width:180px;">Subject</th>
                    <th>Notes</th>
                    <th style="width:110px;">IP Address</th>
                    <th style="width:70px;">Details</th>
                </tr>
            </thead>
            <tbody id="logsTableBody">
                @forelse ($logs as $log)
                    <tr class="log-row"
                        data-subject="{{ $log->subject_type }}"
                        data-action="{{ $log->action }}"
                        data-label="{{ strtolower($log->subject_label ?? '') }}"
                        data-notes="{{ strtolower($log->notes ?? '') }}">

                        <td>
                            <span class="log-date">{{ $log->logged_at?->format('M d, Y') ?? '—' }}</span>
                            <span class="log-time">{{ $log->logged_at?->format('h:i A') ?? '' }}</span>
                        </td>

                        <td>
                            <span class="module-badge module-{{ str_replace('_', '-', $log->subject_type) }}">
                                <span class="material-symbols-outlined" style="font-size:12px;">
                                    @switch($log->subject_type)
                                        @case('driver')             group @break
                                        @case('truck')              local_shipping @break
                                        @case('trip_ticket')        inventory_2 @break
                                        @case('maintenance_record') build @break
                                        @case('report_compilation') bar_chart @break
                                        @case('admin')              admin_panel_settings @break
                                        @case('admin_settings')     lock @break
                                        @default                    circle @break
                                    @endswitch
                                </span>
                                {{ ucfirst(str_replace('_', ' ', $log->subject_type ?? '—')) }}
                            </span>
                        </td>

                        <td>
                            <span class="action-badge action-{{ str_replace('_', '-', $log->action) }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>

                        <td>
                            <span class="log-subject-label">{{ $log->subject_label ?? '—' }}</span>
                            @if($log->subject_id)
                                <span class="log-subject-id">#{{ $log->subject_id }}</span>
                            @endif
                        </td>

                        <td>
                            <span class="log-notes-cell" title="{{ $log->notes }}">
                                {{ $log->notes ?? '—' }}
                            </span>
                        </td>

                        <td class="log-ip">{{ $log->ip_address ?? '—' }}</td>

                        <td>
                            @if($log->old_values || $log->new_values)
                                <button type="button" class="btn-detail" onclick="openLogDetail({{ $log->id }})">
                                    <span class="material-symbols-outlined" style="font-size:13px;">open_in_new</span>
                                    View
                                </button>
                            @else
                                <span class="log-no-detail">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="no-data">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="pagination-row">
            <p class="pagination-info">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
            </p>
            <div class="pagination-controls">
                @if($logs->onFirstPage())
                    <span class="page-btn page-btn-disabled">
                        <span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
                    </span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="page-btn">
                        <span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
                    </a>
                @endif

                @foreach($logs->getUrlRange(max(1, $logs->currentPage()-2), min($logs->lastPage(), $logs->currentPage()+2)) as $page => $url)
                    <a href="{{ $url }}" class="page-btn {{ $page === $logs->currentPage() ? 'page-btn-active' : '' }}">
                        {{ $page }}
                    </a>
                @endforeach

                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="page-btn">
                        <span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span>
                    </a>
                @else
                    <span class="page-btn page-btn-disabled">
                        <span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span>
                    </span>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- ── Log Detail Modal ─────────────────────────────────────────────────────── --}}
<div class="modal" id="logDetailModal">
    <div class="modal-content"
         style="max-width:700px; width:90%; display:flex; flex-direction:column; max-height:calc(100vh - 80px); overflow:hidden;">
        <div class="modal-header">
            <span class="material-symbols-outlined">manage_search</span>
            <h2>Log Detail</h2>
        </div>
        <div class="modal-body" id="logDetailBody"
             style="flex:1 1 auto; overflow-y:auto; overflow-x:hidden; padding:20px 24px; min-height:0;">
            <div class="log-loading-state">
                <span class="material-symbols-outlined log-spin">sync</span>
                Loading…
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closeModal('logDetailModal')">Close</button>
        </div>
    </div>
</div>

{{-- ── Styles ───────────────────────────────────────────────────────────────── --}}
<style>
/* ── Filter Panel ─────────────────────────────────────────────────────────── */
.log-filter-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 6px);
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    padding: 14px 16px;
    width: 230px;
    z-index: 500;
    max-height: min(420px, calc(100vh - 120px));
    overflow-y: auto;
    overflow-x: hidden;
}
.filter-dot {
    display: inline-block;
    width: 7px; height: 7px;
    background: #f59e0b;
    border-radius: 50%;
    margin-left: 3px;
}
.filter-section-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin: 0 0 7px;
}
.filter-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #1e293b;
    cursor: pointer;
    padding: 3px 0;
    border-radius: 4px;
    transition: color .12s;
}
.filter-checkbox-label:hover { color: #0f1a2e; }
.filter-checkbox-label input[type="checkbox"] {
    accent-color: #0f1a2e;
    width: 14px; height: 14px;
    cursor: pointer;
    flex-shrink: 0;
}
.filter-divider {
    border-top: 1px solid #f1f5f9;
    margin: 10px 0;
}
.filter-date-label {
    font-size: 11px;
    color: #64748b;
    display: block;
    margin-bottom: 2px;
}
.filter-date-input {
    width: 100% !important;
    font-size: 12px !important;
    padding: 6px 10px !important;
    box-sizing: border-box;
}
.filter-btn-apply,
.filter-btn-clear {
    flex: 1;
    padding: 7px;
    border-radius: 6px;
    font-size: 12px;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .15s;
}
.filter-btn-apply {
    border: none;
    background: #0f1a2e;
    color: #fff;
}
.filter-btn-apply:hover  { opacity: .88; }
.filter-btn-clear {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
}
.filter-btn-clear:hover  { background: #f1f5f9; }

/* ── Table cell helpers ───────────────────────────────────────────────────── */
.log-date {
    display: block;
    font-weight: 600;
    color: #1e293b;
    font-size: 13px;
}
.log-time {
    display: block;
    color: #64748b;
    font-size: 11px;
    margin-top: 1px;
}
.log-subject-label {
    font-weight: 600;
    color: #1e293b;
    font-size: 13px;
}
.log-subject-id {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 400;
    margin-left: 3px;
}
.log-notes-cell {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 220px;
    color: #64748b;
    font-size: 13px;
}
.log-ip {
    color: #94a3b8;
    font-size: 12px;
    font-family: monospace;
}
.log-no-detail {
    color: #cbd5e1;
    font-size: 12px;
}
.log-count-badge {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    margin-left: 6px;
}

/* ── "View" detail button ─────────────────────────────────────────────────── */
.btn-detail {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 4px 10px;
    font-size: 11px;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: background .12s, border-color .12s;
    white-space: nowrap;
}
.btn-detail:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
}

/* ── Module badges ────────────────────────────────────────────────────────── */
.module-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.module-driver             { background: #eff6ff; color: #1d4ed8; }
.module-truck              { background: #f0fdf4; color: #15803d; }
.module-trip-ticket        { background: #fdf4ff; color: #7e22ce; }
.module-maintenance-record { background: #fff7ed; color: #c2410c; }
.module-report-compilation { background: #f0f9ff; color: #0369a1; }
.module-admin,
.module-admin-settings     { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

/* ── Action badges ────────────────────────────────────────────────────────── */
.action-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.action-created          { background: #dcfce7; color: #166534; }
.action-updated          { background: #dbeafe; color: #1e40af; }
.action-deleted          { background: #fee2e2; color: #991b1b; }
.action-archived         { background: #fef9c3; color: #854d0e; }
.action-restored         { background: #e0f2fe; color: #075985; }
.action-status-changed   { background: #f3e8ff; color: #6b21a8; }
.action-compiled         { background: #ecfdf5; color: #065f46; }
.action-login            { background: #f0fdf4; color: #166534; }
.action-login-failed     { background: #fee2e2; color: #991b1b; }
.action-logout           { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
.action-password-changed { background: #fef3c7; color: #92400e; }

/* ── Pagination ───────────────────────────────────────────────────────────── */
.pagination-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 16px;
    padding: 0 2px;
    flex-wrap: wrap;
    gap: 10px;
}
.pagination-info {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}
.pagination-controls {
    display: flex;
    gap: 4px;
    align-items: center;
    flex-wrap: wrap;
}
.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #1e293b;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background .12s, border-color .12s;
}
.page-btn:hover      { background: #f1f5f9; }
.page-btn-active     { background: #0f1a2e !important; color: #fff !important; border-color: #0f1a2e !important; }
.page-btn-disabled   { opacity: .4; pointer-events: none; }

/* ── Log Detail Modal ─────────────────────────────────────────────────────── */
.log-loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    color: #94a3b8;
    gap: 8px;
}

/* ── Detail — meta card ───────────────────────────────────────────────────── */
.log-detail-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
    margin-bottom: 24px;
    padding: 16px 18px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.log-detail-field--full {
    grid-column: 1 / -1;
}
.log-detail-field-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin: 0 0 3px;
}
.log-detail-field-value {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    font-family: 'Poppins', sans-serif;
}
.log-detail-field-value.is-muted {
    font-weight: 400;
    color: #475569;
}

/* ── Detail — changes section ─────────────────────────────────────────────── */
.log-detail-section-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin: 0 0 8px;
}

@keyframes log-spin { to { transform: rotate(360deg); } }
.log-spin { animation: log-spin 1s linear infinite; font-size: 20px; }
</style>

<script src="{{ asset('js/logs.js') }}"></script>
@endsection