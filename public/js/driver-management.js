// Global variables
let currentDriverId = null;
let currentDriverName = '';

// ── Error Helpers ─────────────────────────────────────────

function showFormError(boxId, listId, errors) {
    const box  = document.getElementById(boxId);
    const list = document.getElementById(listId);
    if (!box || !list) return;

    list.innerHTML = '';

    if (Array.isArray(errors)) {
        errors.forEach(msg => {
            const li = document.createElement('li');
            li.textContent = msg;
            list.appendChild(li);
        });
    } else if (typeof errors === 'object') {
        // Laravel validation errors: { field: ['msg1', 'msg2'] }
        Object.values(errors).flat().forEach(msg => {
            const li = document.createElement('li');
            li.textContent = msg;
            list.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = errors;
        list.appendChild(li);
    }

    box.style.display = 'block';

    // Scroll the error into view inside the modal body
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearFormError(boxId) {
    const box = document.getElementById(boxId);
    if (box) box.style.display = 'none';
}

function showPasswordError(errorId, textId, message) {
    const el   = document.getElementById(errorId);
    const text = document.getElementById(textId);
    if (el && text) {
        text.textContent = message;
        el.style.display = 'flex';
    }
}

function clearPasswordError(errorId) {
    const el = document.getElementById(errorId);
    if (el) el.style.display = 'none';
}

// ── Init ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initializeEventListeners();
});

function initializeEventListeners() {
    const addDriverBtn = document.getElementById('addDriverBtn');
    if (addDriverBtn) {
        addDriverBtn.addEventListener('click', () => openModal('addDriverModal'));
    }

    const archivedBtn = document.getElementById('archivedBtn');
    if (archivedBtn) {
        archivedBtn.addEventListener('click', loadArchivedDrivers);
    }

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    const archivedSearchInput = document.getElementById('archivedSearchInput');
    if (archivedSearchInput) {
        archivedSearchInput.addEventListener('input', debounce(handleArchivedSearch, 300));
    }

    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput      = document.getElementById('fileInput');
    if (fileUploadArea && fileInput) {
        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileSelect);
    }

    const editFileUploadArea = document.getElementById('editFileUploadArea');
    const editFileInput      = document.getElementById('editFileInput');
    if (editFileUploadArea && editFileInput) {
        editFileUploadArea.addEventListener('click', () => editFileInput.click());
        editFileInput.addEventListener('change', handleEditFileSelect);
    }

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

    const addDriverForm = document.getElementById('addDriverForm');
    if (addDriverForm) {
        addDriverForm.addEventListener('submit', handleAddDriver);
    }

    const editDriverForm = document.getElementById('editDriverForm');
    if (editDriverForm) {
        editDriverForm.addEventListener('submit', handleEditDriver);
    }

    // Clear errors when modal is closed by clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Clear password errors when typing
    const adminPassword = document.getElementById('adminPassword');
    if (adminPassword) {
        adminPassword.addEventListener('input', () => clearPasswordError('archivePasswordError'));
    }

    const adminPasswordUnarchive = document.getElementById('adminPasswordUnarchive');
    if (adminPasswordUnarchive) {
        adminPasswordUnarchive.addEventListener('input', () => clearPasswordError('unarchivePasswordError'));
    }
}

// ── Modal Functions ───────────────────────────────────────

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

        if (modalId === 'addDriverModal') {
            document.getElementById('addDriverForm').reset();
            clearFormError('addDriverError');
        } else if (modalId === 'editDriverModal') {
            document.getElementById('editDriverForm').reset();
            clearFormError('editDriverError');
        } else if (modalId === 'warningModal2') {
            clearPasswordError('archivePasswordError');
        } else if (modalId === 'warningModal4') {
            clearPasswordError('unarchivePasswordError');
        }
    }
}

// ── Actions Menu ──────────────────────────────────────────

