<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Maintenance Report</title>
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

        .doc-title-block { margin-bottom: 24px; }
        .doc-title {
            font-size: 15px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: #0f1a2e;
        }
        .doc-subtitle { font-size: 11px; color: #64748b; margin-top: 3px; }

        /* ── Summary strip ── */
        .summary-strip {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 1px; background: #e2e8f0;
            border: 1px solid #e2e8f0; border-radius: 8px;
            overflow: hidden; margin-bottom: 24px;
        }
        .summary-cell {
            background: #fff; padding: 12px 14px;
        }
        .summary-cell .s-label { font-size: 10px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: .4px; }
        .summary-cell .s-value { font-size: 18px; font-weight: 700; color: #0f1a2e; margin-top: 2px; }
        .summary-cell .s-value.red   { color: #dc2626; }
        .summary-cell .s-value.green { color: #16a34a; }
        .summary-cell .s-value.blue  { color: #2563eb; }

        .section { margin-bottom: 24px; }
        .section-title {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .6px; color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px; margin-bottom: 12px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead tr { background: #0f1a2e; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-weight: 600; letter-spacing: .3px; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tfoot td { padding: 8px 10px; font-weight: 600; border-top: 2px solid #0f1a2e; }

        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 10px; font-weight: 600; letter-spacing: .3px;
        }
        .badge-completed   { background:#dcfce7; color:#15803d; }
        .badge-inprogress  { background:#dbeafe; color:#1d4ed8; }
        .badge-pending     { background:#fef9c3; color:#a16207; }
        .badge-cancelled   { background:#fee2e2; color:#b91c1c; }

        .sig-block {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 24px; margin-top: 48px;
        }
        .sig-item { border-top: 1px solid #1e293b; padding-top: 6px; }
        .sig-label { font-size: 10px; color: #64748b; }
        .sig-name  { font-size: 11px; font-weight: 600; color: #1e293b; margin-top: 2px; }

        .doc-footer {
            margin-top: 32px; padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px; color: #94a3b8;
            display: flex; justify-content: space-between;
        }

        @media print {
            body { padding: 0; }
            @page { size: A4 landscape; margin: 14mm 16mm; }
            .maint-print-report { font-family:'Inter',sans-serif; color:#1e293b; }
            .maint-print-header { display:flex; justify-content:space-between; border-bottom:2px solid #0f1a2e; padding-bottom:10px; margin-bottom:16px; }
            .maint-print-logo   { font-size:16px; font-weight:700; color:#0f1a2e; }
            .maint-print-meta   { font-size:11px; color:#64748b; text-align:right; }
            .maint-print-title  { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
            .maint-print-subtitle { font-size:11px; color:#64748b; margin-bottom:14px; }
            .maint-print-table  { width:100%; border-collapse:collapse; font-size:11px; }
            .maint-print-table thead tr { background:#0f1a2e; color:#fff; }
            .maint-print-table th, .maint-print-table td { padding:7px 10px; border-bottom:1px solid #e2e8f0; text-align:left; }
            .maint-print-table tbody tr:nth-child(even) { background:#f8fafc; }
            .maint-summary      { display:flex; gap:24px; margin-top:16px; }
            .maint-summary-item { border-top:2px solid #0f1a2e; padding-top:6px; }
            .maint-summary-label { font-size:10px; color:#64748b; }
            .maint-summary-value { font-size:14px; font-weight:700; }
            .maint-print-footer { margin-top:24px; font-size:10px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:8px; }
        }
    </style>
</head>
<body>

    {{-- ── Document Header ── --}}
    <div class="doc-header">
        <div>
            <div class="doc-company">Fleet Management System</div>
            <div class="doc-company-sub">Fleet Maintenance Division</div>
        </div>
        <div class="doc-meta">
            <div><strong>Reference No.:</strong> {{ $referenceNo }}</div>
            <div><strong>Date Generated:</strong> {{ now()->format('F d, Y') }}</div>
            <div><strong>Time:</strong> {{ now()->format('h:i A') }}</div>
            <div><strong>Total Records:</strong> {{ $records->count() }}</div>
        </div>
    </div>

    {{-- ── Title ── --}}
    <div class="doc-title-block">
        <div class="doc-title">Fleet Maintenance Report</div>
        <div class="doc-subtitle">Comprehensive maintenance log for compliance, insurance, and fleet audit purposes.</div>
    </div>

    {{-- ── Summary Strip ── --}}
    @php
        $byStatus = $records->groupBy('status');
        $completed  = $byStatus->get('Completed', collect())->count();
        $inProgress = $byStatus->get('In-Progress', collect())->count();
        $pending    = $byStatus->get('Pending', collect())->count();
        $cancelled  = $byStatus->get('Cancelled', collect())->count();
    @endphp
    <div class="summary-strip">
        <div class="summary-cell">
            <div class="s-label">Total Cost</div>
            <div class="s-value red">₱{{ number_format($totalCost, 0) }}</div>
        </div>
        <div class="summary-cell">
            <div class="s-label">Completed</div>
            <div class="s-value green">{{ $completed }}</div>
        </div>
        <div class="summary-cell">
            <div class="s-label">In Progress</div>
            <div class="s-value blue">{{ $inProgress }}</div>
        </div>
        <div class="summary-cell">
            <div class="s-label">Pending / Cancelled</div>
            <div class="s-value">{{ $pending }} / {{ $cancelled }}</div>
        </div>
    </div>

    {{-- ── Records Table ── --}}
    <div class="section">
        <div class="section-title">Maintenance Log</div>
        @if($records->isEmpty())
            <p style="color:#64748b; font-size:11px;">No maintenance records found.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Truck</th>
                    <th>Issue Description</th>
                    <th>Start Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th style="text-align:right;">Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $i => $rec)
                    @php $sk = strtolower(str_replace(['-',' '], '', $rec->status)); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight:600;">{{ $rec->truck->truck_code ?? '—' }}</td>
                        <td>{{ $rec->issue_description }}</td>
                        <td style="white-space:nowrap;">{{ $rec->start_date ?? '—' }}</td>
                        <td><span class="badge badge-{{ $sk }}">{{ $rec->status }}</span></td>
                        <td style="color:#64748b;">{{ $rec->notes ?? '—' }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            {{ $rec->cost !== null ? '₱'.number_format($rec->cost, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right; color:#64748b;">Total Maintenance Cost</td>
                    <td style="text-align:right;">₱{{ number_format($totalCost, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    {{-- ── Signature Block ── --}}
    <div class="sig-block">
        <div class="sig-item">
            <div class="sig-name">&nbsp;</div>
            <div class="sig-label">Prepared by / Maintenance Staff</div>
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
        <span>Fleet Management System — Confidential &nbsp;|&nbsp; For Internal Use Only</span>
        <span>Ref: {{ $referenceNo }} &nbsp;|&nbsp; {{ now()->format('Y-m-d H:i') }}</span>
    </div>

    <script>window.onload = () => window.print();</script>
</body>
</html>