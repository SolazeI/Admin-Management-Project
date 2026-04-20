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
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" placeholder="Search trips">
            </div>
            <button class="btn btn-filter">
                <span class="material-symbols-outlined">filter_alt</span>
                Filter
            </button>
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

    {{-- Entry Panel --}}
    <div class="trip-entry-panel">
        <div class="trip-entry-header">Weekly / Monthly Trip Ticket Entry</div>
        <div class="trip-entry-subtitle">Record trip details for the selected period</div>
        <form action="{{ url('/trips') }}" method="POST" class="trip-entry-form">
            @csrf
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
            <div style="grid-column: auto;">
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
        </form>
    </div>

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
                                            <select name="truck_id" class="search-input" style="width:100%;" required>
                                                @foreach ($trucks as $truck)
                                                    <option value="{{ $truck->id }}" {{ $trip->truck_id === $truck->id ? 'selected' : '' }}>
                                                        {{ $truck->truck_code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="driver_id" class="search-input" style="width:100%;" required>
                                                @foreach ($drivers as $driver)
                                                    <option value="{{ $driver->id }}" {{ $trip->driver_id === $driver->id ? 'selected' : '' }}>
                                                        {{ $driver->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="status" class="search-input" style="width:100%;" required>
                                                @foreach (['Draft','In-Transit','Completed','Cancelled'] as $st)
                                                    <option value="{{ $st }}" {{ $trip->status === $st ? 'selected' : '' }}>{{ $st }}</option>
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
@endsection