function openActionsMenu(driverId) {
    document.querySelectorAll('.actions-menu').forEach(menu => menu.classList.remove('show'));

    const menu = document.getElementById(`menu-${driverId}`);
    if (menu) menu.classList.toggle('show');

    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && !e.target.closest('.action-btn')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

// ── View Driver ───────────────────────────────────────────

async function viewDriver(driverId) {
    try {
        const response = await fetch(`/drivers/${driverId}`);
        const driver   = await response.json();

        document.getElementById('viewFullName').value        = driver.full_name;
        document.getElementById('viewPhoneNumber').value     = driver.phone_number;
        document.getElementById('viewLicenseNumber').value   = driver.license_number;
        document.getElementById('viewLicenseExpiry').value   = formatDateForInput(driver.license_expiry_date);
        document.getElementById('viewAddress').value         = driver.address;
        document.getElementById('viewEmergencyContact').value = driver.emergency_contact;
        document.getElementById('viewFilePath').value        = driver.file_url || '';

        const viewFileBtn = document.getElementById('viewFileBtnModal');
        viewFileBtn.style.display = driver.file_url ? 'inline-flex' : 'none';

        const archivedModal = document.getElementById('archivedModal');
        if (archivedModal && archivedModal.classList.contains('show')) {
            closeModal('archivedModal');
        }

        openModal('viewDriverModal');
    } catch (error) {
        console.error('Error loading driver:', error);
    }
}

function viewDriverFileFromModal() {
    const fileUrl = document.getElementById('viewFilePath').value;
    if (fileUrl) {
        window.open(fileUrl, '_blank');
    }
}

// ── Edit Driver ───────────────────────────────────────────

async function editDriver(driverId) {
    try {
        const response = await fetch(`/drivers/${driverId}`);
        const driver   = await response.json();

        document.getElementById('editDriverId').value          = driver.id;
        document.getElementById('editFullName').value          = driver.full_name;
        document.getElementById('editPhoneNumber').value       = driver.phone_number;
        document.getElementById('editLicenseNumber').value     = driver.license_number;
        document.getElementById('editLicenseExpiry').value     = formatDateForInput(driver.license_expiry_date);
        document.getElementById('editAddress').value           = driver.address;
        document.getElementById('editEmergencyContact').value  = driver.emergency_contact;
        document.getElementById('currentFilePath').value       = driver.file_url || '';

        window.currentDriver = driver;
        clearFormError('editDriverError');
        openModal('editDriverModal');
    } catch (error) {
        console.error('Error loading driver:', error);
    }
}

// ── Add Driver ────────────────────────────────────────────

async function handleAddDriver(e) {
    e.preventDefault();
    clearFormError('addDriverError');

    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files || fileInput.files.length === 0) {
        showFormError('addDriverError', 'addDriverErrorList', ['Please select a file to upload.']);
        return;
    }

    const formData    = new FormData(e.target);
    const expiryDate  = formData.get('license_expiry_date');
    if (expiryDate && expiryDate.includes('/')) {
        const [m, d, y] = expiryDate.split('/');
        formData.set('license_expiry_date', `${y}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`);
    }

    try {
        const response = await fetch('/drivers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (response.ok) {
            closeModal('addDriverModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            if (data?.errors) {
                showFormError('addDriverError', 'addDriverErrorList', data.errors);
            } else {
                showFormError('addDriverError', 'addDriverErrorList', [
                    data?.message || 'Something went wrong. Please try again.',
                ]);
            }
        }
    } catch (error) {
        console.error('Error adding driver:', error);
        showFormError('addDriverError', 'addDriverErrorList', [
            'Unable to connect to the server. Please check your connection and try again.',
        ]);
    }
}

// ── Edit Driver Submit ────────────────────────────────────

async function handleEditDriver(e) {
    e.preventDefault();
    clearFormError('editDriverError');

    const driverId = document.getElementById('editDriverId').value;
    const formData = new FormData(e.target);

    const expiryDate = formData.get('license_expiry_date');
    if (expiryDate && expiryDate.includes('/')) {
        const [m, d, y] = expiryDate.split('/');
        formData.set('license_expiry_date', `${y}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`);
    }

    try {
        const response = await fetch(`/drivers/${driverId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (response.ok) {
            closeModal('editDriverModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            if (data?.errors) {
                showFormError('editDriverError', 'editDriverErrorList', data.errors);
            } else {
                showFormError('editDriverError', 'editDriverErrorList', [
                    data?.message || 'Something went wrong. Please try again.',
                ]);
            }
        }
    } catch (error) {
        console.error('Error updating driver:', error);
        showFormError('editDriverError', 'editDriverErrorList', [
            'Unable to connect to the server. Please check your connection and try again.',
        ]);
    }
}

// ── Archive ───────────────────────────────────────────────

function confirmArchive(driverId, driverName) {
    currentDriverId   = driverId;
    currentDriverName = driverName;
    document.getElementById('warningDriverName').textContent = driverName;
    openModal('warningModal1');
}

function proceedToPassword() {
    closeModal('warningModal1');
    document.getElementById('adminPassword').value = '';
    clearPasswordError('archivePasswordError');
    openModal('warningModal2');
}

async function confirmArchiveAction() {
    const password = document.getElementById('adminPassword').value;

    if (!password) {
        showPasswordError('archivePasswordError', 'archivePasswordErrorText', 'Please enter the admin password.');
        return;
    }

    try {
        const response = await fetch(`/drivers/${currentDriverId}/archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeModal('warningModal2');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showPasswordError(
                'archivePasswordError',
                'archivePasswordErrorText',
                data?.message || 'Incorrect password. Please try again.'
            );
        }
    } catch (error) {
        console.error('Error archiving driver:', error);
        showPasswordError('archivePasswordError', 'archivePasswordErrorText', 'Unable to connect. Please try again.');
    }
}

