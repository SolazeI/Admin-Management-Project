// Global variables
let currentDriverId = null;
let currentDriverName = '';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

function initializeEventListeners() {
    // Add Driver Button
    const addDriverBtn = document.getElementById('addDriverBtn');
    if (addDriverBtn) {
        addDriverBtn.addEventListener('click', () => openModal('addDriverModal'));
    }

    // Archived Button
    const archivedBtn = document.getElementById('archivedBtn');
    if (archivedBtn) {
        archivedBtn.addEventListener('click', loadArchivedDrivers);
    }

    // Search Input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // Archived Search Input
    const archivedSearchInput = document.getElementById('archivedSearchInput');
    if (archivedSearchInput) {
        archivedSearchInput.addEventListener('input', debounce(handleArchivedSearch, 300));
    }

    // File Upload Areas
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('fileInput');
    if (fileUploadArea && fileInput) {
        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileSelect);
    }

    const editFileUploadArea = document.getElementById('editFileUploadArea');
    const editFileInput = document.getElementById('editFileInput');
    if (editFileUploadArea && editFileInput) {
        editFileUploadArea.addEventListener('click', () => editFileInput.click());
        editFileInput.addEventListener('change', handleEditFileSelect);
    }

    // Date input formatting (mm/dd/yyyy)
    const addDateInput = document.getElementById('addLicenseExpiry');
    if (addDateInput) {
        addDateInput.addEventListener('input', formatDateInput);
        addDateInput.addEventListener('keypress', restrictDateInput);
    }

    const editDateInput = document.getElementById('editLicenseExpiry');
    if (editDateInput) {
        editDateInput.addEventListener('input', formatDateInput);
        editDateInput.addEventListener('keypress', restrictDateInput);
    }

    // Form Submissions
    const addDriverForm = document.getElementById('addDriverForm');
    if (addDriverForm) {
        addDriverForm.addEventListener('submit', handleAddDriver);
    }

    const editDriverForm = document.getElementById('editDriverForm');
    if (editDriverForm) {
        editDriverForm.addEventListener('submit', handleEditDriver);
    }

    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        
        // Reset forms
        if (modalId === 'addDriverModal') {
            document.getElementById('addDriverForm').reset();
        } else if (modalId === 'editDriverModal') {
            document.getElementById('editDriverForm').reset();
        }
    }
}

