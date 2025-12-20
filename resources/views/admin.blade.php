<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - Driver Management</title>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body class="admin-page">
    <aside class="sidebar">
        <header class="sidebar-header">
            <a href="#" class="header-logo">
                <img src="{{ asset('images/AdminLogo.png') }}" alt="Company Logo" class="logo">
            </a>
        </header>

        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined">home</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <span class="material-symbols-outlined">group</span>
                        <span class="nav-label">Driver Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined">local_shipping</span>
                        <span class="nav-label">Fleet Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined">inventory_2</span>
                        <span class="nav-label">Trip Tickets</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined">build</span>
                        <span class="nav-label">Maintenance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-outlined">bar_chart</span>
                        <span class="nav-label">Reports</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="content-header">
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
                <button class="btn btn-filter">
                    <span class="material-symbols-outlined">filter_alt</span>
                    Filter
                    <span class="material-symbols-outlined">arrow_drop_down</span>
                </button>
            </div>
        </div>

        <div class="drivers-section">
            <h2 class="section-title">All Drivers</h2>
            <div class="table-container">
                <table class="drivers-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>PHONE</th>
                            <th>LICENSE NO.</th>
                            <th>ASSIGNED TRUCK</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
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
                                    <span class="status-badge status-{{ strtolower(str_replace('-', '', $driver->status)) }}">
                                        {{ $driver->status }}
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn" onclick="openActionsMenu({{ $driver->id }})">
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>
                                    <div class="actions-menu" id="menu-{{ $driver->id }}">
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
    </main>

    <!-- Add Driver Modal -->
    <div class="modal" id="addDriverModal">
        <div class="modal-content">
            <div class="modal-header">
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
                                <input type="text" name="license_expiry_date" id="addLicenseExpiry" placeholder="mm/dd/yyyy" required>
                                <span class="material-symbols-outlined date-icon">calendar_today</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address <span class="required">*</span></label>
                        <input type="text" name="address" required>
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact <span class="required">*</span></label>
                        <input type="text" name="emergency_contact" required>
                    </div>
                    <div class="form-group">
                        <label>File <span class="required">*</span></label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" name="file" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;">
                            <span class="material-symbols-outlined">download</span>
                            <p>Click to upload file</p>
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

    <!-- View Driver Modal -->
    <div class="modal" id="viewDriverModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Driver Information</h2>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" id="viewFullName" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="text" id="viewPhoneNumber" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>License Number <span class="required">*</span></label>
                        <input type="text" id="viewLicenseNumber" readonly>
                    </div>
                    <div class="form-group">
                        <label>License Expiry Date <span class="required">*</span></label>
                        <div class="date-input-wrapper">
                            <input type="text" id="viewLicenseExpiry" readonly>
                            <span class="material-symbols-outlined date-icon">calendar_today</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address <span class="required">*</span></label>
                    <input type="text" id="viewAddress" readonly>
                </div>
                <div class="form-group">
                    <label>Emergency Contact <span class="required">*</span></label>
                    <input type="text" id="viewEmergencyContact" readonly>
                </div>
                <div class="form-group">
                    <label>File <span class="required">*</span></label>
                    <button type="button" class="btn btn-view-file" id="viewFileBtnModal" onclick="viewDriverFileFromModal()">
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

    <!-- Edit Driver Modal -->
    <div class="modal" id="editDriverModal">
        <div class="modal-content">
            <div class="modal-header">
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
                                <input type="text" name="license_expiry_date" id="editLicenseExpiry" placeholder="mm/dd/yyyy" required>
                                <span class="material-symbols-outlined date-icon">calendar_today</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address <span class="required">*</span></label>
                        <input type="text" name="address" id="editAddress" required>
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact <span class="required">*</span></label>
                        <input type="text" name="emergency_contact" id="editEmergencyContact" required>
                    </div>
                    <div class="form-group">
                        <label>File <span class="required">*</span></label>
                        <button type="button" class="btn btn-view-file" id="viewFileBtn" onclick="viewDriverFile()">
                            <span class="material-symbols-outlined">visibility</span>
                            View File
                        </button>
                        <div class="file-upload-area" id="editFileUploadArea">
                            <input type="file" name="file" id="editFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;">
                            <span class="material-symbols-outlined">cloud_download</span>
                            <p>Click to upload file</p>
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

    <!-- Archived List Modal -->
    <div class="modal" id="archivedModal">
        <div class="modal-content archived-modal">
            <div class="modal-header">
                <h2>Archived list</h2>
                <div class="search-wrapper">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" class="search-input" id="archivedSearchInput" placeholder="Find Driver">
                </div>
            </div>
            <div class="modal-body">
                <table class="archived-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>License Number</th>
                            <th>Total Trips</th>
                            <th>Last Trip</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="archivedTableBody">
                        <!-- Archived drivers will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('archivedModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Warning Modal (Step 1) -->
    <div class="modal" id="warningModal1">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">warning</span>
                <h2>Warning</h2>
            </div>
            <div class="modal-body">
                <p><strong>Driver:</strong> <span id="warningDriverName"></span></p>
                <p>Are you sure you want to archive this driver?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="proceedToPassword()">Yes</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal('warningModal1')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Warning Modal (Step 2 - Password) -->
    <div class="modal" id="warningModal2">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <span class="material-symbols-outlined warning-icon">warning</span>
                <h2>Warning</h2>
            </div>
            <div class="modal-body">
                <p>Please input the admin password to confirm this action.</p>
                <input type="password" class="password-input" id="adminPassword" placeholder="Enter admin password">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="confirmArchiveAction()">Confirm</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal('warningModal2')">Cancel</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/driver-management.js') }}"></script>
</body>

</html>
