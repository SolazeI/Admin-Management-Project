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
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" placeholder="Search records">
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

    {{-- Add Record Form --}}
    <div class="drivers-section" style="margin-bottom:16px;">
        <h2 class="section-title">Add Maintenance Record</h2>
        <form action="{{ url('/maintenance') }}" method="POST"
            style="display:grid; grid-template-columns: repeat(4, minmax(160px,1fr)); gap:10px; align-items:end;">
            @csrf
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
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Issue Description <span style="color:#ef4444;">*</span></label>
                <input name="issue_description" class="search-input" style="width:100%;" placeholder="Issue description" required>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Start Date</label>
                <input type="date" name="start_date" class="search-input" style="width:100%;">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Status <span style="color:#ef4444;">*</span></label>
                <select name="status" class="search-input" style="width:100%;" required>
                    @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                        <option value="{{ $st }}">{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Notes</label>
                <input name="notes" class="search-input" style="width:100%;" placeholder="Notes (optional)">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Cost</label>
                <input name="cost" class="search-input" style="width:100%;" placeholder="₱ Cost (optional)">
            </div>
            <button class="btn btn-primary" type="submit" style="grid-column: 3 / 5; justify-content:center;">
                <span class="material-symbols-outlined">add</span>
                Add Record
            </button>
        </form>
    </div>

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
                            <td>
                                <details>
                                    <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer; font-size:12px;">
                                        <span class="material-symbols-outlined">build</span>
                                        Update
                                    </summary>
                                    <div style="margin-top:10px;">
                                        <form action="{{ url('/maintenance/'.$rec->id) }}" method="POST"
                                            style="display:grid; grid-template-columns: repeat(4, minmax(140px,1fr)); gap:9px; align-items:end;">
                                            @csrf
                                            <select name="truck_id" class="search-input" style="width:100%;" required>
                                                @foreach ($trucks as $truck)
                                                    <option value="{{ $truck->id }}" {{ $rec->truck_id === $truck->id ? 'selected' : '' }}>
                                                        {{ $truck->truck_code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input name="issue_description" class="search-input" style="width:100%;" value="{{ $rec->issue_description }}" required>
                                            <input type="date" name="start_date" class="search-input" style="width:100%;" value="{{ $rec->start_date }}">
                                            <select name="status" class="search-input" style="width:100%;" required>
                                                @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                                                    <option value="{{ $st }}" {{ $rec->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                                @endforeach
                                            </select>
                                            <input name="notes" class="search-input" style="width:100%;" value="{{ $rec->notes }}" placeholder="Notes">
                                            <input name="cost" class="search-input" style="width:100%;" value="{{ $rec->cost }}" placeholder="Cost">
                                            <button class="btn btn-primary" type="submit" style="grid-column: 3 / 5; justify-content:center;">Save Changes</button>
                                        </form>
                                        <form action="{{ url('/maintenance/'.$rec->id.'/delete') }}" method="POST" style="margin-top:8px;">
                                            @csrf
                                            <button class="btn btn-cancel" type="submit"
                                                onclick="return confirm('Delete this record?')">Delete Record</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="no-data">No maintenance records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
