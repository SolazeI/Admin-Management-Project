@extends('layouts.app')

@php
    $active = 'trips';
    $title = 'Trip Tickets';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Trip tickets</h1>
            <p class="page-subtitle">Manage weekly/Monthly trip entries and view monthly summaries</p>
        </div>
    </div>

    @if (session('success'))
        <div class="table-container" style="padding:12px; margin-bottom:14px; border-left:4px solid #16a34a;">
            {{ session('success') }}
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="table-container" style="padding:12px; margin-bottom:14px; border-left:4px solid #dc2626;">
            {{ $errors->first() }}
        </div>
    @endif

    <button class="btn btn-primary" style="margin-bottom:12px;">+ Trip Ticket Entry</button>

    <div class="trip-entry-panel">
        <div class="trip-entry-header">Weekly/Monthly Trip Ticket Entry</div>
        <div class="trip-entry-subtitle">Record trip details for the selected week</div>
        <form action="{{ url('/trips') }}" method="POST" class="trip-entry-form">
            @csrf
            <input type="date" name="date_issued" class="search-input" placeholder="Date Issued">
            <select name="truck_id" class="search-input" required>
                <option value="">Select Truck</option>
                @foreach ($trucks as $truck)
                    <option value="{{ $truck->id }}">{{ $truck->truck_code }}</option>
                @endforeach
            </select>
            <select name="driver_id" class="search-input" required>
                <option value="">Select Driver</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                @endforeach
            </select>
            <input name="trip_no" class="search-input" placeholder="Week Number / Trip No.">

            <input name="distance_km" class="search-input" placeholder="No. of Trips / Distance">
            <input name="origin" class="search-input" placeholder="Loading">
            <input name="destination" class="search-input" placeholder="Destination">

            <input type="datetime-local" name="departure_time" class="search-input" placeholder="Departure Time">
            <input type="datetime-local" name="arrival_time" class="search-input" placeholder="Arrival Time">
            <input name="amount" class="search-input" placeholder="Amount">
            <select name="status" class="search-input" required>
                @foreach (['Draft','In-Transit','Completed','Cancelled'] as $st)
                    <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </select>

            <textarea name="remarks" class="search-input trip-remarks" placeholder="Remarks"></textarea>
            <button class="btn btn-primary trip-save-btn" type="submit">Save Record</button>
        </form>
    </div>

    <div class="table-container" style="margin-top:16px;">
        <table class="drivers-table">
            <thead>
                <tr>
                    <th>TRIP NO.</th>
                    <th>TRUCK</th>
                    <th>DRIVER</th>
                    <th>ORIGIN</th>
                    <th>DESTINATION</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
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
                        <td>{{ $trip->status }}</td>
                        <td>
                            <details>
                                <summary class="btn btn-secondary" style="display:inline-flex;">Edit</summary>
                                <div style="margin-top:10px;">
                                    <form action="{{ url('/trips/' . $trip->id) }}" method="POST" style="display:grid; grid-template-columns: repeat(4, minmax(160px, 1fr)); gap:10px;">
                                        @csrf
                                        <input name="trip_no" class="search-input" value="{{ $trip->trip_no }}" required>
                                        <select name="truck_id" class="search-input" required>
                                            @foreach ($trucks as $truck)
                                                <option value="{{ $truck->id }}" {{ $trip->truck_id === $truck->id ? 'selected' : '' }}>
                                                    {{ $truck->truck_code }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="driver_id" class="search-input" required>
                                            @foreach ($drivers as $driver)
                                                <option value="{{ $driver->id }}" {{ $trip->driver_id === $driver->id ? 'selected' : '' }}>
                                                    {{ $driver->full_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="status" class="search-input" required>
                                            @foreach (['Draft','In-Transit','Completed','Cancelled'] as $st)
                                                <option value="{{ $st }}" {{ $trip->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                            @endforeach
                                        </select>

                                        <input name="origin" class="search-input" value="{{ $trip->origin }}">
                                        <input name="destination" class="search-input" value="{{ $trip->destination }}">
                                        <input type="date" name="date_issued" class="search-input" value="{{ optional($trip->date_issued)->format('Y-m-d') }}">
                                        <input name="distance_km" class="search-input" value="{{ $trip->distance_km }}">

                                        <input type="datetime-local" name="departure_time" class="search-input" value="{{ $trip->departure_time ? \Illuminate\Support\Carbon::parse($trip->departure_time)->format('Y-m-d\\TH:i') : '' }}">
                                        <input type="datetime-local" name="arrival_time" class="search-input" value="{{ $trip->arrival_time ? \Illuminate\Support\Carbon::parse($trip->arrival_time)->format('Y-m-d\\TH:i') : '' }}">
                                        <input name="amount" class="search-input" value="{{ $trip->amount }}">
                                        <button class="btn btn-secondary" type="submit">Save</button>

                                        <textarea name="remarks" class="search-input" style="grid-column:1 / -1; height:70px;">{{ $trip->remarks }}</textarea>
                                    </form>
                                    <form action="{{ url('/trips/' . $trip->id . '/delete') }}" method="POST" style="margin-top:8px;">
                                        @csrf
                                        <button class="btn btn-cancel" type="submit">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:#777;">No trip tickets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

