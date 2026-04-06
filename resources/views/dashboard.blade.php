@extends('layouts.app')

@php
    $active = 'dashboard';
    $title = 'Dashboard';
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
            <div class="metric-value">{{ $availableTrucks }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Available Drivers</div>
            <div class="metric-value">{{ $availableDrivers }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Pending Maintenance</div>
            <div class="metric-value">{{ $pendingMaintenance }}</div>
        </div>
    </div>

    <div class="dashboard-panels">
        <section class="panel-card">
            <div class="panel-head">
                <h2 class="section-title panel-title">Revenue Trend</h2>
                <span class="chip-light">Last 7 Months</span>
            </div>
            <div class="chart-placeholder">
                <div class="chart-bars">
                    <span style="height:45%"></span>
                    <span style="height:62%"></span>
                    <span style="height:31%"></span>
                    <span style="height:82%"></span>
                    <span style="height:44%"></span>
                    <span style="height:57%"></span>
                </div>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-head">
                <h2 class="section-title panel-title">Active Trips</h2>
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

