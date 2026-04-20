@extends('layouts.app')

@php
    $active = 'fleet';
    $title  = 'Fleet Management';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Fleet Management</h1>
            <p class="page-subtitle">Manage truck details, availability, and assignments for fleet operations.</p>
        </div>
        <div class="header-actions">
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input class="search-input" placeholder="Search trucks">
            </div>
            <button class="btn btn-filter">
                <span class="material-symbols-outlined">filter_alt</span>
                Filter
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="notice-line" style="border-left-color:#059669; background:#f0fdf4; color:#065f46;">
            {{ session('success') }}
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="notice-line" style="border-left-color:#dc2626; background:#fef2f2; color:#991b1b;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="notice-line">
        Trucks currently on scheduled trips will become available when trip tickets are marked complete.
    </div>

    <div class="fleet-list">
        @forelse ($trucks as $truck)
            <article class="fleet-card">
                <div class="fleet-card-head">
                    <div>
                        <div class="fleet-code">{{ $truck->truck_code }}</div>
                        <div class="fleet-sub">{{ $truck->model ?? 'No model provided' }}</div>
                        <div class="fleet-status">{{ $truck->status }}</div>
                    </div>
                    <span class="status-badge status-{{ strtolower(str_replace(['-',' '], '', $truck->status)) }}">
                        {{ $truck->status }}
                    </span>
                </div>
                <div class="fleet-grid">
                    <div><strong>Plate Number</strong><br>{{ $truck->plate_number ?? '—' }}</div>
                    <div><strong>Notes</strong><br>{{ $truck->notes ?? '—' }}</div>
                </div>
                <details style="margin-top:12px;">
                    <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer;">
                        <span class="material-symbols-outlined">edit</span>
                        Edit Info
                    </summary>
                    <div style="margin-top:10px;">
                        <form action="{{ url('/fleet/'.$truck->id) }}" method="POST" class="fleet-edit-form">
                            @csrf
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Truck Code</label>
                                <input name="truck_code" class="search-input" style="width:100%;" value="{{ $truck->truck_code }}" required>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Plate Number</label>
                                <input name="plate_number" class="search-input" style="width:100%;" value="{{ $truck->plate_number }}">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Model</label>
                                <input name="model" class="search-input" style="width:100%;" value="{{ $truck->model }}">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Status</label>
                                <select name="status" class="search-input" style="width:100%;" required>
                                    @foreach (['Available','In-Transit','Maintenance','Inactive'] as $st)
                                        <option value="{{ $st }}" {{ $truck->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Notes</label>
                                <input name="notes" class="search-input" style="width:100%;" value="{{ $truck->notes }}">
                            </div>
                            <button class="btn btn-primary" type="submit" style="align-self:flex-end;">Save</button>
                        </form>
                        <form action="{{ url('/fleet/'.$truck->id.'/delete') }}" method="POST" style="margin-top:8px;">
                            @csrf
                            <button class="btn btn-cancel" type="submit"
                                onclick="return confirm('Delete this truck?')">Delete Truck</button>
                        </form>
                    </div>
                </details>
            </article>
        @empty
            <div class="drivers-section">
                <p class="no-data">No trucks found. Add one below.</p>
            </div>
        @endforelse
    </div>

    {{-- Add Truck --}}
    <div class="drivers-section" style="margin-top:16px;">
        <h3 class="section-title">Add New Truck</h3>
        <form action="{{ url('/fleet') }}" method="POST" class="fleet-edit-form">
            @csrf
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Truck Code <span style="color:#ef4444;">*</span></label>
                <input name="truck_code" class="search-input" style="width:100%;" placeholder="e.g. ZMG-003" required>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Plate Number</label>
                <input name="plate_number" class="search-input" style="width:100%;" placeholder="Plate #">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Model</label>
                <input name="model" class="search-input" style="width:100%;" placeholder="Model">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Status <span style="color:#ef4444;">*</span></label>
                <select name="status" class="search-input" style="width:100%;" required>
                    <option value="Available">Available</option>
                    <option value="In-Transit">In-Transit</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Notes</label>
                <input name="notes" class="search-input" style="width:100%;" placeholder="Notes">
            </div>
            <button class="btn btn-primary" type="submit" style="align-self:flex-end;">
                <span class="material-symbols-outlined">add</span>
                Add Truck
            </button>
        </form>
    </div>
@endsection
