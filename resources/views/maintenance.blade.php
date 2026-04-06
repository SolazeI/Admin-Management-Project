@extends('layouts.app')

@php
    $active = 'maintenance';
    $title = 'Maintenance';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Maintenance</h1>
            <p class="page-subtitle">Manage weekly/monthly maintenance entries and view summaries.</p>
        </div>
        <div class="header-actions">
            <input class="search-input" placeholder="Search">
            <button class="btn btn-filter">Filter</button>
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

    <div class="table-container" style="background:#fff; border-radius:14px; padding:16px; margin-bottom:16px;">
        <h2 class="section-title" style="margin-top:0;">Pending Maintenance ({{ $records->count() }})</h2>
        <form action="{{ url('/maintenance') }}" method="POST" style="display:grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap:12px; align-items:end; margin-bottom:16px;">
            @csrf
            <select name="truck_id" class="search-input" required>
                <option value="">Select Truck</option>
                @foreach ($trucks as $truck)
                    <option value="{{ $truck->id }}">{{ $truck->truck_code }}</option>
                @endforeach
            </select>
            <input name="issue_description" class="search-input" placeholder="Issue description" required>
            <input type="date" name="start_date" class="search-input" placeholder="Start date">
            <select name="status" class="search-input" required>
                @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                    <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </select>

            <input name="notes" class="search-input" placeholder="Notes (optional)">
            <input name="cost" class="search-input" placeholder="Cost (optional)">
            <button class="btn btn-primary" type="submit" style="grid-column: 3 / 5; justify-content:center;">
                <span class="material-symbols-outlined">add</span>
                Add Record
            </button>
        </form>
        <table class="drivers-table">
            <thead>
                <tr>
                    <th>TRUCK</th>
                    <th>ISSUE DESCRIPTION</th>
                    <th>START DATE</th>
                    <th>STATUS</th>
                    <th>NOTES</th>
                    <th>COST</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $rec)
                    <tr>
                        <td>{{ $rec->truck->truck_code ?? '—' }}</td>
                        <td>{{ $rec->issue_description }}</td>
                        <td>{{ $rec->start_date ?? '—' }}</td>
                        <td>{{ $rec->status }}</td>
                        <td>{{ $rec->notes ?? '—' }}</td>
                        <td>{{ $rec->cost ?? '—' }}</td>
                        <td style="white-space:nowrap;">
                            <details>
                                <summary class="btn btn-secondary" style="display:inline-flex;">Start Work</summary>
                                <div style="margin-top:10px;">
                                    <form action="{{ url('/maintenance/' . $rec->id) }}" method="POST"
                                        style="display:grid; grid-template-columns: repeat(4, minmax(160px, 1fr)); gap:10px;">
                                        @csrf
                                        <select name="truck_id" class="search-input" required>
                                            @foreach ($trucks as $truck)
                                                <option value="{{ $truck->id }}" {{ $rec->truck_id === $truck->id ? 'selected' : '' }}>
                                                    {{ $truck->truck_code }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input name="issue_description" class="search-input" value="{{ $rec->issue_description }}" required>
                                        <input type="date" name="start_date" class="search-input" value="{{ $rec->start_date }}">
                                        <select name="status" class="search-input" required>
                                            @foreach (['Pending','In-Progress','Completed','Cancelled'] as $st)
                                                <option value="{{ $st }}" {{ $rec->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                            @endforeach
                                        </select>

                                        <input name="notes" class="search-input" value="{{ $rec->notes }}">
                                        <input name="cost" class="search-input" value="{{ $rec->cost }}">
                                        <button class="btn btn-secondary" type="submit" style="grid-column: 3 / 5;">Save</button>
                                    </form>
                                    <form action="{{ url('/maintenance/' . $rec->id . '/delete') }}" method="POST" style="margin-top:8px;">
                                        @csrf
                                        <button class="btn btn-cancel" type="submit">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:#777;">No maintenance records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