// ── Unarchive ─────────────────────────────────────────────

function confirmUnarchive(driverId, driverName) {
    try {
        const menu = document.getElementById(`archived-menu-${driverId}`);
        if (menu) menu.classList.remove('show');

        currentDriverId   = driverId;
        currentDriverName = driverName;

        const nameEl = document.getElementById('warningUnarchiveDriverName');
        if (nameEl) nameEl.textContent = driverName;

        openModal('warningModal3');
    } catch (error) {
        console.error('Error in confirmUnarchive:', error);
    }
}

function proceedToUnarchivePassword() {
    closeModal('warningModal3');
    document.getElementById('adminPasswordUnarchive').value = '';
    clearPasswordError('unarchivePasswordError');
    openModal('warningModal4');
}

async function confirmUnarchiveAction() {
    const password = document.getElementById('adminPasswordUnarchive').value;

    if (!password) {
        showPasswordError('unarchivePasswordError', 'unarchivePasswordErrorText', 'Please enter the admin password.');
        return;
    }

    try {
        const response = await fetch(`/drivers/${currentDriverId}/unarchive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeModal('warningModal4');
            closeModal('archivedModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showPasswordError(
                'unarchivePasswordError',
                'unarchivePasswordErrorText',
                data?.message || 'Incorrect password. Please try again.'
            );
        }
    } catch (error) {
        console.error('Error unarchiving driver:', error);
        showPasswordError('unarchivePasswordError', 'unarchivePasswordErrorText', 'Unable to connect. Please try again.');
    }
}

// ── Archived Modal ────────────────────────────────────────

async function loadArchivedDrivers() {
    try {
        const response = await fetch('/drivers/archived');
        const drivers  = await response.json();
        const tbody    = document.getElementById('archivedTableBody');
        tbody.innerHTML = '';

        if (drivers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No archived drivers found</td></tr>';
        } else {
            drivers.forEach(driver => {
                const row     = document.createElement('tr');
                const lastTrip = driver.last_trip
                    ? new Date(driver.last_trip).toISOString().split('T')[0]
                    : '';

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
                            <button disabled style="opacity:0.5;cursor:not-allowed;">Select Action</button>
                            <button class="action-view-btn" data-driver-id="${driver.id}">View</button>
                            <button class="action-unarchive-btn"
                                data-driver-id="${driver.id}"
                                data-driver-name="${driver.full_name.replace(/"/g,'&quot;').replace(/'/g,'&#39;')}">
                                Unarchive
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            tbody.querySelectorAll('.action-view-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    viewDriver(parseInt(this.dataset.driverId));
                });
            });

            tbody.querySelectorAll('.action-unarchive-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const driverId = parseInt(this.dataset.driverId);
                    const textarea = document.createElement('textarea');
                    textarea.innerHTML = this.dataset.driverName;
                    confirmUnarchive(driverId, textarea.value || 'this driver');
                });
            });
        }

        openModal('archivedModal');
    } catch (error) {
        console.error('Error loading archived drivers:', error);
    }
}

function openArchivedActionsMenu(driverId) {
    document.querySelectorAll('.actions-menu').forEach(menu => {
        if (menu.id.startsWith('archived-menu-')) menu.classList.remove('show');
    });

    const menu = document.getElementById(`archived-menu-${driverId}`);
    if (menu) menu.classList.toggle('show');

    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && !e.target.closest('.action-btn')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

// ── Search ────────────────────────────────────────────────

async function handleSearch(e) {
    const query = e.target.value.trim();
    if (query.length === 0) { location.reload(); return; }

    try {
        const response = await fetch(`/drivers/search?q=${encodeURIComponent(query)}`);
        updateDriversTable(await response.json());
    } catch (error) {
        console.error('Error searching drivers:', error);
    }
}

function handleArchivedSearch(e) {
    const query = e.target.value.toLowerCase();
    document.querySelectorAll('#archivedTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}

// ── Table Update ──────────────────────────────────────────

function updateDriversTable(drivers) {
    const tbody = document.getElementById('driversTableBody');
    tbody.innerHTML = '';

    if (drivers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">No drivers found</td></tr>';
        return;
    }

    drivers.forEach(driver => {
        const row         = document.createElement('tr');
        const statusClass = driver.status.toLowerCase().replace(/[-\s]/g, '');

        row.innerHTML = `
            <td>
                <span class="material-symbols-outlined driver-icon">person</span>
                ${driver.full_name}
            </td>
            <td>${driver.phone_number}</td>
            <td>${driver.license_number}</td>
            <td>${driver.assigned_truck || 'Unassigned'}</td>
            <td>
                <span class="status-badge status-${statusClass}">${driver.status}</span>
            </td>
            <td>
                <button class="action-btn" onclick="openActionsMenu(${driver.id})">
                    <span class="material-symbols-outlined">more_vert</span>
                </button>
                <div class="actions-menu" id="menu-${driver.id}">
                    <button disabled>Select Action</button>
                    <button onclick="viewDriver(${driver.id})">View</button>
                    <button onclick="editDriver(${driver.id})">Edit</button>
                    <button onclick="confirmArchive(${driver.id}, ${JSON.stringify(driver.full_name)})">Archive</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// ── File Upload ───────────────────────────────────────────

function handleFileSelect(e) {
    updateFileUploadUI('fileUploadArea', e.target.files[0]);
}

function handleEditFileSelect(e) {
    updateFileUploadUI('editFileUploadArea', e.target.files[0]);
}

function updateFileUploadUI(areaId, file) {
    if (!file) return;
    const area = document.getElementById(areaId);
    const icon = area.querySelector('.material-symbols-outlined');
    const text = area.querySelector('p');
    if (icon) { icon.textContent = 'check_circle'; icon.style.color = '#10b981'; }
    if (text) text.textContent = file.name;
    area.style.borderColor = '#10b981';
}

function viewDriverFile() {
    const fileUrl = document.getElementById('currentFilePath').value;
    if (fileUrl) window.open(fileUrl, '_blank');
}

// ── Date Helpers ──────────────────────────────────────────

function formatDateForInput(dateString) {
    if (!dateString) return '';
    const date  = new Date(dateString);
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day   = String(date.getDate()).padStart(2, '0');
    return `${month}/${day}/${date.getFullYear()}`;
}

function formatDateInput(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2);
    if (value.length >= 5) value = value.substring(0, 5) + '/' + value.substring(5, 9);
    e.target.value = value;
}

function restrictDateInput(e) {
    if (!/[0-9]/.test(String.fromCharCode(e.which))) e.preventDefault();
}

// ── Utilities ─────────────────────────────────────────────

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name    = 'csrf-token';
    meta.content = document.querySelector('input[name="_token"]')?.value || '';
    document.head.appendChild(meta);
}

// ── Filter Panel ──────────────────────────────────────────

const filterBtn   = document.getElementById('filterBtn');
const filterPanel = document.getElementById('filterPanel');

filterBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    filterPanel.style.display = filterPanel.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
    if (!filterPanel.contains(e.target) && e.target !== filterBtn) {
        filterPanel.style.display = 'none';
    }
});

document.querySelectorAll('.status-filter').forEach(cb => {
    cb.addEventListener('change', applyDriverFilters);
});

function clearFilters() {
    document.querySelectorAll('.status-filter').forEach(cb => cb.checked = true);
    applyDriverFilters();
}

function applyDriverFilters() {
    const checked = Array.from(document.querySelectorAll('.status-filter:checked')).map(cb => cb.value);

    document.querySelectorAll('#driversTableBody tr').forEach(row => {
        const badge = row.querySelector('.status-badge');
        if (!badge) return;
        const status = Array.from(badge.classList)
            .find(c => c.startsWith('status-') && c !== 'status-badge')
            ?.replace('status-', '') ?? '';
        row.style.display = checked.includes(status) ? '' : 'none';
    });
}