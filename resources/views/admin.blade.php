@extends('layouts.app')

@php
    $active = 'drivers';
    $title  = 'Driver Management';
@endphp

@section('content')
    <div class="content-header app-divider">
        <div class="header-text">
            <h1 class="page-title">Driver Management</h1>
            <p class="page-subtitle">Manage your driver information and assignments</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" id="archivedBtn">
                <span class="material-symbols-outlined">folder</span>
                Archived
            </button>
            <button class="btn btn-primary" id="addDriverBtn">
                <span class="material-symbols-outlined">person_add</span>
                Add Driver
            </button>
            <div class="search-wrapper">
                <span class="material-symbols-outlined search-icon">search</span>
                <input type="text" class="search-input" id="searchInput" placeholder="Find Driver">
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
                    @foreach (['Available','Covering'] as $st)
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

    <div class="drivers-section">
        <h2 class="section-title">All Drivers</h2>
        <div class="table-container">
            <table class="drivers-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>License No.</th>
                        <th>Assigned Truck</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="driversTableBody">
                    @forelse($drivers ?? [] as $driver)
                        <tr>
                            <td>
                                <span class="material-symbols-outlined driver-icon">person</span>
                                {{ $driver->full_name }}
                            </td>
                            <td>{{ $driver->phone_number }}</td>
                            <td>{{ $driver->license_number }}</td>
                            <td>{{ $driver->assigned_truck ?? 'Unassigned' }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(['-',' '], '', $driver->status)) }}">
                                    {{ $driver->status }}
                                </span>
                            </td>
                            <td>
                                <button class="action-btn" onclick="openActionsMenu({{ $driver->id }})">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                                <div class="actions-menu" id="menu-{{ $driver->id }}">
                                    <button disabled>Select Action</button>
                                    <button onclick="viewDriver({{ $driver->id }})">View</button>
                                    <button onclick="editDriver({{ $driver->id }})">Edit</button>
                                    <button onclick="confirmArchive({{ $driver->id }}, {{ json_encode($driver->full_name) }})">Archive</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="no-data">No drivers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================== ADD DRIVER MODAL ===================== --}}
    <div class="modal" id="addDriverModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="material-symbols-outlined">person_add</span>
                <h2>Add New Driver</h2>
            </div>
            <form id="addDriverForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="text" name="phone_number" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>License Number <span class="required">*</span></label>
                            <input type="text" name="license_number" required>
                        </div>
                        <div class="form-group">
                            <label>License Expiry Date <span class="required">*</span></label>
                            <div class="date-input-wrapper">
                                <input type="text" name="license_expiry_date" id="addLicenseExpiry"
                                    placeholder="mm/dd/yyyy" required>
                                <span class="material-symbols-outlined date-icon">calendar_today</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Address <span class="required">*</span></label>
                        <input type="text" name="address" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Emergency Contact <span class="required">*</span></label>
                        <input type="text" name="emergency_contact" required>
                    </div>
                    <div class="form-group">
                        <label>File <span class="required">*</span></label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" name="file" id="fileInput"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required style="display:none;">
                            <span class="material-symbols-outlined">upload_file</span>
                            <p>Click to upload file</p>
                        </div>
                    </div>

                    {{-- Error Box --}}
                    <div id="addDriverError" style="
                        display:none; margin-top:14px; padding:12px 14px;
                        background:#fef2f2; border:1px solid #fca5a5; border-radius:8px;">
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                            <ul id="addDriverErrorList" style="
                                margin:0; padding:0; list-style:none;
                                font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('addDriverModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Driver</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== VIEW DRIVER MODAL ===================== --}}
    <div class="modal" id="viewDriverModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="material-symbols-outlined">person</span>
                <h2>Driver Information</h2>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="viewFullName" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" id="viewPhoneNumber" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>License Number</label>
                        <input type="text" id="viewLicenseNumber" readonly>
                    </div>
                    <div class="form-group">
                        <label>License Expiry Date</label>
                        <div class="date-input-wrapper">
                            <input type="text" id="viewLicenseExpiry" readonly>
                            <span class="material-symbols-outlined date-icon">calendar_today</span>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Address</label>
                    <input type="text" id="viewAddress" readonly>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Emergency Contact</label>
                    <input type="text" id="viewEmergencyContact" readonly>
                </div>
                <div class="form-group">
                    <label>File</label>
                    <button type="button" class="btn-view-file" id="viewFileBtnModal"
                        onclick="viewDriverFileFromModal()" style="display:none;">
                        <span class="material-symbols-outlined">visibility</span>
                        View File
                    </button>
                    <input type="hidden" id="viewFilePath">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('viewDriverModal')">Close</button>
            </div>
        </div>
    </div>

    {{-- ===================== EDIT DRIVER MODAL ===================== --}}
    <div class="modal" id="editDriverModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="material-symbols-outlined">edit</span>
                <h2>Edit Driver Info</h2>
            </div>
            <form id="editDriverForm" enctype="multipart/form-data">
                <input type="hidden" name="driver_id" id="editDriverId">
                <input type="hidden" name="current_file_path" id="currentFilePath">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" id="editFullName" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="text" name="phone_number" id="editPhoneNumber" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>License Number <span class="required">*</span></label>
                            <input type="text" name="license_number" id="editLicenseNumber" required>
                        </div>
                        <div class="form-group">
                            <label>License Expiry Date <span class="required">*</span></label>
                            <div class="date-input-wrapper">
                                <input type="text" name="license_expiry_date" id="editLicenseExpiry"
                                    placeholder="mm/dd/yyyy" required>
                                <span class="material-symbols-outlined date-icon">calendar_today</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Address <span class="required">*</span></label>
                        <input type="text" name="address" id="editAddress" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Emergency Contact <span class="required">*</span></label>
                        <input type="text" name="emergency_contact" id="editEmergencyContact" required>
                    </div>
                    <div class="form-group">
                        <label>File</label>
                        <button type="button" class="btn-view-file" id="viewFileBtn" onclick="viewDriverFile()">
                            <span class="material-symbols-outlined">visibility</span>
                            View Current File
                        </button>
                        <div class="file-upload-area" id="editFileUploadArea">
                            <input type="file" name="file" id="editFileInput"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display:none;">
                            <span class="material-symbols-outlined">cloud_upload</span>
                            <p>Click to upload new file (optional)</p>
                        </div>
                    </div>

                    {{-- Error Box --}}
                    <div id="editDriverError" style="
                        display:none; margin-top:14px; padding:12px 14px;
                        background:#fef2f2; border:1px solid #fca5a5; border-radius:8px;">
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <span class="material-symbols-outlined" style="color:#dc2626; font-size:18px; margin-top:1px; flex-shrink:0;">error</span>
                            <ul id="editDriverErrorList" style="
                                margin:0; padding:0; list-style:none;
                                font-size:13px; color:#dc2626; line-height:1.6;"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('editDriverModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== ARCHIVED MODAL ===================== --}}
    <div class="modal" id="archivedModal">
        <div class="modal-content archived-modal">
            <div class="modal-header">
                <h2>Archived Drivers</h2>
                <div class="search-wrapper">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" class="search-input" id="archivedSearchInput" placeholder="Find Driver">
                </div>
            </div>
            <div class="modal-body" style="padding:0 0 0;">
                <div style="overflow-x:auto; padding:0 24px 24px;">
                    <table class="archived-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>License No.</th>
                                <th>Total Trips</th>
                                <th>Last Trip</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('archivedModal')">Close</button>
            </div>
        </div>
    </div>

    {{-- ===================== ARCHIVE WARNING MODALS ===================== --}}
    <div class="modal" id="warningModal1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">warning</span>
                <h2>Archive Driver</h2>
            </div>
            <div class="modal-body">
                <p><strong>Driver:</strong> <span id="warningDriverName"></span></p>
                <p>Are you sure you want to archive this driver?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('warningModal1')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedToPassword()">Yes, Archive</button>
            </div>
        </div>
    </div>

    <div class="modal" id="warningModal2">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">lock</span>
                <h2>Confirm with Password</h2>
            </div>
            <div class="modal-body">
                <p>Enter admin password to confirm.</p>
                <input type="password" class="password-input" id="adminPassword" placeholder="Admin password">
                <p id="archivePasswordError" style="
                    display:none; margin-top:8px; font-size:13px; color:#dc2626;
                    display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">error</span>
                    <span id="archivePasswordErrorText"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('warningModal2')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmArchiveAction()">Confirm</button>
            </div>
        </div>
    </div>

    {{-- ===================== UNARCHIVE WARNING MODALS ===================== --}}
    <div class="modal" id="warningModal3">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">warning</span>
                <h2>Unarchive Driver</h2>
            </div>
            <div class="modal-body">
                <p><strong>Driver:</strong> <span id="warningUnarchiveDriverName"></span></p>
                <p>Are you sure you want to unarchive this driver?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('warningModal3')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedToUnarchivePassword()">Yes, Unarchive</button>
            </div>
        </div>
    </div>

    <div class="modal" id="warningModal4">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">lock</span>
                <h2>Confirm with Password</h2>
            </div>
            <div class="modal-body">
                <p>Enter admin password to confirm.</p>
                <input type="password" class="password-input" id="adminPasswordUnarchive" placeholder="Admin password">
                <p id="unarchivePasswordError" style="
                    display:none; margin-top:8px; font-size:13px; color:#dc2626;
                    display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">error</span>
                    <span id="unarchivePasswordErrorText"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('warningModal4')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmUnarchiveAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/driver-management.js') }}"></script>
@endsection