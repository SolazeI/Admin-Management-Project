@extends('layouts.app')

@php
    $active = 'fleet';
    $title = 'Fleet Management';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Fleet Management</h1>
            <p class="page-subtitle">Manage truck details, availability, and assignments for fleet operations.</p>
        </div>
    </div>
    <div class="fleet-toolbar">
        <div class="fleet-tabs">
            <button class="btn btn-secondary">Truck List</button>
            <button class="btn btn-primary">Schedule Trip</button>
        </div>
        <div class="fleet-actions">
            <button class="btn btn-primary">+ New Trip</button>
            <input class="search-input" placeholder="Search">
            <button class="btn btn-filter">Filter</button>
        </div>
    </div>
    <div class="notice-line">These trucks are currently on scheduled trips. They will become available when trip tickets are marked complete.</div>

    @if (session('success'))
        <div class="table-container" style="padding:12px; margin-bottom:14px; border-left:4px solid #16a34a; background:#fff; border-radius:10px;">
            {{ session('success') }}
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="table-container" style="padding:12px; margin-bottom:14px; border-left:4px solid #dc2626;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="fleet-list">
        @forelse ($trucks as $truck)
            <article class="fleet-card">
                <div class="fleet-card-head">
                    <div>
                        <div class="fleet-code">{{ $truck->truck_code }}</div>
                        <div class="fleet-sub">{{ $truck->model ?? 'No model provided' }}</div>
                    </div>
                    <div class="fleet-status">{{ $truck->status }}</div>
                </div>
                <div class="fleet-grid">
                    <div><strong>Plate Number*</strong><br>{{ $truck->plate_number ?? '—' }}</div>
                    <div><strong>Notes*</strong><br>{{ $truck->notes ?? '—' }}</div>
                </div>
                <details style="margin-top:14px;">
                    <summary class="btn btn-secondary" style="display:inline-flex;">Edit Info</summary>
                    <div style="margin-top:10px;">
                        <form action="{{ url('/fleet/' . $truck->id) }}" method="POST" class="fleet-edit-form">
                            @csrf
                            <input name="truck_code" class="search-input" value="{{ $truck->truck_code }}" required>
                            <input name="plate_number" class="search-input" value="{{ $truck->plate_number }}">
                            <input name="model" class="search-input" value="{{ $truck->model }}">
                            <select name="status" class="search-input" required>
                                @foreach (['Available','In-Transit','Maintenance','Inactive'] as $st)
                                    <option value="{{ $st }}" {{ $truck->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                            <input name="notes" class="search-input" value="{{ $truck->notes }}">
                            <button class="btn btn-secondary" type="submit">Save</button>
                        </form>
                        <form action="{{ url('/fleet/' . $truck->id . '/delete') }}" method="POST" style="margin-top:8px;">
                            @csrf
                            <button class="btn btn-cancel" type="submit">Delete</button>
                        </form>
                    </div>
                </details>
            </article>
        @empty
            <div class="empty-text">No trucks yet.</div>
        @endforelse
    </div>

    <div class="table-container" style="margin-top:16px; background:#fff; border-radius:12px; padding:16px;">
        <h3 style="margin-top:0;">Add Truck</h3>
        <form action="{{ url('/fleet') }}" method="POST" class="fleet-edit-form">
            @csrf
            <input name="truck_code" class="search-input" placeholder="Truck Code (ZMG-003)" required>
            <input name="plate_number" class="search-input" placeholder="Plate #">
            <input name="model" class="search-input" placeholder="Model">
            <select name="status" class="search-input" required>
                <option value="Available">Available</option>
                <option value="In-Transit">In-Transit</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Inactive">Inactive</option>
            </select>
            <input name="notes" class="search-input" placeholder="Notes">
            <button class="btn btn-primary" type="submit">Add Truck</button>
        </form>
    </div>
@endsection