// Actions Menu
function openActionsMenu(driverId) {
    // Close all other menus
    document.querySelectorAll('.actions-menu').forEach(menu => {
        menu.classList.remove('show');
    });
    
    const menu = document.getElementById(`menu-${driverId}`);
    if (menu) {
        menu.classList.toggle('show');
    }
    
    // Close menu when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && !e.target.closest('.action-btn')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

// View Driver
async function viewDriver(driverId) {
    try {
        const response = await fetch(`/drivers/${driverId}`);
        const driver = await response.json();
        
        // Format date as mm/dd/yyyy
        const expiryDate = formatDateForInput(driver.license_expiry_date);
        
        document.getElementById('viewFullName').value = driver.full_name;
        document.getElementById('viewPhoneNumber').value = driver.phone_number;
        document.getElementById('viewLicenseNumber').value = driver.license_number;
        document.getElementById('viewLicenseExpiry').value = expiryDate;
        document.getElementById('viewAddress').value = driver.address;
        document.getElementById('viewEmergencyContact').value = driver.emergency_contact;
        document.getElementById('viewFilePath').value = driver.file_path || '';
        
        // Show/hide View File button based on file existence
        const viewFileBtn = document.getElementById('viewFileBtnModal');
        if (driver.file_path) {
            viewFileBtn.style.display = 'inline-flex';
        } else {
            viewFileBtn.style.display = 'none';
        }
        
        // Close archived modal if it's open
        const archivedModal = document.getElementById('archivedModal');
        if (archivedModal && archivedModal.classList.contains('show')) {
            closeModal('archivedModal');
        }
        
        openModal('viewDriverModal');
    } catch (error) {
        console.error('Error loading driver:', error);
        alert('Error loading driver information');
    }
}

// View File from View Modal
function viewDriverFileFromModal() {
    const filePath = document.getElementById('viewFilePath').value;
    if (filePath) {
        window.open(`/storage/${filePath}`, '_blank');
    } else {
        alert('No file available');
    }
}

// Edit Driver
async function editDriver(driverId) {
    try {
        const response = await fetch(`/drivers/${driverId}`);
        const driver = await response.json();
        
        // Format date as mm/dd/yyyy
        const expiryDate = formatDateForInput(driver.license_expiry_date);
        
        document.getElementById('editDriverId').value = driver.id;
        document.getElementById('editFullName').value = driver.full_name;
        document.getElementById('editPhoneNumber').value = driver.phone_number;
        document.getElementById('editLicenseNumber').value = driver.license_number;
        document.getElementById('editLicenseExpiry').value = expiryDate;
        document.getElementById('editAddress').value = driver.address;
        document.getElementById('editEmergencyContact').value = driver.emergency_contact;
        document.getElementById('currentFilePath').value = driver.file_path || '';
        
        // Store current driver for view file
        window.currentDriver = driver;
        
        openModal('editDriverModal');
    } catch (error) {
        console.error('Error loading driver:', error);
        alert('Error loading driver information');
    }
}

// Confirm Archive (Step 1)
function confirmArchive(driverId, driverName) {
    currentDriverId = driverId;
    currentDriverName = driverName;
    document.getElementById('warningDriverName').textContent = driverName;
    openModal('warningModal1');
}

// Proceed to Password (Step 2)
function proceedToPassword() {
    closeModal('warningModal1');
    openModal('warningModal2');
    document.getElementById('adminPassword').value = '';
}

// Confirm Archive Action
async function confirmArchiveAction() {
    const password = document.getElementById('adminPassword').value;
    
    if (!password) {
        alert('Please enter admin password');
        return;
    }
    
    try {
        const response = await fetch(`/drivers/${currentDriverId}/archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ password })
        });
        
        if (response.ok) {
            alert('Driver archived successfully');
            closeModal('warningModal2');
            location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to archive driver');
        }
    } catch (error) {
        console.error('Error archiving driver:', error);
        alert('Error archiving driver');
    }
}

// Add Driver
async function handleAddDriver(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Convert date from mm/dd/yyyy to yyyy-mm-dd format
    const expiryDate = formData.get('license_expiry_date');
    if (expiryDate && expiryDate.includes('/')) {
        const dateParts = expiryDate.split('/');
        if (dateParts.length === 3) {
            const formattedDate = `${dateParts[2]}-${dateParts[0].padStart(2, '0')}-${dateParts[1].padStart(2, '0')}`;
            formData.set('license_expiry_date', formattedDate);
        }
    }
    
    try {
        const response = await fetch('/drivers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        });
        
        if (response.ok) {
            alert('Driver added successfully');
            closeModal('addDriverModal');
            location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to add driver');
        }
    } catch (error) {
        console.error('Error adding driver:', error);
        alert('Error adding driver');
    }
}

// Edit Driver
async function handleEditDriver(e) {
    e.preventDefault();
    
    const driverId = document.getElementById('editDriverId').value;
    const formData = new FormData(e.target);
    
    // Convert date from mm/dd/yyyy to yyyy-mm-dd format
    const expiryDate = formData.get('license_expiry_date');
    if (expiryDate && expiryDate.includes('/')) {
        const dateParts = expiryDate.split('/');
        if (dateParts.length === 3) {
            const formattedDate = `${dateParts[2]}-${dateParts[0].padStart(2, '0')}-${dateParts[1].padStart(2, '0')}`;
            formData.set('license_expiry_date', formattedDate);
        }
    }
    
    formData.append('_method', 'PUT');
    
    try {
        const response = await fetch(`/drivers/${driverId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        });
        
        if (response.ok) {
            alert('Driver updated successfully');
            closeModal('editDriverModal');
            location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to update driver');
        }
    } catch (error) {
        console.error('Error updating driver:', error);
        alert('Error updating driver');
    }
}

// Load Archived Drivers
async function loadArchivedDrivers() {
    try {
        const response = await fetch('/drivers/archived');
        const drivers = await response.json();
        
        const tbody = document.getElementById('archivedTableBody');
        tbody.innerHTML = '';
        
        if (drivers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No archived drivers found</td></tr>';
        } else {
            drivers.forEach(driver => {
                const row = document.createElement('tr');
                const lastTrip = driver.last_trip ? new Date(driver.last_trip).toISOString().split('T')[0] : '';
                
                row.innerHTML = `
                    <td>
                        <span class="material-symbols-outlined driver-icon">person</span>
                        ${driver.full_name}
                    </td>
                    <td>${driver.phone_number}</td>
                    <td>${driver.license_number}</td>
                    <td>${driver.total_trips || 0}</td>
                    <td>${lastTrip}</td>
                    <td>
                        <button class="action-btn" onclick="openArchivedActionsMenu(${driver.id})">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                        <div class="actions-menu" id="archived-menu-${driver.id}">
                            <button disabled style="opacity: 0.5; cursor: not-allowed;">Select Action</button>
                            <button onclick="viewDriver(${driver.id})">View</button>
                            <button onclick="unarchiveDriver(${driver.id})">Unarchived</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        openModal('archivedModal');
    } catch (error) {
        console.error('Error loading archived drivers:', error);
        alert('Error loading archived drivers');
    }
}

// Open Archived Actions Menu
function openArchivedActionsMenu(driverId) {
    // Close all other menus
    document.querySelectorAll('.actions-menu').forEach(menu => {
        if (menu.id.startsWith('archived-menu-')) {
            menu.classList.remove('show');
        }
    });
    
    const menu = document.getElementById(`archived-menu-${driverId}`);
    if (menu) {
        menu.classList.toggle('show');
    }
    
    // Close menu when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && !e.target.closest('.action-btn')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

// Unarchive Driver
async function unarchiveDriver(driverId) {
    if (!confirm('Are you sure you want to unarchive this driver?')) {
        return;
    }
    
    try {
        const response = await fetch(`/drivers/${driverId}/unarchive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        if (response.ok) {
            alert('Driver unarchived successfully');
            closeModal('archivedModal');
            location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to unarchive driver');
        }
    } catch (error) {
        console.error('Error unarchiving driver:', error);
        alert('Error unarchiving driver');
    }
}

// Search
async function handleSearch(e) {
    const query = e.target.value.trim();
    
    if (query.length === 0) {
        location.reload();
        return;
    }
    
    try {
        const response = await fetch(`/drivers/search?q=${encodeURIComponent(query)}`);
        const drivers = await response.json();
        
        updateDriversTable(drivers);
    } catch (error) {
        console.error('Error searching drivers:', error);
    }
}

// Archived Search
function handleArchivedSearch(e) {
    const query = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#archivedTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// Update Drivers Table
function updateDriversTable(drivers) {
    const tbody = document.getElementById('driversTableBody');
    tbody.innerHTML = '';
    
    if (drivers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">No drivers found</td></tr>';
    } else {
        drivers.forEach(driver => {
            const row = document.createElement('tr');
            const statusClass = driver.status.toLowerCase().replace('-', '');
            
            row.innerHTML = `
                <td>
                    <span class="material-symbols-outlined driver-icon">person</span>
                    ${driver.full_name}
                </td>
                <td>${driver.phone_number}</td>
                <td>${driver.license_number}</td>
                <td>${driver.assigned_truck || 'Unassigned'}</td>
                <td>
                    <span class="status-badge status-${statusClass}">
                        ${driver.status}
                    </span>
                </td>
                <td>
                    <button class="action-btn" onclick="openActionsMenu(${driver.id})">
                        <span class="material-symbols-outlined">more_vert</span>
                    </button>
                    <div class="actions-menu" id="menu-${driver.id}">
                        <button onclick="viewDriver(${driver.id})">View</button>
                        <button onclick="editDriver(${driver.id})">Edit</button>
                        <button onclick="confirmArchive(${driver.id}, ${JSON.stringify(driver.full_name)})">Archive</button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
}

// File Upload Handlers
function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        const uploadArea = document.getElementById('fileUploadArea');
        uploadArea.innerHTML = `
            <span class="material-symbols-outlined">check_circle</span>
            <p>${file.name}</p>
        `;
        uploadArea.style.borderColor = '#10b981';
    }
}

function handleEditFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        const uploadArea = document.getElementById('editFileUploadArea');
        uploadArea.innerHTML = `
            <span class="material-symbols-outlined">check_circle</span>
            <p>${file.name}</p>
        `;
        uploadArea.style.borderColor = '#10b981';
    }
}

// View File
function viewDriverFile() {
    const filePath = document.getElementById('currentFilePath').value;
    if (filePath) {
        window.open(`/storage/${filePath}`, '_blank');
    } else {
        alert('No file available');
    }
}

// Format date for display (mm/dd/yyyy)
function formatDateForInput(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
}

// Date Input Formatting
function formatDateInput(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }
    e.target.value = value;
}

function restrictDateInput(e) {
    const char = String.fromCharCode(e.which);
    if (!/[0-9]/.test(char)) {
        e.preventDefault();
    }
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add CSRF token to meta tag if not present
if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = document.querySelector('input[name="_token"]')?.value || '';
    document.head.appendChild(meta);
}

