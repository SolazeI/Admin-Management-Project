@extends('layouts.app')

@php
    $active = 'reports';
    $title = 'Reports';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">View and generate summaries for trips and fleet maintenance.</p>
        </div>
        <div class="header-actions">
            <input class="search-input" placeholder="Find Record">
            <button class="btn btn-filter">Filter</button>
        </div>
    </div>

    <div class="fleet-tabs" style="margin-bottom:16px;">
        <button class="btn btn-secondary">Driver Records</button>
        <button class="btn btn-primary">Maintenance Record</button>
    </div>

    <div class="metric-grid metric-grid-reports">
        <div class="metric-card">
            <div class="metric-label">Total Revenue</div>
            <div class="metric-value metric-green">₱{{ number_format($totalRevenue, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Driver Expenses</div>
            <div class="metric-value metric-blue">₱{{ number_format($driverExpenses, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Maintenance Cost</div>
            <div class="metric-value metric-red">₱{{ number_format($totalMaintenanceCost, 0) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Net Profit</div>
            <div class="metric-value metric-gold">₱{{ number_format($netProfit, 0) }}</div>
        </div>
    </div>

    <div class="table-container" style="background:#fff; border-radius:14px; padding:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
            <h2 class="section-title" style="margin:0;">Driver Trip Records ({{ $driverTripRecords->count() }})</h2>
            <div style="font-size:12px; color:#666;">
                Trucks: <strong>{{ $truckCount }}</strong> • Trips: <strong>{{ $tripCount }}</strong>
            </div>
        </div>
        <table class="drivers-table">
            <thead>
                <tr>
                    <th>DRIVER</th>
                    <th>TRUCK (LATEST)</th>
                    <th>TOTAL TRIPS</th>
                    <th>EXPENSES</th>
                    <th>HAULING</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($driverTripRecords as $driver)
                    <tr>
                        <td>{{ $driver->full_name }}</td>
                        <td>—</td>
                        <td>{{ $driver->total_trips_count }}</td>
                        <td class="metric-red">₱0</td>
                        <td class="metric-blue">₱0</td>
                        <td>...</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#777;">No records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

