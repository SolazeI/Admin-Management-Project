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

    {{-- Tickets Table --}}
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
                        <tr>
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
                            <td>
                                <details style="position:relative;">
                                    <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer;">
                                        <span class="material-symbols-outlined">edit</span>
                                        Edit
                                    </summary>
                                    <div style="margin-top:10px; display:grid; grid-template-columns: repeat(4, minmax(140px,1fr)); gap:9px;">
                                        <form action="{{ url('/trips/'.$trip->id) }}" method="POST"
                                            style="display:contents;">
                                            @csrf
                                            <input name="trip_no" class="search-input" style="width:100%;" value="{{ $trip->trip_no }}" required>
                                            {{-- Truck select in edit form --}}
                                            <select name="truck_id" class="search-input" style="width:100%;" required>
                                                @foreach ($allTrucks as $truck)
                                                    @if ($truck->status === 'Available' || $trip->truck_id === $truck->id)
                                                        <option value="{{ $truck->id }}" {{ $trip->truck_id === $truck->id ? 'selected' : '' }}>
                                                            {{ $truck->truck_code }}
                                                            @if ($trip->truck_id !== $truck->id && $truck->status !== 'Available')
                                                                ({{ $truck->status }})
                                                            @endif
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>

                                            {{-- Driver select in edit form --}}
                                            <select name="driver_id" class="search-input" style="width:100%;" required>
                                                @foreach ($allDrivers as $driver)
                                                    @if ($driver->status === 'Available' || $trip->driver_id === $driver->id)
                                                        <option value="{{ $driver->id }}" {{ $trip->driver_id === $driver->id ? 'selected' : '' }}>
                                                            {{ $driver->full_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                           <select name="status" class="search-input" style="width:100%;" required>
                                            @foreach (['Draft','In-Transit','Completed','Cancelled'] as $st)
                                                @php
                                                    $isInTransit = $st === 'In-Transit';
                                                    $alreadyThisStatus = $trip->status === $st;

                                                    // Check if another trip is already In-Transit with same truck or driver
                                                    $conflictExists = $isInTransit && !$alreadyThisStatus && (
                                                        \App\Models\TripTicket::where('status', 'In-Transit')
                                                            ->where('id', '!=', $trip->id)
                                                            ->where(function($q) use ($trip) {
                                                                $q->where('truck_id', $trip->truck_id)
                                                                ->orWhere('driver_id', $trip->driver_id);
                                                            })->exists()
                                                    );
                                                @endphp
                                                <option
                                                    value="{{ $st }}"
                                                    {{ $alreadyThisStatus ? 'selected' : '' }}
                                                    {{ $conflictExists ? 'disabled' : '' }}
                                                    style="{{ $conflictExists ? 'color:#94a3b8; background:#f8fafc;' : '' }}"
                                                >
                                                    {{ $st }}{{ $conflictExists ? ' (unavailable)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                            <input name="origin" class="search-input" style="width:100%;" value="{{ $trip->origin }}" placeholder="Origin">
                                            <input name="destination" class="search-input" style="width:100%;" value="{{ $trip->destination }}" placeholder="Destination">
                                            <input type="date" name="date_issued" class="search-input" style="width:100%;" value="{{ optional($trip->date_issued)->format('Y-m-d') }}">
                                            <input name="distance_km" class="search-input" style="width:100%;" value="{{ $trip->distance_km }}" placeholder="Distance km">
                                            <input type="datetime-local" name="departure_time" class="search-input" style="width:100%;" value="{{ $trip->departure_time ? \Illuminate\Support\Carbon::parse($trip->departure_time)->format('Y-m-d\TH:i') : '' }}">
                                            <input type="datetime-local" name="arrival_time" class="search-input" style="width:100%;" value="{{ $trip->arrival_time ? \Illuminate\Support\Carbon::parse($trip->arrival_time)->format('Y-m-d\TH:i') : '' }}">
                                            <input name="amount" class="search-input" style="width:100%;" value="{{ $trip->amount }}" placeholder="Amount">
                                            <button class="btn btn-primary" type="submit">Save</button>
                                            <textarea name="remarks" class="search-input"
                                                style="grid-column:1 / -1; width:100%; height:60px; resize:none;">{{ $trip->remarks }}</textarea>
                                        </form>
                                        <form action="{{ url('/trips/'.$trip->id.'/delete') }}" method="POST" style="margin-top:6px;">
                                            @csrf
                                            <button class="btn btn-cancel" type="submit"
                                                onclick="return confirm('Delete this trip ticket?')">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="no-data">No trip tickets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- ===================== ADD TRIP TICKET MODAL ===================== --}}
    <div class="modal" id="addTripModal">
        <div class="modal-content" style="max-width:900px;">
            <div class="modal-header">
                <span class="material-symbols-outlined">receipt_long</span>
                <h2>Trip Ticket Entry</h2>
            </div>
            <div class="modal-body" style="padding:0;">
                <form action="{{ url('/trips') }}" method="POST">
                    @csrf
                    <div class="trip-entry-header">Weekly / Monthly Trip Ticket Entry</div>
                    <div class="trip-entry-subtitle">Record trip details for the selected period</div>
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
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Status <span style="color:#ef4444;">*</span></label>
                            <select name="status" class="search-input" style="width:100%;" required>
                                @foreach (['Draft','In-Transit','Completed','Cancelled'] as $st)
                                    <option value="{{ $st }}">{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">&nbsp;</label>
                            <button class="btn btn-primary trip-save-btn" type="submit" style="width:100%;">
                                <span class="material-symbols-outlined">save</span>
                                Save Record
                            </button>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Remarks</label>
                            <textarea name="remarks" class="search-input trip-remarks" style="width:100%;" placeholder="Remarks..."></textarea>
                        </div>
                    </div>

                    {{-- modal footer cancel --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" onclick="closeTripModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('addTripBtn').addEventListener('click', () => {
            document.getElementById('addTripModal').classList.add('show');
        });

        function closeTripModal() {
            document.getElementById('addTripModal').classList.remove('show');
        }

        document.getElementById('addTripModal').addEventListener('click', function (e) {
            if (e.target === this) closeTripModal();
        });

        // ── Trip Filter Panel ────────────────────────────────────
        const tripFilterBtn   = document.getElementById('tripFilterBtn');
        const tripFilterPanel = document.getElementById('tripFilterPanel');

        tripFilterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            tripFilterPanel.style.display = tripFilterPanel.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', (e) => {
            if (!tripFilterPanel.contains(e.target) && e.target !== tripFilterBtn) {
                tripFilterPanel.style.display = 'none';
            }
        });

        document.querySelectorAll('.trip-status-filter').forEach(cb => {
            cb.addEventListener('change', applyTripFilters);
        });

        function clearTripFilters() {
            document.querySelectorAll('.trip-status-filter').forEach(cb => cb.checked = true);
            applyTripFilters();
        }

        function applyTripFilters() {
            const query   = document.getElementById('tripSearch')?.value.trim().toLowerCase() ?? '';
            const checked = Array.from(document.querySelectorAll('.trip-status-filter:checked'))
                .map(cb => cb.value);

            document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (!cells.length) return;

                const truck       = cells[1]?.textContent.toLowerCase() ?? '';
                const driver      = cells[2]?.textContent.toLowerCase() ?? '';
                const destination = cells[4]?.textContent.toLowerCase() ?? '';

                const matchesSearch = !query
                    || truck.includes(query)
                    || driver.includes(query)
                    || destination.includes(query);

                const badge  = row.querySelector('.status-badge');
                const status = Array.from(badge?.classList ?? [])
                    .find(c => c.startsWith('status-') && c !== 'status-badge')
                    ?.replace('status-', '') ?? '';

                row.style.display = (matchesSearch && checked.includes(status)) ? '' : 'none';
            });
        }

        // ── Search ───────────────────────────────────────────────
        document.getElementById('tripSearch').addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (!cells.length) return;

                const truck       = cells[1]?.textContent.toLowerCase() ?? '';
                const driver      = cells[2]?.textContent.toLowerCase() ?? '';
                const destination = cells[4]?.textContent.toLowerCase() ?? '';

                const matches = !query
                    || truck.includes(query)
                    || driver.includes(query)
                    || destination.includes(query);

                // Respect active filter — only show if both search and filter pass
                if (!matches) {
                    row.style.display = 'none';
                } else {
                    // Re-check filter state before showing
                    const badge = row.querySelector('.status-badge');
                    const status = Array.from(badge?.classList ?? [])
                        .find(c => c.startsWith('status-') && c !== 'status-badge')
                        ?.replace('status-', '') ?? '';

                    const checked = Array.from(document.querySelectorAll('.trip-status-filter:checked'))
                        .map(cb => cb.value);

                    row.style.display = checked.includes(status) ? '' : 'none';
                }
            });
        });
    </script>
@endsection
