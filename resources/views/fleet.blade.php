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
            <button class="btn btn-primary" id="addFleetBtn">
                <span class="material-symbols-outlined">add</span>
                Fleet
            </button>
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input
                    class="search-input"
                    id="truckSearch"
                    placeholder="Search trucks"
                    autocomplete="off"
                >
            </div>
            <div style="position:relative;">
                <button class="btn btn-filter" id="filterBtn">
                    <span class="material-symbols-outlined">filter_alt</span>
                    Filter
                </button>
                <div id="filterPanel" style="
                    display:none; position:absolute; right:0; top:calc(100% + 6px);
                    background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                    box-shadow:0 8px 24px rgba(0,0,0,.12); padding:14px 16px;
                    min-width:180px; z-index:500;">
                    @foreach (['Available','In-Transit','Maintenance','Inactive'] as $st)
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#1e293b; cursor:pointer; padding:4px 0;">
                            <input type="checkbox" class="status-filter" value="{{ strtolower(str_replace(['-',' '], '', $st)) }}" checked
                                style="accent-color:#0f1a2e; width:14px; height:14px; cursor:pointer;">
                            {{ $st }}
                        </label>
                    @endforeach
                    <button onclick="clearFilters()" style="
                        margin-top:10px; width:100%; padding:6px; border-radius:6px;
                        border:1px solid #e2e8f0; background:#f8fafc; font-size:12px;
                        font-family:'Poppins',sans-serif; font-weight:600; color:#64748b;
                        cursor:pointer;">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="notice-line">
        Trucks currently on scheduled trips will become available when trip tickets are marked complete.
    </div>

    <div class="fleet-list">
        @forelse ($trucks as $truck)
            <article class="fleet-card" data-id="{{ $truck->id }}">
                <div class="fleet-card-head">
                    <div>
                        <div class="fleet-code">{{ $truck->truck_code }}</div>
                        <div class="fleet-sub">{{ $truck->model ?? 'No model provided' }}</div>
                    </div>
                    <span class="status-badge status-{{ strtolower(str_replace(['-',' '], '', $truck->status)) }}">
                        {{ $truck->status }}
                    </span>
                </div>
                <div class="fleet-grid">
                    <div><strong>Plate Number</strong><br>{{ $truck->plate_number ?? '—' }}</div>
                    <div><strong>Notes</strong><br>{{ $truck->notes ?? '—' }}</div>
                </div>
                <details style="margin-top:12px;" class="fleet-edit-details">
                    <summary class="btn btn-secondary" style="display:inline-flex; cursor:pointer;">
                        <span class="material-symbols-outlined">edit</span>
                        Edit Info
                    </summary>
                    <div style="margin-top:10px;">
                        <form class="fleet-edit-form" data-id="{{ $truck->id }}">
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
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Notes</label>
                                <input name="notes" class="search-input" style="width:100%;" value="{{ $truck->notes }}">
                            </div>
                            @if ($truck->status === 'Available' || $truck->status === 'Inactive')
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Status</label>
                                <select name="status" class="search-input" style="width:100%;">
                                    <option value="Available" {{ $truck->status === 'Available' ? 'selected' : '' }}>Available</option>
                                    <option value="Inactive"  {{ $truck->status === 'Inactive'  ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            @else
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Status</label>
                                <input class="search-input" style="width:100%; background:#f1f5f9; color:#64748b;" value="{{ $truck->status }}" readonly>
                            </div>
                            @endif

                            {{-- Edit Error Box --}}
                            <div class="fleet-form-error" style="
                                display:none; padding:12px 14px;
                                background:#fef2f2; border:1px solid #fca5a5; border-radius:8px;">
                                <div style="display:flex; align-items:flex-start; gap:8px;">
                                    <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                                    <ul class="fleet-form-error-list" style="
                                        margin:0; padding:0; list-style:none;
                                        font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                                </div>
                            </div>

                            <div style="display:flex; gap:8px; align-items:center;">
                                <button class="btn btn-primary fleet-save-btn" type="submit" style="align-self:flex-end;">Save</button>
                                <button class="btn btn-cancel fleet-delete-btn" type="button"
                                    data-id="{{ $truck->id }}"
                                    onclick="confirmDeleteTruck({{ $truck->id }}, {{ json_encode($truck->truck_code) }})">
                                    Delete Truck
                                </button>
                            </div>
                        </form>
                    </div>
                </details>
            </article>
        @empty
            <div class="drivers-section">
                <p class="no-data">No trucks found.</p>
            </div>
        @endforelse
    </div>

    {{-- ===================== ADD FLEET MODAL ===================== --}}
    <div class="modal" id="addFleetModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="material-symbols-outlined">local_shipping</span>
                <h2>Add New Truck</h2>
            </div>
            <form id="addFleetForm">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Truck Code <span class="required">*</span></label>
                            <input type="text" name="truck_code" placeholder="e.g. ZMG-003" required>
                        </div>
                        <div class="form-group">
                            <label>Plate Number <span class="required">*</span></label>
                            <input type="text" name="plate_number" placeholder="Plate #" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" name="model" placeholder="Model">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <input type="text" name="notes" placeholder="Notes">
                    </div>

                    {{-- Add Error Box --}}
                    <div id="addFleetError" style="
                        display:none; margin-top:14px; padding:12px 14px;
                        background:#fef2f2; border:1px solid #fca5a5; border-radius:8px;">
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                            <ul id="addFleetErrorList" style="
                                margin:0; padding:0; list-style:none;
                                font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeFleetModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">add</span>
                        Add Truck
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== DELETE WARNING MODALS ===================== --}}
    <div class="modal" id="deleteTruckModal1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">warning</span>
                <h2>Delete Truck</h2>
            </div>
            <div class="modal-body">
                <p><strong>Truck:</strong> <span id="deleteTruckCode"></span></p>
                <p>Are you sure you want to delete this truck? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeFleetModal('deleteTruckModal1')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedToDeleteTruck()">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/fleet.js') }}"></script>
@endsection