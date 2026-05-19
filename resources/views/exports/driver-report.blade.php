<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Report — {{ $driver->full_name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #1e293b;
            background: #fff;
            padding: 48px 56px;
            max-width: 860px;
            margin: 0 auto;
        }

        /* ── Header ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 3px solid #0f1a2e;
            margin-bottom: 28px;
        }
        .doc-company { font-size: 18px; font-weight: 700; color: #0f1a2e; letter-spacing: .3px; }
        .doc-company-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-meta { text-align: right; font-size: 11px; color: #64748b; line-height: 1.7; }
        .doc-meta strong { color: #1e293b; }

        /* ── Document title ── */
        .doc-title-block { margin-bottom: 24px; }
        .doc-title {
            font-size: 15px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: #0f1a2e;
        }
        .doc-subtitle { font-size: 11px; color: #64748b; margin-top: 3px; }

        /* ── Section ── */
        .section { margin-bottom: 24px; }
        .section-title {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .6px; color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px; margin-bottom: 12px;
        }

        /* ── Key-value grid ── */
        .kv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 32px;
        }
        .kv-row { display: flex; gap: 8px; align-items: baseline; }
        .kv-label { font-size: 11px; color: #64748b; font-weight: 500; min-width: 110px; }
        .kv-value { font-size: 12px; color: #1e293b; font-weight: 500; }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead tr { background: #0f1a2e; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-weight: 600; letter-spacing: .3px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #f1f5f9; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        tfoot td { padding: 8px 10px; font-weight: 600; border-top: 2px solid #0f1a2e; }

        /* ── Status badge ── */
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 10px; font-weight: 600; letter-spacing: .3px;
        }
        .badge-available   { background:#dcfce7; color:#15803d; }
        .badge-on-leave,
        .badge-onleave     { background:#fef9c3; color:#a16207; }
        .badge-covering    { background:#dbeafe; color:#1d4ed8; }
        .badge-inactive    { background:#f1f5f9; color:#64748b; }
        .badge-completed   { background:#dcfce7; color:#15803d; }
        .badge-inprogress,
        .badge-in-progress { background:#dbeafe; color:#1d4ed8; }
        .badge-pending     { background:#fef9c3; color:#a16207; }
        .badge-cancelled   { background:#fee2e2; color:#b91c1c; }

        /* ── Signature block ── */
        .sig-block {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 24px; margin-top: 48px;
        }
        .sig-item { border-top: 1px solid #1e293b; padding-top: 6px; }
        .sig-label { font-size: 10px; color: #64748b; }
        .sig-name  { font-size: 11px; font-weight: 600; color: #1e293b; margin-top: 2px; }

        /* ── Footer ── */
        .doc-footer {
            margin-top: 32px; padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px; color: #94a3b8;
            display: flex; justify-content: space-between;
        }

        /* ── Print ── */
        @media print {
            body { padding: 0; }
            @page { size: A4; margin: 18mm 16mm; }
        }
    </style>
</head>
<body>

    {{-- ── Document Header ── --}}
    <div class="doc-header">
        <div>
            <div class="doc-company">Fleet Management System</div>
            <div class="doc-company-sub">Internal Operations Document</div>
        </div>
        <div class="doc-meta">
            <div><strong>Reference No.:</strong> {{ $referenceNo }}</div>
            <div><strong>Date Generated:</strong> {{ now()->format('F d, Y') }}</div>
            <div><strong>Time:</strong> {{ now()->format('h:i A') }}</div>
        </div>
    </div>

    {{-- ── Title ── --}}
    <div class="doc-title-block">
        <div class="doc-title">Driver Trip Summary Report</div>
        <div class="doc-subtitle">Official record of trip history for HR, audit, and operational use.</div>
    </div>

    {{-- ── Driver Information ── --}}
    <div class="section">
        <div class="section-title">Driver Information</div>
        <div class="kv-grid">
            <div class="kv-row">
                <span class="kv-label">Full Name</span>
                <span class="kv-value">{{ $driver->full_name }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Status</span>
                <span class="kv-value">
                    @php $sk = strtolower(str_replace(['-',' '], '', $driver->status ?? 'inactive')); @endphp
                    <span class="badge badge-{{ $sk }}">{{ $driver->status ?? 'Inactive' }}</span>
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Latest Truck</span>
                <span class="kv-value">{{ $driver->latestTruck->truck_code ?? '—' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Total Trips</span>
                <span class="kv-value">{{ $driver->total_trips_count }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">License No.</span>
                <span class="kv-value">{{ $driver->license_number ?? '—' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Contact No.</span>
                <span class="kv-value">{{ $driver->contact_number ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Trip Records ── --}}
    <div class="section">
        <div class="section-title">Trip Records</div>
        @if($driver->trips->isEmpty())
            <p style="color:#64748b; font-size:11px;">No trip records found for this driver.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Trip Ticket</th>
                    <th>Truck</th>
                    <th>Origin → Destination</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($driver->trips as $i => $trip)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $trip->ticket_number ?? '—' }}</td>
                        <td>{{ $trip->truck->truck_code ?? '—' }}</td>
                        <td>{{ $trip->origin ?? '—' }} → {{ $trip->destination ?? '—' }}</td>
                        <td>{{ $trip->created_at?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @php $ts = strtolower(str_replace(['-',' '], '', $trip->status ?? '')); @endphp
                            <span class="badge badge-{{ $ts }}">{{ $trip->status ?? '—' }}</span>
                        </td>
                        <td style="text-align:right;">
                            {{ $trip->amount !== null ? '₱'.number_format($trip->amount, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right; color:#64748b;">Total Revenue (Completed)</td>
                    <td style="text-align:right;">
                        ₱{{ number_format($driver->trips->where('status','Completed')->sum('amount'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    {{-- ── Signature Block ── --}}
    <div class="sig-block">
        <div class="sig-item">
            <div class="sig-name">&nbsp;</div>
            <div class="sig-label">Prepared by / Operations</div>
        </div>
        <div class="sig-item">
            <div class="sig-name">&nbsp;</div>
            <div class="sig-label">Reviewed by / Fleet Manager</div>
        </div>
        <div class="sig-item">
            <div class="sig-name">&nbsp;</div>
            <div class="sig-label">Approved by / Director</div>
        </div>
    </div>

    {{-- ── Footer ── --}}
    <div class="doc-footer">
        <span>Fleet Management System — Confidential</span>
        <span>Ref: {{ $referenceNo }} &nbsp;|&nbsp; {{ now()->format('Y-m-d H:i') }}</span>
    </div>

    <script>window.onload = () => window.print();</script>
</body>
</html>