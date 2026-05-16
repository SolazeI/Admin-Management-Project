@extends('layouts.app')
@php
    $active = 'trips';
    $title  = 'Trip Tickets';
@endphp
@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Trip Tickets</h1>
            <p class="page-subtitle">Manage weekly/monthly trip entries and view monthly summaries.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" id="archivedTripsBtn">
                <span class="material-symbols-outlined">folder</span>
                Archived
            </button>
            <button class="btn btn-primary" id="addTripBtn">
                <span class="material-symbols-outlined">add</span>
                Trip Ticket Entry
            </button>
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" id="tripSearch" placeholder="Search by truck, driver, destination" autocomplete="off">
            </div>
            <div style="position:relative;">
                <button class="btn btn-filter" id="tripFilterBtn">
                    <span class="material-symbols-outlined">filter_alt</span>
                    Filter
                </button>
                <div id="tripFilterPanel" style="
                    display:none; position:absolute; right:0; top:calc(100% + 6px);
                    background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                    box-shadow:0 8px 24px rgba(0,0,0,.12); padding:14px 16px;
                    min-width:180px; z-index:500;">
                    @foreach (['Draft','In-Transit','Completed','Cancelled'] as $st)
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#1e293b; cursor:pointer; padding:4px 0;">
                            <input type="checkbox" class="trip-status-filter" value="{{ strtolower(str_replace(['-',' '], '', $st)) }}" checked
                                style="accent-color:#0f1a2e; width:14px; height:14px; cursor:pointer;">
                            {{ $st }}
                        </label>
                    @endforeach
                    <button onclick="clearTripFilters()" style="
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

    {{-- ── Tickets Table ─────────────────────────────────────────────── --}}
    <div class="drivers-section">
        <h2 class="section-title">All Trip Tickets</h2>
        <div class="table-container">
            <table class="drivers-table">
                <thead>
                    <tr>
                        <th>Trip No.</th>
                        <th>Truck</th>
                        <th>Driver</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tripTickets as $trip)
                        <tr data-trip-id="{{ $trip->id }}">
                            <td style="font-weight:700;">{{ $trip->trip_no }}</td>
                            <td>{{ $trip->truck->truck_code ?? '—' }}</td>
                            <td>{{ $trip->driver->full_name ?? '—' }}</td>
                            <td>{{ $trip->origin ?? '—' }}</td>
                            <td>{{ $trip->destination ?? '—' }}</td>
                            <td>{{ $trip->amount ? '₱'.number_format($trip->amount,2) : '—' }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(['-',' '],'', $trip->status)) }}">
                                    {{ $trip->status }}
                                </span>
                            </td>
                            <td style="position:relative;">
                                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">

                                    {{-- Status transition buttons --}}
                                    @if ($trip->status === 'Draft')
                                        <button type="button" class="btn btn-primary trip-transition-btn"
                                            style="padding:5px 11px; font-size:12px; gap:4px;"
                                            data-id="{{ $trip->id }}" data-status="In-Transit">
                                            <span class="material-symbols-outlined" style="font-size:14px;">local_shipping</span>
                                            Dispatch
                                        </button>
                                        <button type="button" class="btn btn-cancel trip-transition-btn"
                                            style="padding:5px 11px; font-size:12px;"
                                            data-id="{{ $trip->id }}" data-status="Cancelled" data-confirm="Cancel this trip ticket?">
                                            Cancel
                                        </button>
                                    @elseif ($trip->status === 'In-Transit')
                                        <button type="button" class="btn btn-primary trip-transition-btn"
                                            style="padding:5px 11px; font-size:12px; gap:4px; background:#059669;"
                                            data-id="{{ $trip->id }}" data-status="Completed">
                                            <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span>
                                            Complete
                                        </button>
                                        <button type="button" class="btn btn-cancel trip-transition-btn"
                                            style="padding:5px 11px; font-size:12px;"
                                            data-id="{{ $trip->id }}" data-status="Cancelled" data-confirm="Cancel this trip ticket?">
                                            Cancel
                                        </button>
                                    @endif

                                    {{-- Edit panel --}}
                                    @if (!in_array($trip->status, ['Completed', 'Cancelled']))
                                        <details class="trip-edit-details" style="display:inline-block;">
                                            <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer; padding:5px 11px; font-size:12px; gap:4px;">
                                                <span class="material-symbols-outlined" style="font-size:14px;">edit</span>
                                                Edit
                                            </summary>
                                            <div style="
                                                position:absolute; right:0; top:calc(100% + 4px);
                                                background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                                                box-shadow:0 8px 24px rgba(0,0,0,.12); padding:16px;
                                                min-width:560px; z-index:300;">
                                                <form class="trip-edit-form" data-id="{{ $trip->id }}"
                                                    style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                    @csrf
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Trip No.</label>
                                                        <input name="trip_no" class="search-input" style="width:100%;" value="{{ $trip->trip_no }}" required>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Date Issued</label>
                                                        <input type="date" name="date_issued" class="search-input" style="width:100%;" value="{{ optional($trip->date_issued)->format('Y-m-d') }}">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Truck</label>
                                                        <select name="truck_id" class="search-input" style="width:100%;" required>
                                                            @foreach ($allTrucks as $truck)
                                                                @if ($truck->status === 'Available' || $trip->truck_id === $truck->id)
                                                                    <option value="{{ $truck->id }}" {{ $trip->truck_id === $truck->id ? 'selected' : '' }}>
                                                                        {{ $truck->truck_code }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Driver</label>
                                                        <select name="driver_id" class="search-input" style="width:100%;" required>
                                                            @foreach ($allDrivers as $driver)
                                                                @if ($driver->status === 'Available' || $trip->driver_id === $driver->id)
                                                                    <option value="{{ $driver->id }}" {{ $trip->driver_id === $driver->id ? 'selected' : '' }}>
                                                                        {{ $driver->full_name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Origin</label>
                                                        <input name="origin" class="search-input" style="width:100%;" value="{{ $trip->origin }}" placeholder="Origin">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Destination</label>
                                                        <input name="destination" class="search-input" style="width:100%;" value="{{ $trip->destination }}" placeholder="Destination">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Departure Time</label>
                                                        <input type="datetime-local" name="departure_time" class="search-input" style="width:100%;"
                                                            value="{{ $trip->departure_time ? \Illuminate\Support\Carbon::parse($trip->departure_time)->format('Y-m-d\TH:i') : '' }}">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Arrival Time</label>
                                                        <input type="datetime-local" name="arrival_time" class="search-input" style="width:100%;"
                                                            value="{{ $trip->arrival_time ? \Illuminate\Support\Carbon::parse($trip->arrival_time)->format('Y-m-d\TH:i') : '' }}">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Distance (km)</label>
                                                        <input name="distance_km" class="search-input" style="width:100%;" value="{{ $trip->distance_km }}" placeholder="Distance km">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Amount</label>
                                                        <input name="amount" class="search-input" style="width:100%;" value="{{ $trip->amount }}" placeholder="₱ Amount">
                                                    </div>
                                                    <div style="grid-column: 1 / -1;">
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Remarks</label>
                                                        <textarea name="remarks" class="search-input"
                                                            style="width:100%; height:60px; resize:none;">{{ $trip->remarks }}</textarea>
                                                    </div>

                                                    {{-- Inline Error Box --}}
                                                    <div class="trip-form-error" style="
                                                        display:none; grid-column: 1 / -1;
                                                        padding:12px 14px; background:#fef2f2;
                                                        border:1px solid #fca5a5; border-radius:8px;">
                                                        <div style="display:flex; align-items:flex-start; gap:8px;">
                                                            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                                                            <ul class="trip-form-error-list" style="
                                                                margin:0; padding:0; list-style:none;
                                                                font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                                                        </div>
                                                    </div>

                                                    <div style="grid-column: 1 / -1; display:flex; gap:8px; justify-content:flex-end; padding-top:4px;">
                                                        <button class="btn btn-primary trip-edit-save-btn" type="submit" style="font-size:12px; padding:6px 14px;">Save Changes</button>
                                                    </div>
                                                </form>

                                                <div style="margin-top:10px; padding-top:10px; border-top:1px solid #f1f5f9;">
                                                    <button type="button" class="btn btn-secondary" style="font-size:12px; gap:4px;"
                                                        onclick="confirmArchiveTrip({{ $trip->id }}, '{{ addslashes($trip->trip_no) }}')">
                                                        <span class="material-symbols-outlined" style="font-size:14px;">archive</span>
                                                        Archive Trip
                                                    </button>
                                                </div>
                                            </div>
                                        </details>
                                    @else
                                        <button type="button" class="btn btn-secondary" style="font-size:12px; padding:5px 11px; gap:4px;"
                                            onclick="confirmArchiveTrip({{ $trip->id }}, '{{ addslashes($trip->trip_no) }}')">
                                            <span class="material-symbols-outlined" style="font-size:14px;">archive</span>
                                            Archive
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="no-data">No trip tickets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── ADD TRIP TICKET MODAL ─────────────────────────────────────── --}}
    <div class="modal" id="addTripModal">
        <div class="modal-content" style="max-width:900px;">
            <div class="modal-header">
                <span class="material-symbols-outlined">receipt_long</span>
                <h2>Trip Ticket Entry</h2>
            </div>
            <div class="modal-body" style="padding:0;">
                <form id="addTripForm">
                    @csrf
                    <div class="trip-entry-header">Weekly / Monthly Trip Ticket Entry</div>
                    <div class="trip-entry-subtitle">New tickets start as Draft — use the Dispatch button to put them In-Transit</div>
                    <div class="trip-entry-form">
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Date Issued</label>
                            <input type="date" name="date_issued" class="search-input" style="width:100%;">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Truck <span style="color:#ef4444;">*</span></label>
                            <select name="truck_id" class="search-input" style="width:100%;" required>
                                <option value="">Select Truck</option>
                                @foreach ($trucks as $truck)
                                    <option value="{{ $truck->id }}">{{ $truck->truck_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Driver <span style="color:#ef4444;">*</span></label>
                            <select name="driver_id" class="search-input" style="width:100%;" required>
                                <option value="">Select Driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Trip No.</label>
                            <input name="trip_no" class="search-input" style="width:100%;" placeholder="Auto-generated if blank">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Distance (km)</label>
                            <input name="distance_km" class="search-input" style="width:100%;" placeholder="Distance km">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Origin</label>
                            <input name="origin" class="search-input" style="width:100%;" placeholder="Loading point">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Destination</label>
                            <input name="destination" class="search-input" style="width:100%;" placeholder="Destination">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Amount</label>
                            <input name="amount" class="search-input" style="width:100%;" placeholder="₱ Amount">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Departure Time</label>
                            <input type="datetime-local" name="departure_time" class="search-input" style="width:100%;">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Arrival Time</label>
                            <input type="datetime-local" name="arrival_time" class="search-input" style="width:100%;">
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">
                                <span class="material-symbols-outlined">save</span>
                                Save as Draft
                            </button>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Remarks</label>
                            <textarea name="remarks" class="search-input trip-remarks" style="width:100%;" placeholder="Remarks..."></textarea>
                        </div>

                        {{-- Add Error Box --}}
                        <div id="addTripError" style="
                            display:none; grid-column: 1 / -1;
                            padding:12px 14px; background:#fef2f2;
                            border:1px solid #fca5a5; border-radius:8px;">
                            <div style="display:flex; align-items:flex-start; gap:8px;">
                                <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                                <ul id="addTripErrorList" style="
                                    margin:0; padding:0; list-style:none;
                                    font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" onclick="closeTripModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── ARCHIVED TRIPS MODAL ──────────────────────────────────────── --}}
    <div class="modal" id="archivedTripsModal">
        <div class="modal-content archived-modal">
            <div class="modal-header">
                <h2>Archived Trip Tickets</h2>
                <div class="search-wrapper">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" class="search-input" id="archivedTripSearch" placeholder="Search archived trips">
                </div>
            </div>
            <div class="modal-body" style="padding:0;">
                <div style="overflow-x:auto; padding:0 24px 24px;">
                    <table class="archived-table">
                        <thead>
                            <tr>
                                <th>Trip No.</th>
                                <th>Truck</th>
                                <th>Driver</th>
                                <th>Destination</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedTripsTableBody">
                            @forelse ($archivedTripTickets as $archived)
                                <tr>
                                    <td style="font-weight:700;">{{ $archived->trip_no }}</td>
                                    <td>{{ $archived->truck->truck_code ?? '—' }}</td>
                                    <td>{{ $archived->driver->full_name ?? '—' }}</td>
                                    <td>{{ $archived->destination ?? '—' }}</td>
                                    <td>{{ $archived->amount ? '₱'.number_format($archived->amount,2) : '—' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower(str_replace(['-',' '],'', $archived->status)) }}">
                                            {{ $archived->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <button type="button" class="btn btn-secondary trip-unarchive-btn"
                                                style="font-size:12px; padding:5px 11px; gap:4px;"
                                                data-id="{{ $archived->id }}">
                                                <span class="material-symbols-outlined" style="font-size:14px;">restore</span>
                                                Restore
                                            </button>
                                            <span style="display:inline-block; width:1px; height:22px; background:#e2e8f0; margin:0 4px;"></span>
                                            <button type="button"
                                                style="
                                                    display:inline-flex; align-items:center; gap:4px;
                                                    font-size:12px; padding:5px 11px; border-radius:6px;
                                                    border:1px solid #fca5a5; background:#fff5f5;
                                                    color:#dc2626; font-family:'Poppins',sans-serif;
                                                    font-weight:600; cursor:pointer; line-height:1;"
                                                onclick="confirmDeleteTrip({{ $archived->id }}, '{{ addslashes($archived->trip_no) }}')">
                                                <span class="material-symbols-outlined" style="font-size:14px;">delete_forever</span>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="no-data">No archived trip tickets.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('archivedTripsModal')">Close</button>
            </div>
        </div>
    </div>

    {{-- ── ARCHIVE WARNING MODALS ────────────────────────────────────── --}}
    <div class="modal" id="tripArchiveWarning1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">archive</span>
                <h2>Archive Trip Ticket</h2>
            </div>
            <div class="modal-body">
                <p><strong>Trip No:</strong> <span id="archiveTripLabel"></span></p>
                <p>This ticket will be moved to the archive. You can restore it later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('tripArchiveWarning1')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedToArchivePassword()">Yes, Archive</button>
            </div>
        </div>
    </div>

    <div class="modal" id="tripArchiveWarning2">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">lock</span>
                <h2>Confirm with Password</h2>
            </div>
            <div class="modal-body">
                <p>Enter admin password to archive this ticket.</p>
                <input type="password" class="password-input" id="tripArchivePassword" placeholder="Admin password">
                <p id="tripArchivePasswordError" style="
                    display:none; margin-top:8px; font-size:13px; color:#dc2626;">
                    <span style="display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:16px;">error</span>
                        <span id="tripArchivePasswordErrorText"></span>
                    </span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('tripArchiveWarning2')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmArchiveAction()">Confirm</button>
            </div>
        </div>
    </div>

    {{-- ── DELETE WARNING MODALS ─────────────────────────────────────── --}}
    <div class="modal" id="tripDeleteWarning1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon" style="color:#dc2626;">warning</span>
                <h2>Permanently Delete Trip</h2>
            </div>
            <div class="modal-body">
                <p><strong>Trip No:</strong> <span id="deleteTripLabel"></span></p>
                <p style="color:#dc2626; font-weight:600;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('tripDeleteWarning1')">Cancel</button>
                <button type="button" class="btn btn-primary" style="background:#dc2626;"
                    onclick="proceedToDeletePassword()">Yes, Delete Permanently</button>
            </div>
        </div>
    </div>

    <div class="modal" id="tripDeleteWarning2">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">lock</span>
                <h2>Confirm with Password</h2>
            </div>
            <div class="modal-body">
                <p>Enter admin password to permanently delete this ticket.</p>
                <input type="password" class="password-input" id="tripDeletePassword" placeholder="Admin password">
                <p id="tripDeletePasswordError" style="
                    display:none; margin-top:8px; font-size:13px; color:#dc2626;">
                    <span style="display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:16px;">error</span>
                        <span id="tripDeletePasswordErrorText"></span>
                    </span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('tripDeleteWarning2')">Cancel</button>
                <button type="button" class="btn btn-primary" style="background:#dc2626;"
                    onclick="confirmDeleteAction()">Delete Permanently</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/trips.js') }}"></script>
@endsection