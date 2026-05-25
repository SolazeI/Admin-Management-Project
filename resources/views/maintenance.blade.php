@extends('layouts.app')

@php
    $active = 'maintenance';
    $title  = 'Maintenance';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Maintenance</h1>
            <p class="page-subtitle">Manage weekly/monthly maintenance entries and view summaries.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" id="archivedMaintenanceBtn">
                <span class="material-symbols-outlined">folder</span>
                Archived
            </button>
            <button type="button" class="btn btn-primary" id="addMaintenanceBtn">
                <span class="material-symbols-outlined">add</span>
                Add Maintenance Record
            </button>
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" id="maintSearch" placeholder="Search by truck, issue, notes" autocomplete="off">
            </div>
            <div style="position:relative;">
                <button class="btn btn-filter" id="maintFilterBtn">
                    <span class="material-symbols-outlined">filter_alt</span>
                    Filter
                </button>
                <div id="maintFilterPanel" style="
                    display:none; position:absolute; right:0; top:calc(100% + 6px);
                    background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                    box-shadow:0 8px 24px rgba(0,0,0,.12); padding:14px 16px;
                    min-width:180px; z-index:500;">
                    @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#1e293b; cursor:pointer; padding:4px 0;">
                            <input type="checkbox" class="maint-status-filter" value="{{ strtolower(str_replace(['-',' '], '', $st)) }}" checked
                                style="accent-color:#0f1a2e; width:14px; height:14px; cursor:pointer;">
                            {{ $st }}
                        </label>
                    @endforeach
                    <button onclick="clearMaintFilters()" style="
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

    <div id="maintServerError" style="
        display:none; margin-bottom:12px; padding:12px 16px;
        background:#fef2f2; border:1px solid #fca5a5; border-radius:10px;
        align-items:flex-start; gap:10px;">
        <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; flex-shrink:0; margin-top:1px;">error</span>
        <div style="flex:1;">
            <p id="maintServerErrorText" style="margin:0; font-size:13px; color:#dc2626; font-weight:600;"></p>
            <p id="maintServerErrorSub"  style="margin:4px 0 0; font-size:12px; color:#b91c1c;"></p>
        </div>
        <button onclick="hideMaintServerError()" style="background:none; border:none; cursor:pointer; padding:0; color:#dc2626;">
            <span class="material-symbols-outlined" style="font-size:18px;">close</span>
        </button>
    </div>

    {{-- ── Records Table ─────────────────────────────────────────────── --}}
    <div class="drivers-section">
        <h2 class="section-title">Maintenance Records ({{ $records->count() }})</h2>
        <div class="table-container">
            <table class="drivers-table">
                <thead>
                    <tr>
                        <th>Truck</th>
                        <th>Issue Description</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Cost</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $rec)
                        <tr data-maint-id="{{ $rec->id }}"
                            data-status="{{ strtolower(str_replace(['-',' '], '', $rec->status)) }}">
                            <td style="font-weight:600;">{{ $rec->truck->truck_code ?? '—' }}</td>
                            <td>{{ $rec->issue_description }}</td>
                            <td>{{ $rec->start_date ?? '—' }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(['-',' '],'', $rec->status)) }}">
                                    {{ $rec->status }}
                                </span>
                            </td>
                            <td>{{ $rec->notes ?? '—' }}</td>
                            <td>{{ $rec->cost ? '₱'.number_format($rec->cost,2) : '—' }}</td>
                            <td style="position:relative;">
                                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">

                                    {{-- Status transition buttons --}}
                                    @if ($rec->status === 'Pending')
                                        <form action="{{ url('/maintenance/'.$rec->id.'/transition') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="status" value="In-Progress">
                                            <button type="submit" class="btn btn-primary" style="padding:5px 11px; font-size:12px; gap:4px;">
                                                <span class="material-symbols-outlined" style="font-size:14px;">play_arrow</span>
                                                Start
                                            </button>
                                        </form>
                                        <form action="{{ url('/maintenance/'.$rec->id.'/transition') }}" method="POST" style="display:inline;"
                                            onsubmit="return confirm('Cancel this maintenance record?')">
                                            @csrf
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn btn-cancel" style="padding:5px 11px; font-size:12px;">
                                                Cancel
                                            </button>
                                        </form>
                                    @elseif ($rec->status === 'In-Progress')
                                        <form action="{{ url('/maintenance/'.$rec->id.'/transition') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="status" value="Completed">
                                            <button type="submit" class="btn btn-primary" style="padding:5px 11px; font-size:12px; gap:4px; background:#059669;">
                                                <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span>
                                                Complete
                                            </button>
                                        </form>
                                        <form action="{{ url('/maintenance/'.$rec->id.'/transition') }}" method="POST" style="display:inline;"
                                            onsubmit="return confirm('Cancel this maintenance record?')">
                                            @csrf
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn btn-cancel" style="padding:5px 11px; font-size:12px;">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Edit (active records only) --}}
                                        <button type="button" class="btn btn-secondary"
                                            style="padding:5px 11px; font-size:12px; gap:4px;"
                                            onclick="openEditMaint(
                                                {{ $rec->id }},
                                                {{ $rec->truck_id }},
                                                '{{ $rec->start_date ?? '' }}',
                                                '{{ addslashes($rec->issue_description) }}',
                                                '{{ addslashes($rec->notes ?? '') }}',
                                                '{{ $rec->cost ?? '' }}'
                                            )">
                                            <span class="material-symbols-outlined" style="font-size:14px;">edit</span>
                                            Edit
                                        </button>
                                        <button type="button" class="btn btn-secondary" style="font-size:12px; padding:5px 11px; gap:4px;"
                                            onclick="confirmArchiveMaint({{ $rec->id }}, '{{ addslashes($rec->issue_description) }}')">
                                            <span class="material-symbols-outlined" style="font-size:14px;">archive</span>
                                            Archive
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-secondary" style="font-size:12px; padding:5px 11px; gap:4px;"
                                            onclick="confirmArchiveMaint({{ $rec->id }}, '{{ addslashes($rec->issue_description) }}')">
                                            <span class="material-symbols-outlined" style="font-size:14px;">archive</span>
                                            Archive
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="no-data">No maintenance records yet.</td></tr>
                    @endforelse
                    <tr id="maintEmptyStateRow" style="display:none;">
                        <td colspan="7" class="no-data" id="maintEmptyStateMsg">No results found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── ADD MAINTENANCE MODAL ─────────────────────────────────────── --}}
    <div class="modal" id="addMaintenanceModal">
        <div class="modal-content" style="max-width:680px;">
            <div class="modal-header">
                <span class="material-symbols-outlined">build</span>
                <h2>Add Maintenance Record</h2>
            </div>
            <form id="addMaintenanceForm" action="{{ url('/maintenance') }}" method="POST" novalidate>
                @csrf
                <div class="modal-body">
                    <p style="font-size:12px; color:#64748b; margin-bottom:14px; background:#f8fafc; border-radius:7px; padding:8px 12px; border:1px solid #e2e8f0;">
                        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; color:#d97706;">info</span>
                        New records start as <strong>Pending</strong>. Use the <strong>Start</strong> button on the table to begin work.
                    </p>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Truck <span class="required">*</span></label>
                            <select name="truck_id">
                                <option value="">Select Truck</option>
                                @foreach ($trucks as $truck)
                                    <option value="{{ $truck->id }}">{{ $truck->truck_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Issue Description <span class="required">*</span></label>
                        <input type="text" name="issue_description" placeholder="Describe the issue">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cost</label>
                            <input type="text" name="cost" placeholder="₱ Cost (optional)">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="notes" placeholder="Notes (optional)">
                        </div>
                    </div>

                    {{-- Error Box --}}
                    <div id="addMaintError" style="
                        display:none; margin-top:14px; padding:12px 14px;
                        background:#fef2f2; border:1px solid #fca5a5; border-radius:8px;">
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                            <ul id="addMaintErrorList" style="
                                margin:0; padding:0; list-style:none;
                                font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeMaintenanceModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">add</span>
                        Add Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── ARCHIVED MAINTENANCE MODAL ────────────────────────────────── --}}
    <div class="modal" id="archivedMaintenanceModal">
        <div class="modal-content archived-modal">
            <div class="modal-header">
                <h2>Archived Maintenance Records</h2>
                <div class="search-wrapper">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" class="search-input" id="archivedMaintSearch" placeholder="Search archived records">
                </div>
            </div>
            <div class="modal-body" style="padding:0;">
                <div style="overflow-x:auto; padding:0 24px 24px;">
                    <table class="archived-table">
                        <thead>
                            <tr>
                                <th>Truck</th>
                                <th>Issue Description</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedMaintTableBody">
                            @forelse ($archivedRecords as $archived)
                                <tr>
                                    <td style="font-weight:600;">{{ $archived->truck->truck_code ?? '—' }}</td>
                                    <td>{{ $archived->issue_description }}</td>
                                    <td>{{ $archived->start_date ?? '—' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower(str_replace(['-',' '],'', $archived->status)) }}">
                                            {{ $archived->status }}
                                        </span>
                                    </td>
                                    <td>{{ $archived->cost ? '₱'.number_format($archived->cost,2) : '—' }}</td>
                                    <td>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <form action="{{ url('/maintenance/'.$archived->id.'/unarchive') }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="font-size:12px; padding:5px 11px; gap:4px;">
                                                    <span class="material-symbols-outlined" style="font-size:14px;">restore</span>
                                                    Restore
                                                </button>
                                            </form>
                                            <span style="display:inline-block; width:1px; height:22px; background:#e2e8f0; margin:0 4px;"></span>
                                            <button type="button"
                                                style="
                                                    display:inline-flex; align-items:center; gap:4px;
                                                    font-size:12px; padding:5px 11px; border-radius:6px;
                                                    border:1px solid #fca5a5; background:#fff5f5;
                                                    color:#dc2626; font-family:'Poppins',sans-serif;
                                                    font-weight:600; cursor:pointer; line-height:1;"
                                                onclick="confirmDeleteMaint({{ $archived->id }}, '{{ addslashes($archived->issue_description) }}')">
                                                <span class="material-symbols-outlined" style="font-size:14px;">delete_forever</span>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="no-data">No archived maintenance records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('archivedMaintenanceModal')">Close</button>
            </div>
        </div>
    </div>

    {{-- ── ARCHIVE WARNING MODALS ────────────────────────────────────── --}}
    <div class="modal" id="maintArchiveWarning1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">archive</span>
                <h2>Archive Maintenance Record</h2>
            </div>
            <div class="modal-body">
                <p><strong>Issue:</strong> <span id="archiveMaintLabel"></span></p>
                <p>This record will be moved to the archive. You can restore it later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('maintArchiveWarning1')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedToMaintArchivePassword()">Yes, Archive</button>
            </div>
        </div>
    </div>

    <div class="modal" id="maintArchiveWarning2">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">lock</span>
                <h2>Confirm with Password</h2>
            </div>
            <div class="modal-body">
                <p>Enter admin password to archive this record.</p>
                <input type="password" class="password-input" id="maintArchivePassword" placeholder="Admin password">
                <p id="maintArchivePasswordError" style="
                    display:none; margin-top:8px; font-size:13px; color:#dc2626;
                    align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">error</span>
                    <span id="maintArchivePasswordErrorText"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('maintArchiveWarning2')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmMaintArchiveAction()">Confirm</button>
            </div>
        </div>
    </div>

    {{-- ── DELETE WARNING MODALS ─────────────────────────────────────── --}}
    <div class="modal" id="maintDeleteWarning1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon" style="color:#dc2626;">warning</span>
                <h2>Permanently Delete Record</h2>
            </div>
            <div class="modal-body">
                <p><strong>Issue:</strong> <span id="deleteMaintLabel"></span></p>
                <p style="color:#dc2626; font-weight:600;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('maintDeleteWarning1')">Cancel</button>
                <button type="button" class="btn btn-primary" style="background:#dc2626;"
                    onclick="proceedToMaintDeletePassword()">Yes, Delete Permanently</button>
            </div>
        </div>
    </div>

    <div class="modal" id="maintDeleteWarning2">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">lock</span>
                <h2>Confirm with Password</h2>
            </div>
            <div class="modal-body">
                <p>Enter admin password to permanently delete this record.</p>
                <input type="password" class="password-input" id="maintDeletePassword" placeholder="Admin password">
                <p id="maintDeletePasswordError" style="
                    display:none; margin-top:8px; font-size:13px; color:#dc2626;
                    align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">error</span>
                    <span id="maintDeletePasswordErrorText"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('maintDeleteWarning2')">Cancel</button>
                <button type="button" class="btn btn-primary" style="background:#dc2626;"
                    onclick="confirmMaintDeleteAction()">Delete Permanently</button>
            </div>
        </div>
    </div>

    {{-- ── EDIT MAINTENANCE MODAL ────────────────────────────────────── --}}
    <div class="modal" id="editMaintenanceModal">
        <div class="modal-content" style="max-width:680px;">
            <div class="modal-header">
                <span class="material-symbols-outlined">edit</span>
                <h2>Edit Maintenance Record</h2>
            </div>
            <form id="editMaintenanceForm" novalidate>
                @csrf
                <input type="hidden" id="editMaintId">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Truck <span class="required">*</span></label>
                            <select name="truck_id" id="editMaintTruckId">
                                @foreach ($trucks as $truck)
                                    <option value="{{ $truck->id }}">{{ $truck->truck_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="editMaintStartDate">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Issue Description <span class="required">*</span></label>
                        <input type="text" name="issue_description" id="editMaintIssue">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="notes" id="editMaintNotes" placeholder="Notes (optional)">
                        </div>
                        <div class="form-group">
                            <label>Cost</label>
                            <input type="text" name="cost" id="editMaintCost" placeholder="₱ Cost (optional)">
                        </div>
                    </div>

                    {{-- Edit Error Box --}}
                    <div id="editMaintError" style="
                        display:none; margin-top:14px; padding:12px 14px;
                        background:#fef2f2; border:1px solid #fca5a5; border-radius:8px;">
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                            <ul id="editMaintErrorList" style="
                                margin:0; padding:0; list-style:none;
                                font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('editMaintenanceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/maintenance.js') }}"></script>
@endpush