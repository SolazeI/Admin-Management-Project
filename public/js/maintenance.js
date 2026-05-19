// ── CSRF Token ────────────────────────────────────────────

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

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
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearFormError(boxId) {
    const box = document.getElementById(boxId);
    if (box) box.style.display = 'none';
}

function showInlineError(form, errors) {
    const box  = form.querySelector('.editMaintError');
    const list = form.querySelector('.editMaintErrorList');
    if (!box || !list) return;

    list.innerHTML = '';

    const messages = Array.isArray(errors)
        ? errors
        : typeof errors === 'object'
            ? Object.values(errors).flat()
            : [errors];

    messages.forEach(msg => {
        const li = document.createElement('li');
        li.textContent = msg;
        list.appendChild(li);
    });

    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearInlineError(form) {
    const box = form.querySelector('.editMaintError');
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

// ── State ─────────────────────────────────────────────────

let _archiveMaintId = null;
let _deleteMaintId  = null;

function openEditMaint(id, truckId, startDate, issue, notes, cost) {
    document.getElementById('editMaintId').value        = id;
    document.getElementById('editMaintTruckId').value   = truckId;
    document.getElementById('editMaintStartDate').value = startDate;
    document.getElementById('editMaintIssue').value     = issue;
    document.getElementById('editMaintNotes').value     = notes;
    document.getElementById('editMaintCost').value      = cost;
    clearFormError('editMaintError');
    openModal('editMaintenanceModal');
}

// ── Init ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initializeEventListeners();
});

