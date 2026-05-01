@extends('layouts.app')

@php
    $active = 'dashboard';
    $title  = 'Dashboard';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Welcome back! Here's what's happening with your fleet today.</p>
        </div>
    </div>

    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-label">Total Trucks</div>
            <div class="metric-value">{{ $totalTrucks }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Drivers</div>
            <div class="metric-value">{{ $totalDrivers }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Available Trucks</div>
            <div class="metric-value metric-green">{{ $availableTrucks }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Available Drivers</div>
            <div class="metric-value metric-blue">{{ $availableDrivers }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Pending Maintenance</div>
            <div class="metric-value metric-gold">{{ $pendingMaintenance }}</div>
        </div>
    </div>

    <div class="dashboard-panels">

        {{-- Revenue Trend --}}
        <section class="panel-card">
            <div class="panel-head">
                <h2 class="panel-title">Revenue Trend</h2>
                <span class="chip-light" id="range-label">Last 6 Months</span>
            </div>

            {{-- Summary chips --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;">
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;">
                    <div style="font-size:10px;font-weight:600;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;margin-bottom:4px;">Total Revenue</div>
                    <div id="total-rev" style="font-size:22px;font-weight:700;color:#2563eb;">—</div>
                </div>
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;">
                    <div style="font-size:10px;font-weight:600;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;margin-bottom:4px;">Highest Month</div>
                    <div id="high-rev" style="font-size:22px;font-weight:700;color:#059669;">—</div>
                </div>
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;">
                    <div style="font-size:10px;font-weight:600;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;margin-bottom:4px;">Monthly Avg</div>
                    <div id="avg-rev" style="font-size:22px;font-weight:700;color:#d97706;">—</div>
                </div>
            </div>

            {{-- Range toggles --}}
            <div style="display:flex;gap:6px;margin-bottom:14px;">
                <button class="filter-range active" onclick="setRange(6,this)">6 Months</button>
                <button class="filter-range" onclick="setRange(9,this)">9 Months</button>
                <button class="filter-range" onclick="setRange(12,this)">12 Months</button>
            </div>

            <div style="position:relative;height:220px;">
                <canvas id="revenueChart" role="img" aria-label="Monthly revenue bar chart">Revenue data by month.</canvas>
            </div>

            <div style="display:flex;gap:14px;margin-top:12px;font-size:11px;color:var(--muted);">
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="width:10px;height:10px;border-radius:2px;background:#1e3a8a;display:inline-block;"></span>Trip Revenue
                </span>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="width:10px;height:10px;border-radius:2px;background:#60a5fa;display:inline-block;"></span>Net Profit
                </span>
            </div>
        </section>

        {{-- Active Trips --}}
        <section class="panel-card">
            <div class="panel-head">
                <h2 class="panel-title">Active Trips</h2>
                <a href="{{ url('/trips') }}" class="small-link">View All</a>
            </div>
            <div class="trip-list">
                @forelse ($activeTrips as $trip)
                    <article class="trip-card">
                        <div class="trip-head">
                            <div>
                                <div class="trip-code">{{ $trip->truck->truck_code ?? '—' }}</div>
                                <div class="trip-driver">{{ $trip->driver->full_name ?? '—' }}</div>
                            </div>
                            <div class="trip-status">{{ $trip->status }}</div>
                        </div>
                        <div class="trip-route">
                            {{ $trip->origin ?? '—' }} → {{ $trip->destination ?? '—' }}
                        </div>
                    </article>
                @empty
                    <div class="empty-text">No active trips yet.</div>
                @endforelse
            </div>
        </section>

    </div>
@endsection

@push('styles')
<style>
    .filter-range {
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--muted);
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all .15s;
    }
    .filter-range.active {
        background: var(--navy);
        color: #fff;
        border-color: var(--navy);
    }
    .filter-range:hover:not(.active) {
        background: #f8fafc;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const allData = @json($revenueData);
    let chart;

    function fmt(v) {
        if (v >= 1000000) return '₱' + (v / 1000000).toFixed(1) + 'M';
        if (v >= 1000)    return '₱' + Math.round(v / 1000) + 'K';
        return '₱' + v;
    }

    function buildChart(months) {
        const labels  = allData.labels.slice(-months);
        const revenue = allData.revenue.slice(-months);
        const profit  = allData.profit.slice(-months);

        document.getElementById('total-rev').textContent = fmt(revenue.reduce((a, b) => a + b, 0));
        document.getElementById('high-rev').textContent  = fmt(Math.max(...revenue));
        document.getElementById('avg-rev').textContent   = fmt(Math.round(revenue.reduce((a, b) => a + b, 0) / revenue.length));

        if (chart) chart.destroy();

        chart = new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Trip Revenue',
                        data: revenue,
                        backgroundColor: '#1e3a8a',
                        borderRadius: 5,
                        borderSkipped: false,
                        barPercentage: .55,
                        categoryPercentage: .75,
                    },
                    {
                        label: 'Net Profit',
                        data: profit,
                        backgroundColor: '#60a5fa',
                        borderRadius: 5,
                        borderSkipped: false,
                        barPercentage: .55,
                        categoryPercentage: .75,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: ctx => ' ' + fmt(ctx.raw) },
                        backgroundColor: '#0f1a2e',
                        titleColor: '#fff',
                        bodyColor: '#94a3b8',
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 11 }, color: '#64748b' },
                        border: { display: false },
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: 'Poppins', size: 10 }, color: '#64748b', callback: v => fmt(v) },
                        border: { display: false },
                    }
                }
            }
        });
    }

    function setRange(n, btn) {
        document.querySelectorAll('.filter-range').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('range-label').textContent =
            n === 12 ? 'Last 12 Months' : n === 9 ? 'Last 9 Months' : 'Last 6 Months';
        buildChart(n);
    }

    buildChart(6);
</script>
@endpush