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
                            <td>{{ $rec->cost ? '₱'.number_format($rec->cost,2) : '—' }}</td>
                            <td style="position:relative;">
                                {{-- Status transition action buttons --}}
                                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
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

                                    {{-- Edit details (non-status fields) --}}
                                    @if (!in_array($rec->status, ['Completed', 'Cancelled']))
                                        <details style="display:inline-block;">
                                            <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer; padding:5px 11px; font-size:12px; gap:4px;">
                                                <span class="material-symbols-outlined" style="font-size:14px;">edit</span>
                                                Edit
                                            </summary>
                                            <div style="
                                                position:absolute; right:0; top:calc(100% + 4px);
                                                background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                                                box-shadow:0 8px 24px rgba(0,0,0,.12); padding:16px;
                                                min-width:500px; z-index:300;">
                                                <form action="{{ url('/maintenance/'.$rec->id) }}" method="POST"
                                                    style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                                    @csrf
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Truck</label>
                                                        <select name="truck_id" class="search-input" style="width:100%;" required>
                                                            @foreach ($trucks as $truck)
                                                                <option value="{{ $truck->id }}" {{ $rec->truck_id === $truck->id ? 'selected' : '' }}>
                                                                    {{ $truck->truck_code }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Start Date</label>
                                                        <input type="date" name="start_date" class="search-input" style="width:100%;" value="{{ $rec->start_date }}">
                                                    </div>
                                                    <div style="grid-column: 1 / -1;">
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Issue Description</label>
                                                        <input name="issue_description" class="search-input" style="width:100%;" value="{{ $rec->issue_description }}" required>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Notes</label>
                                                        <input name="notes" class="search-input" style="width:100%;" value="{{ $rec->notes }}" placeholder="Notes">
                                                    </div>
                                                    <div>
                                                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Cost</label>
                                                        <input name="cost" class="search-input" style="width:100%;" value="{{ $rec->cost }}" placeholder="Cost (optional)">
                                                    </div>
                                                    <div style="grid-column: 1 / -1; display:flex; gap:8px; justify-content:flex-end; padding-top:4px;">
                                                        <button class="btn btn-primary" type="submit" style="font-size:12px; padding:6px 14px;">Save Changes</button>
                                                    </div>
                                                </form>
                                                <form action="{{ url('/maintenance/'.$rec->id.'/delete') }}" method="POST" style="margin-top:8px;">
                                                    @csrf
                                                    <button class="btn btn-cancel" type="submit" style="font-size:12px;"
                                                        onclick="return confirm('Delete this record?')">Delete Record</button>
                                                </form>
                                            </div>
                                        </details>
                                    @else
                                        {{-- Completed/Cancelled: only allow delete --}}
                                        <form action="{{ url('/maintenance/'.$rec->id.'/delete') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-cancel" type="submit" style="font-size:12px; padding:5px 11px;"
                                                onclick="return confirm('Delete this record?')">Delete</button>
                                        </form>
                                    @endif
                                </div>
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
                    <p style="font-size:12px; color:#64748b; margin-bottom:14px; background:#f8fafc; border-radius:7px; padding:8px 12px; border:1px solid #e2e8f0;">
                        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; color:#d97706;">info</span>
                        New records start as <strong>Pending</strong>. Use the <strong>Start</strong> button on the table to begin work.
                    </p>
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
                            <label>Start Date</label>
                            <input type="date" name="start_date">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Issue Description <span class="required">*</span></label>
                        <input type="text" name="issue_description" placeholder="Describe the issue" required>
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
<<<<<<< HEAD

    <script>
        document.getElementById('addMaintenanceBtn').addEventListener('click', () => {
            document.getElementById('addMaintenanceModal').classList.add('show');
        });

        function closeMaintenanceModal() {
            document.getElementById('addMaintenanceModal').classList.remove('show');
        }

        document.getElementById('addMaintenanceModal').addEventListener('click', function (e) {
            if (e.target === this) closeMaintenanceModal();
        });

        // Close edit dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details[open]').forEach(d => {
                if (!d.contains(e.target)) d.removeAttribute('open');
            });
        });

        // ── Maintenance Filter Panel ─────────────────────────────
        const maintFilterBtn   = document.getElementById('maintFilterBtn');
        const maintFilterPanel = document.getElementById('maintFilterPanel');

        maintFilterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            maintFilterPanel.style.display = maintFilterPanel.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', (e) => {
            if (!maintFilterPanel.contains(e.target) && e.target !== maintFilterBtn) {
                maintFilterPanel.style.display = 'none';
            }
        });

        document.querySelectorAll('.maint-status-filter').forEach(cb => {
            cb.addEventListener('change', applyMaintFilters);
        });

        function clearMaintFilters() {
            document.querySelectorAll('.maint-status-filter').forEach(cb => cb.checked = true);
            applyMaintFilters();
        }

        function applyMaintFilters() {
            const query   = document.getElementById('maintSearch')?.value.trim().toLowerCase() ?? '';
            const checked = Array.from(document.querySelectorAll('.maint-status-filter:checked'))
                .map(cb => cb.value);

            document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (!cells.length) return;

                const truck   = cells[0]?.textContent.trim().toLowerCase() ?? '';
                const issue   = cells[1]?.textContent.trim().toLowerCase() ?? '';
                const notes   = cells[4]?.textContent.trim().toLowerCase() ?? '';

                const matchesSearch = !query
                    || truck.includes(query)
                    || issue.includes(query)
                    || notes.includes(query);

                const badge  = row.querySelector('.status-badge');
                const status = Array.from(badge?.classList ?? [])
                    .find(c => c.startsWith('status-') && c !== 'status-badge')
                    ?.replace('status-', '') ?? '';

                row.style.display = (matchesSearch && checked.includes(status)) ? '' : 'none';
            });
        }

        document.getElementById('maintSearch').addEventListener('input', applyMaintFilters);
    </script>
@endsection
=======
    <script src="{{ asset('js/maintenance.js') }}"></script>
@endsection
>>>>>>> 2acb994d721b26ccb58fcb8d54e0528b4dc64e62