function initializeEventListeners() {

    document.getElementById('editMaintenanceForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearFormError('editMaintError');

        const id      = document.getElementById('editMaintId').value;
        const payload = {
            truck_id:          document.getElementById('editMaintTruckId').value,
            start_date:        document.getElementById('editMaintStartDate').value,
            issue_description: document.getElementById('editMaintIssue').value,
            notes:             document.getElementById('editMaintNotes').value,
            cost:              document.getElementById('editMaintCost').value,
        };

        const btn = this.querySelector('[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

        try {
            const response = await fetch(`/maintenance/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                closeModal('editMaintenanceModal');
                location.reload();
            } else {
                const data = await response.json().catch(() => null);
                showFormError('editMaintError', 'editMaintErrorList',
                    data?.errors ? Object.values(data.errors).flat() : [data?.message || 'Something went wrong.']
                );
            }
        } catch (err) {
            showFormError('editMaintError', 'editMaintErrorList', ['Unable to connect. Please try again.']);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Save Changes'; }
        }
    });

    // Add modal
    const addMaintenanceBtn = document.getElementById('addMaintenanceBtn');
    if (addMaintenanceBtn) {
        addMaintenanceBtn.addEventListener('click', () => {
            clearFormError('addMaintError');
            openModal('addMaintenanceModal');
        });
    }

    // Archived modal
    const archivedMaintenanceBtn = document.getElementById('archivedMaintenanceBtn');
    if (archivedMaintenanceBtn) {
        archivedMaintenanceBtn.addEventListener('click', () => openModal('archivedMaintenanceModal'));
    }

    // Add form submit
    const addMaintenanceForm = document.getElementById('addMaintenanceForm');
    if (addMaintenanceForm) {
        addMaintenanceForm.addEventListener('submit', handleAddMaintenance);
    }

    // ── Transition forms (Start / Complete / Cancel) ──────
    document.querySelectorAll('form[action*="/transition"]').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const payload  = Object.fromEntries(formData.entries());
            delete payload._token;

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': getCsrf(),
                    },
                    body: JSON.stringify(payload),
                });

                if (response.ok) {
                    location.reload();
                } else {
                    const data = await response.json().catch(() => null);
                    alert(data?.message || 'Could not update status. Please try again.');
                }
            } catch (error) {
                console.error('Error updating maintenance status:', error);
                alert('Unable to connect to the server. Please try again.');
            }
        });
    });

    // ── Unarchive forms (Restore button) ──────────────────
    document.querySelectorAll('form[action*="/unarchive"]').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': getCsrf(),
                    },
                });

                if (response.ok) {
                    location.reload();
                } else {
                    const data = await response.json().catch(() => null);
                    alert(data?.message || 'Could not restore record. Please try again.');
                }
            } catch (error) {
                console.error('Error restoring maintenance record:', error);
                alert('Unable to connect to the server. Please try again.');
            }
        });
    });

    // Search
    const maintSearch = document.getElementById('maintSearch');
    if (maintSearch) {
        maintSearch.addEventListener('input', applyMaintFilters);
    }

    // Archived search
    const archivedMaintSearch = document.getElementById('archivedMaintSearch');
    if (archivedMaintSearch) {
        archivedMaintSearch.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#archivedMaintTableBody tr').forEach(row => {
                row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
            });
        });
    }

    // Status filter checkboxes
    document.querySelectorAll('.maint-status-filter').forEach(cb => {
        cb.addEventListener('change', applyMaintFilters);
    });

    // Filter panel toggle
    const maintFilterBtn   = document.getElementById('maintFilterBtn');
    const maintFilterPanel = document.getElementById('maintFilterPanel');
    if (maintFilterBtn && maintFilterPanel) {
        maintFilterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            maintFilterPanel.style.display = maintFilterPanel.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', (e) => {
            if (!maintFilterPanel.contains(e.target) && e.target !== maintFilterBtn) {
                maintFilterPanel.style.display = 'none';
            }
        });
    }

    // Close modals on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            const noBackdropClose = ['editTripModal', 'addTripModal', 'editMaintenanceModal', 'addMaintenanceModal'];
            if (e.target === this && !noBackdropClose.includes(this.id)) {
                closeModal(this.id);
            }
        });
    });

    // Clear password errors on typing
    document.getElementById('maintArchivePassword')
        ?.addEventListener('input', () => clearPasswordError('maintArchivePasswordError'));

    document.getElementById('maintDeletePassword')
        ?.addEventListener('input', () => clearPasswordError('maintDeletePasswordError'));
}

// ── Modal Helpers ─────────────────────────────────────────

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

        if (modalId === 'addMaintenanceModal') {
            document.getElementById('addMaintenanceForm').reset();
            clearFormError('addMaintError');
        } else if (modalId === 'maintArchiveWarning2') {
            document.getElementById('maintArchivePassword').value = '';
            clearPasswordError('maintArchivePasswordError');
        } else if (modalId === 'maintDeleteWarning2') {
            document.getElementById('maintDeletePassword').value = '';
            clearPasswordError('maintDeletePasswordError');
        } else if (modalId === 'editMaintenanceModal') {
            document.getElementById('editMaintenanceForm').reset();
            clearFormError('editMaintError');
        }
    }
}

function closeMaintenanceModal() {
    closeModal('addMaintenanceModal');
}

// ── Add Maintenance ───────────────────────────────────────

async function handleAddMaintenance(e) {
    e.preventDefault();
    clearFormError('addMaintError');

    const formData = new FormData(e.target);
    const payload  = Object.fromEntries(formData.entries());
    delete payload._token;

    try {
        const response = await fetch(e.target.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            closeModal('addMaintenanceModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            if (data?.errors) {
                showFormError('addMaintError', 'addMaintErrorList', data.errors);
            } else {
                showFormError('addMaintError', 'addMaintErrorList', [
                    data?.message || 'Something went wrong. Please try again.',
                ]);
            }
        }
    } catch (error) {
        console.error('Error adding maintenance record:', error);
        showFormError('addMaintError', 'addMaintErrorList', [
            'Unable to connect to the server. Please check your connection and try again.',
        ]);
    }
}

// ── Edit Maintenance ──────────────────────────────────────

async function handleEditMaintenance(e) {
    e.preventDefault();

    const form     = e.target;
    const formData = new FormData(form);
    const payload  = Object.fromEntries(formData.entries());
    delete payload._token;

    clearInlineError(form);

    const saveBtn = form.querySelector('[type="submit"]');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            if (data?.errors) {
                showInlineError(form, data.errors);
            } else {
                showInlineError(form, [
                    data?.message || 'Something went wrong. Please try again.',
                ]);
            }
        }
    } catch (error) {
        console.error('Error updating maintenance record:', error);
        showInlineError(form, [
            'Unable to connect to the server. Please check your connection and try again.',
        ]);
    } finally {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Changes'; }
    }
}

// ── Filters ───────────────────────────────────────────────

function applyMaintFilters() {
    const query   = document.getElementById('maintSearch').value.trim().toLowerCase();
    const checked = Array.from(document.querySelectorAll('.maint-status-filter:checked'))
        .map(cb => cb.value);

    document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;

        const truck = cells[0]?.textContent.trim().toLowerCase() ?? '';
        const issue = cells[1]?.textContent.trim().toLowerCase() ?? '';
        const notes = cells[4]?.textContent.trim().toLowerCase() ?? '';

        const matchesSearch = !query
            || truck.includes(query)
            || issue.includes(query)
            || notes.includes(query);

        const badge  = row.querySelector('.status-badge');
        const status = Array.from(badge?.classList ?? [])
            .find(c => c.startsWith('status-') && c !== 'status-badge')
            ?.replace('status-', '') ?? '';

        row.style.display = (matchesSearch && checked.includes(status)) ? '' : 'none';
    });
}

function clearMaintFilters() {
    document.querySelectorAll('.maint-status-filter').forEach(cb => cb.checked = true);
    applyMaintFilters();
}

// ── Archive Flow ──────────────────────────────────────────

function confirmArchiveMaint(id, issue) {
    _archiveMaintId = id;
    document.getElementById('archiveMaintLabel').textContent = issue;
    document.getElementById('maintArchivePassword').value = '';
    clearPasswordError('maintArchivePasswordError');
    openModal('maintArchiveWarning1');
}

function proceedToMaintArchivePassword() {
    closeModal('maintArchiveWarning1');
    document.getElementById('maintArchivePassword').value = '';
    clearPasswordError('maintArchivePasswordError');
    openModal('maintArchiveWarning2');
}

async function confirmMaintArchiveAction() {
    const password = document.getElementById('maintArchivePassword').value;

    if (!password) {
        showPasswordError('maintArchivePasswordError', 'maintArchivePasswordErrorText', 'Please enter the admin password.');
        return;
    }

    try {
        const response = await fetch(`/maintenance/${_archiveMaintId}/archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeModal('maintArchiveWarning2');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showPasswordError(
                'maintArchivePasswordError',
                'maintArchivePasswordErrorText',
                data?.message || 'Incorrect password. Please try again.'
            );
        }
    } catch (error) {
        console.error('Error archiving maintenance record:', error);
        showPasswordError('maintArchivePasswordError', 'maintArchivePasswordErrorText', 'Unable to connect. Please try again.');
    }
}

// ── Delete Flow ───────────────────────────────────────────

function confirmDeleteMaint(id, issue) {
    _deleteMaintId = id;
    document.getElementById('deleteMaintLabel').textContent = issue;
    document.getElementById('maintDeletePassword').value = '';
    clearPasswordError('maintDeletePasswordError');
    openModal('maintDeleteWarning1');
}

function proceedToMaintDeletePassword() {
    closeModal('maintDeleteWarning1');
    document.getElementById('maintDeletePassword').value = '';
    clearPasswordError('maintDeletePasswordError');
    openModal('maintDeleteWarning2');
}

async function confirmMaintDeleteAction() {
    const password = document.getElementById('maintDeletePassword').value;

    if (!password) {
        showPasswordError('maintDeletePasswordError', 'maintDeletePasswordErrorText', 'Please enter the admin password.');
        return;
    }

    try {
        const response = await fetch(`/maintenance/${_deleteMaintId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeModal('maintDeleteWarning2');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showPasswordError(
                'maintDeletePasswordError',
                'maintDeletePasswordErrorText',
                data?.message || 'Incorrect password. Please try again.'
            );
        }
    } catch (error) {
        console.error('Error deleting maintenance record:', error);
        showPasswordError('maintDeletePasswordError', 'maintDeletePasswordErrorText', 'Unable to connect. Please try again.');
    }
}