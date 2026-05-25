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

/** JSON payloads must omit empty optionals — "" fails Laravel nullable|numeric|date. */
function buildMaintenancePayload(form) {
    const formData = new FormData(form);
    const payload  = Object.fromEntries(formData.entries());
    delete payload._token;

    if (!payload.start_date) delete payload.start_date;
    if (payload.notes === '' || payload.notes == null) delete payload.notes;

    const costRaw = (payload.cost ?? '').toString().trim();
    if (!costRaw) {
        delete payload.cost;
    } else {
        const cleaned = costRaw.replace(/[^\d.]/g, '');
        if (!cleaned) delete payload.cost;
        else payload.cost = cleaned;
    }

    return payload;
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

    // ── Add Maintenance form ──────────────────────────────
    const addMaintenanceForm = document.getElementById('addMaintenanceForm');
    if (addMaintenanceForm) {
        addMaintenanceForm.addEventListener('submit', handleAddMaintenance);
    }

    // ── Edit Maintenance form ─────────────────────────────
    const editMaintenanceForm = document.getElementById('editMaintenanceForm');
    editMaintenanceForm?.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearFormError('editMaintError');

        const id      = document.getElementById('editMaintId').value;
        const payload = buildMaintenancePayload(this);

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
                if (data?.errors) {
                    showFormError('editMaintError', 'editMaintErrorList', data.errors);
                } else {
                    showFormError('editMaintError', 'editMaintErrorList', [
                        data?.message || 'Something went wrong.',
                    ]);
                }
            }
        } catch (err) {
            showFormError('editMaintError', 'editMaintErrorList', ['Unable to connect. Please try again.']);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Save Changes'; }
        }
    });

    // ── Add modal button ──────────────────────────────────
    document.getElementById('addMaintenanceBtn')?.addEventListener('click', () => {
        clearFormError('addMaintError');
        openModal('addMaintenanceModal');
    });

    // ── Archived modal button ─────────────────────────────
    document.getElementById('archivedMaintenanceBtn')?.addEventListener('click', () => {
        openModal('archivedMaintenanceModal');
    });

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

    // ── Search ────────────────────────────────────────────
    document.getElementById('maintSearch')?.addEventListener('input', function () {
        clearTimeout(_maintFilterDebounce);
        _maintFilterDebounce = setTimeout(applyMaintFilters, 300);
    });

    // ── Archived search ───────────────────────────────────
    document.getElementById('archivedMaintSearch')?.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('#archivedMaintTableBody tr').forEach(row => {
            row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    });

    // ── Status filter checkboxes ──────────────────────────
    document.querySelectorAll('.maint-status-filter').forEach(cb => {
        cb.addEventListener('change', applyMaintFilters);
    });

    // ── Filter panel toggle ───────────────────────────────
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

    // ── Close modals on backdrop click ────────────────────
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            const noBackdropClose = ['editTripModal', 'addTripModal', 'editMaintenanceModal', 'addMaintenanceModal'];
            if (e.target === this && !noBackdropClose.includes(this.id)) {
                closeModal(this.id);
            }
        });
    });

    // ── Clear password errors on typing ───────────────────
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
    if (!modal) return;
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';

    if (modalId === 'addMaintenanceModal') {
        document.getElementById('addMaintenanceForm')?.reset();
        clearFormError('addMaintError');
    } else if (modalId === 'maintArchiveWarning2') {
        document.getElementById('maintArchivePassword').value = '';
        clearPasswordError('maintArchivePasswordError');
    } else if (modalId === 'maintDeleteWarning2') {
        document.getElementById('maintDeletePassword').value = '';
        clearPasswordError('maintDeletePasswordError');
    } else if (modalId === 'editMaintenanceModal') {
        document.getElementById('editMaintenanceForm')?.reset();
        clearFormError('editMaintError');
    }
}

function closeMaintenanceModal() {
    closeModal('addMaintenanceModal');
}

// ── Add Maintenance ───────────────────────────────────────

