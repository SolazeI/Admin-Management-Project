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
            <button class="btn btn-primary" id="addMaintenanceBtn">
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
    @if (isset($errors) && $errors->any())
        <div class="notice-line" style="border-left-color:#dc2626; background:#fef2f2; color:#991b1b; margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Records Table --}}
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
                        <tr data-truck="{{ strtolower($rec->truck->truck_code ?? '') }}"
                        data-issue="{{ strtolower($rec->issue_description) }}"
                        data-notes="{{ strtolower($rec->notes ?? '') }}">
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
                            <td>
                                <details>
                                    <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer; font-size:12px;">
                                        <span class="material-symbols-outlined">build</span>
                                        Update
                                    </summary>
                                    <div style="margin-top:10px;">
                                        <form action="{{ url('/maintenance/'.$rec->id) }}" method="POST"
                                            style="display:grid; grid-template-columns: repeat(4, minmax(140px,1fr)); gap:9px; align-items:end;">
                                            @csrf
                                            <select name="truck_id" class="search-input" style="width:100%;" required>
                                                @foreach ($trucks as $truck)
                                                    <option value="{{ $truck->id }}" {{ $rec->truck_id === $truck->id ? 'selected' : '' }}>
                                                        {{ $truck->truck_code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input name="issue_description" class="search-input" style="width:100%;" value="{{ $rec->issue_description }}" required>
                                            <input type="date" name="start_date" class="search-input" style="width:100%;" value="{{ $rec->start_date }}">
                                            <select name="status" class="search-input" style="width:100%;" required>
                                                @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                                                    <option value="{{ $st }}" {{ $rec->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                                @endforeach
                                            </select>
                                            <input name="notes" class="search-input" style="width:100%;" value="{{ $rec->notes }}" placeholder="Notes">
                                            <input name="cost" class="search-input" style="width:100%;" value="{{ $rec->cost }}" placeholder="Cost">
                                            <button class="btn btn-primary" type="submit" style="grid-column: 3 / 5; justify-content:center;">Save Changes</button>
                                        </form>
                                        <form action="{{ url('/maintenance/'.$rec->id.'/delete') }}" method="POST" style="margin-top:8px;">
                                            @csrf
                                            <button class="btn btn-cancel" type="submit"
                                                onclick="return confirm('Delete this record?')">Delete Record</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="no-data">No maintenance records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- ===================== ADD MAINTENANCE MODAL ===================== --}}
    <div class="modal" id="addMaintenanceModal">
        <div class="modal-content" style="max-width:680px;">
            <div class="modal-header">
                <span class="material-symbols-outlined">build</span>
                <h2>Add Maintenance Record</h2>
            </div>
            <form action="{{ url('/maintenance') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Truck <span class="required">*</span></label>
                            <select name="truck_id" required>
                                <option value="">Select Truck</option>
                                @foreach ($trucks as $truck)
                                    <option value="{{ $truck->id }}">{{ $truck->truck_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status" required>
                                @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                                    <option value="{{ $st }}">{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Issue Description <span class="required">*</span></label>
                        <input type="text" name="issue_description" placeholder="Describe the issue" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date">
                        </div>
                        <div class="form-group">
                            <label>Cost</label>
                            <input type="text" name="cost" placeholder="₱ Cost (optional)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <input type="text" name="notes" placeholder="Notes (optional)">
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
    <script src="{{ asset('js/maintenance.js') }}"></script>
@endsection