async function handleAddMaintenance(e) {
    e.preventDefault();
    clearFormError('addMaintError');

    const payload = buildMaintenancePayload(e.target);

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

// ── Server Error Banner ───────────────────────────────────

function showMaintServerError(message, sub = '') {
    const banner = document.getElementById('maintServerError');
    const text   = document.getElementById('maintServerErrorText');
    const subEl  = document.getElementById('maintServerErrorSub');
    if (!banner || !text) return;
    text.textContent = message;
    if (subEl) subEl.textContent = sub;
    banner.style.display = 'flex';
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideMaintServerError() {
    const banner = document.getElementById('maintServerError');
    if (banner) banner.style.display = 'none';
}

// ── Empty State Row ───────────────────────────────────────

function showMaintEmptyState(message) {
    document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
        .forEach(r => r.style.display = 'none');
    const emptyRow = document.getElementById('maintEmptyStateRow');
    const emptyMsg = document.getElementById('maintEmptyStateMsg');
    if (emptyMsg) emptyMsg.textContent = message;
    if (emptyRow) emptyRow.style.display = '';
}

function hideMaintEmptyState() {
    const emptyRow = document.getElementById('maintEmptyStateRow');
    if (emptyRow) emptyRow.style.display = 'none';
}

// ── HTTP Error Subtitle ───────────────────────────────────

function httpMaintErrorSubtitle(status) {
    switch (status) {
        case 403: return 'You do not have permission to perform this action.';
        case 404: return 'The requested resource could not be found.';
        case 422: return 'The request contained invalid data. Please check your inputs.';
        case 500: return 'An unexpected server error occurred. Please try again later.';
        default:  return `Server responded with status ${status}.`;
    }
}

// ── Status value → proper-cased label ────────────────────

function normalizeMaintStatusValue(value) {
    const map = {
        pending:    'Pending',
        inprogress: 'In-Progress',
        completed:  'Completed',
        cancelled:  'Cancelled',
    };
    return map[value] ?? value;
}

// ── Restore all data rows ─────────────────────────────────

function restoreAllMaintRows() {
    document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
        .forEach(r => r.style.display = '');
}

// ── Main Filter Function ──────────────────────────────────

let _maintFilterDebounce = null;

async function applyMaintFilters() {
    hideMaintServerError();
    hideMaintEmptyState();
    restoreAllMaintRows();

    const query   = document.getElementById('maintSearch').value.trim();
    const checked = Array.from(document.querySelectorAll('.maint-status-filter:checked'))
        .map(cb => normalizeMaintStatusValue(cb.value));

    if (checked.length === 0) {
        showMaintEmptyState('No statuses selected. Use the filter to choose at least one.');
        return;
    }

    // ── Status filter only ────────────────────────────────
    if (!query) {
        try {
            const params   = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
            const response = await fetch(`/maintenance/filter-status?${params}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            const data = await response.json().catch(() => null);

            if (response.status === 404) {
                document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
                    .forEach(r => r.style.display = 'none');
                showMaintEmptyState(data?.message || `No maintenance records found for: ${checked.join(', ')}.`);
                return;
            }
            if (!response.ok) {
                showMaintServerError(data?.message || 'Filter failed.', httpMaintErrorSubtitle(response.status));
                return;
            }

            const resultIds = new Set((data.data ?? []).map(r => String(r.id)));
            let anyVisible  = false;
            document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
                .forEach(row => {
                    const visible = resultIds.has(String(row.dataset.maintId ?? ''));
                    row.style.display = visible ? '' : 'none';
                    if (visible) anyVisible = true;
                });
            if (!anyVisible) showMaintEmptyState(`No maintenance records found for: ${checked.join(', ')}.`);
        } catch (err) {
            console.error('Maintenance filter error:', err);
            showMaintServerError('Unable to connect to the server.', 'Please check your connection and try again.');
        }
        return;
    }

    // ── Search + status filter ────────────────────────────
    try {
        const response = await fetch(`/maintenance/search?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const data = await response.json().catch(() => null);

        if (response.status === 404) {
            document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
                .forEach(r => r.style.display = 'none');
            showMaintEmptyState(data?.message || `No results found for "${query}".`);
            return;
        }
        if (response.status === 422) {
            showMaintServerError(data?.message || 'Invalid search input.', httpMaintErrorSubtitle(422));
            return;
        }
        if (!response.ok) {
            showMaintServerError(data?.message || 'Search failed.', httpMaintErrorSubtitle(response.status));
            return;
        }

        const resultIds = new Set((data.data ?? []).map(r => String(r.id)));
        let anyVisible  = false;
        document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
            .forEach(row => {
                const maintId        = String(row.dataset.maintId ?? '');
                const rowStatus      = row.dataset.status ?? '';
                const inSearch       = resultIds.has(maintId);
                const inStatusFilter = Boolean(
                    checked.find(s => s.toLowerCase().replace(/-/g, '') === rowStatus)
                );
                const visible = inSearch && inStatusFilter;
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });

        if (!anyVisible) {
            document.querySelectorAll('.drivers-table tbody tr:not(#maintEmptyStateRow)')
                .forEach(r => r.style.display = 'none');
            const statusLabel = checked.length < 4 ? checked.join(' / ') : null;
            showMaintEmptyState(
                statusLabel
                    ? `No "${statusLabel}" records match "${query}".`
                    : `No results found for "${query}".`
            );
        }
    } catch (err) {
        console.error('Maintenance filter/search error:', err);
        showMaintServerError('Unable to connect to the server.', 'Please check your connection and try again.');
    }
}

function clearMaintFilters() {
    document.querySelectorAll('.maint-status-filter').forEach(cb => cb.checked = true);
    document.getElementById('maintSearch').value = '';
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