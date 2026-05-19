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

function showInlineError(form, errors) {
    const box  = form.querySelector('.trip-form-error');
    const list = form.querySelector('.trip-form-error-list');
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
    const box = form.querySelector('.trip-form-error');
    if (box) box.style.display = 'none';
}

function showPasswordError(errorId, textId, message) {
    const el   = document.getElementById(errorId);
    const text = document.getElementById(textId);
    if (el && text) {
        text.textContent = message;
        el.style.display = 'block';
    }
}

function clearPasswordError(errorId) {
    const el = document.getElementById(errorId);
    if (el) el.style.display = 'none';
}

// ── Generic Modal Helpers ─────────────────────────────────

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('show');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('show');

    if (id === 'tripArchiveWarning2') {
        clearPasswordError('tripArchivePasswordError');
        document.getElementById('tripArchivePassword').value = '';
    }
    if (id === 'tripDeleteWarning2') {
        clearPasswordError('tripDeletePasswordError');
        document.getElementById('tripDeletePassword').value = '';
    } 
    if (id === 'editTripModal') {
        document.getElementById('editTripForm').reset();
        clearFormError('editTripError');
    }
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

// Clear password errors when typing
document.getElementById('tripArchivePassword')?.addEventListener('input', () => {
    clearPasswordError('tripArchivePasswordError');
});
document.getElementById('tripDeletePassword')?.addEventListener('input', () => {
    clearPasswordError('tripDeletePasswordError');
});

// ── Add Trip Modal ────────────────────────────────────────

document.getElementById('addTripBtn').addEventListener('click', () => {
    clearFormError('addTripError');
    openModal('addTripModal');
});

function closeTripModal() {
    document.getElementById('addTripForm').reset();
    clearFormError('addTripError');
    closeModal('addTripModal');
}

// ── Add Trip Submit ───────────────────────────────────────

document.getElementById('addTripForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearFormError('addTripError');

    const formData = new FormData(this);
    const payload  = Object.fromEntries(formData.entries());
    delete payload._token;

    const btn = this.querySelector('[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    try {
        const response = await fetch('/trips', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            closeTripModal();
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            if (data?.errors) {
                showFormError('addTripError', 'addTripErrorList', data.errors);
            } else {
                showFormError('addTripError', 'addTripErrorList', [
                    data?.message || 'Something went wrong. Please try again.',
                ]);
            }
        }
    } catch (error) {
        console.error('Error adding trip:', error);
        showFormError('addTripError', 'addTripErrorList', [
            'Unable to connect to the server. Please check your connection and try again.',
        ]);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save as Draft'; }
    }
});

// ── Edit Trip Forms ───────────────────────────────────────

function openEditTrip(id) {
    const row = document.querySelector(`tr[data-trip-id="${id}"]`);
    if (!row) return;

    document.getElementById('editTripId').value          = id;
    document.getElementById('editTripNo').value          = row.dataset.tripNo;
    document.getElementById('editTripDateIssued').value  = row.dataset.dateIssued;
    document.getElementById('editTripTruckId').value     = row.dataset.truckId;
    document.getElementById('editTripDriverId').value    = row.dataset.driverId;
    document.getElementById('editTripOrigin').value      = row.dataset.origin;
    document.getElementById('editTripDestination').value = row.dataset.destination;
    document.getElementById('editTripDeparture').value   = row.dataset.departure;
    document.getElementById('editTripArrival').value     = row.dataset.arrival;
    document.getElementById('editTripDistance').value    = row.dataset.distance;
    document.getElementById('editTripAmount').value      = row.dataset.amount;
    document.getElementById('editTripRemarks').value     = row.dataset.remarks;

    clearFormError('editTripError');
    openModal('editTripModal');
}

document.getElementById('editTripForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearFormError('editTripError');

    const id      = document.getElementById('editTripId').value;
    const payload = {
        trip_no:        document.getElementById('editTripNo').value,
        date_issued:    document.getElementById('editTripDateIssued').value,
        truck_id:       document.getElementById('editTripTruckId').value,
        driver_id:      document.getElementById('editTripDriverId').value,
        origin:         document.getElementById('editTripOrigin').value,
        destination:    document.getElementById('editTripDestination').value,
        departure_time: document.getElementById('editTripDeparture').value,
        arrival_time:   document.getElementById('editTripArrival').value,
        distance_km:    document.getElementById('editTripDistance').value,
        amount:         document.getElementById('editTripAmount').value,
        remarks:        document.getElementById('editTripRemarks').value,
    };

    const btn = this.querySelector('[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    try {
        const response = await fetch(`/trips/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            closeModal('editTripModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showFormError('editTripError', 'editTripErrorList',
                data?.errors ? Object.values(data.errors).flat() : [data?.message || 'Something went wrong.']
            );
        }
    } catch (err) {
        showFormError('editTripError', 'editTripErrorList', ['Unable to connect. Please try again.']);
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Save Changes'; }
    }
});

// ── Status Transitions ────────────────────────────────────

document.querySelectorAll('.trip-transition-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const tripId  = this.dataset.id;
        const status  = this.dataset.status;
        const confirm = this.dataset.confirm;

        if (confirm && !window.confirm(confirm)) return;

        this.disabled = true;

        try {
            const response = await fetch(`/trips/${tripId}/transition`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({ status }),
            });

            if (response.ok) {
                location.reload();
            } else {
                const data = await response.json().catch(() => null);
                // Surface error in this row's edit panel if available
                const row  = this.closest('tr');
                const form = row?.querySelector('.trip-edit-form');
                if (form) {
                    const details = form.closest('details');
                    if (details) details.open = true;
                    showInlineError(form, [data?.message || 'Transition failed. Please try again.']);
                } else {
                    alert(data?.message || 'Transition failed. Please try again.');
                }
            }
        } catch (error) {
            console.error('Error transitioning trip:', error);
            alert('Unable to connect to the server. Please try again.');
        } finally {
            this.disabled = false;
        }
    });
});

// ── Unarchive (Restore) ───────────────────────────────────

document.querySelectorAll('.trip-unarchive-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const tripId = this.dataset.id;
        this.disabled = true;

        try {
            const response = await fetch(`/trips/${tripId}/unarchive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
            });

            if (response.ok) {
                closeModal('archivedTripsModal');
                location.reload();
            } else {
                const data = await response.json().catch(() => null);
                alert(data?.message || 'Failed to restore trip. Please try again.');
            }
        } catch (error) {
            console.error('Error restoring trip:', error);
            alert('Unable to connect to the server. Please try again.');
        } finally {
            this.disabled = false;
        }
    });
});

// ── Archive Flow ──────────────────────────────────────────

let _archiveTripId = null;

function confirmArchiveTrip(id, tripNo) {
    _archiveTripId = id;
    document.getElementById('archiveTripLabel').textContent = tripNo;
    document.getElementById('tripArchivePassword').value = '';
    clearPasswordError('tripArchivePasswordError');
    openModal('tripArchiveWarning1');
}

function proceedToArchivePassword() {
    closeModal('tripArchiveWarning1');
    openModal('tripArchiveWarning2');
}

async function confirmArchiveAction() {
    const password = document.getElementById('tripArchivePassword').value.trim();

    if (!password) {
        showPasswordError('tripArchivePasswordError', 'tripArchivePasswordErrorText', 'Please enter the admin password.');
        return;
    }

    try {
        const response = await fetch(`/trips/${_archiveTripId}/archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeModal('tripArchiveWarning2');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showPasswordError(
                'tripArchivePasswordError',
                'tripArchivePasswordErrorText',
                data?.message || 'Incorrect password. Please try again.'
            );
        }
    } catch (error) {
        console.error('Error archiving trip:', error);
        showPasswordError('tripArchivePasswordError', 'tripArchivePasswordErrorText', 'Unable to connect. Please try again.');
    }
}

// ── Delete Flow ───────────────────────────────────────────

let _deleteTripId = null;

function confirmDeleteTrip(id, tripNo) {
    _deleteTripId = id;
    document.getElementById('deleteTripLabel').textContent = tripNo;
    document.getElementById('tripDeletePassword').value = '';
    clearPasswordError('tripDeletePasswordError');
    openModal('tripDeleteWarning1');
}

function proceedToDeletePassword() {
    closeModal('tripDeleteWarning1');
    openModal('tripDeleteWarning2');
}

async function confirmDeleteAction() {
    const password = document.getElementById('tripDeletePassword').value.trim();

    if (!password) {
        showPasswordError('tripDeletePasswordError', 'tripDeletePasswordErrorText', 'Please enter the admin password.');
        return;
    }

    try {
        const response = await fetch(`/trips/${_deleteTripId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeModal('tripDeleteWarning2');
            closeModal('archivedTripsModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            showPasswordError(
                'tripDeletePasswordError',
                'tripDeletePasswordErrorText',
                data?.message || 'Incorrect password. Please try again.'
            );
        }
    } catch (error) {
        console.error('Error deleting trip:', error);
        showPasswordError('tripDeletePasswordError', 'tripDeletePasswordErrorText', 'Unable to connect. Please try again.');
    }
}

// ── Archived Panel ────────────────────────────────────────

document.getElementById('archivedTripsBtn').addEventListener('click', () => {
    openModal('archivedTripsModal');
});

document.getElementById('archivedTripSearch').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#archivedTripsTableBody tr').forEach(row => {
        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
});

// ── Trip Filter Panel ─────────────────────────────────────

const tripFilterBtn   = document.getElementById('tripFilterBtn');
const tripFilterPanel = document.getElementById('tripFilterPanel');

tripFilterBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    tripFilterPanel.style.display = tripFilterPanel.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
    if (!tripFilterPanel.contains(e.target) && e.target !== tripFilterBtn) {
        tripFilterPanel.style.display = 'none';
    }
});

document.querySelectorAll('.trip-status-filter').forEach(cb => {
    cb.addEventListener('change', applyTripFilters);
});

function clearTripFilters() {
    document.querySelectorAll('.trip-status-filter').forEach(cb => cb.checked = true);
    applyTripFilters();
}

// ── Search ────────────────────────────────────────────────

document.getElementById('tripSearch').addEventListener('input', applyTripFilters);

function applyTripFilters() {
    const query   = document.getElementById('tripSearch').value.trim().toLowerCase();
    const checked = Array.from(document.querySelectorAll('.trip-status-filter:checked')).map(cb => cb.value);

    document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;

        const truck       = cells[1]?.textContent.toLowerCase() ?? '';
        const driver      = cells[2]?.textContent.toLowerCase() ?? '';
        const destination = cells[4]?.textContent.toLowerCase() ?? '';
        const matchesSearch = !query || truck.includes(query) || driver.includes(query) || destination.includes(query);

        const badge  = row.querySelector('.status-badge');
        const status = Array.from(badge?.classList ?? [])
            .find(c => c.startsWith('status-') && c !== 'status-badge')
            ?.replace('status-', '') ?? '';

        row.style.display = (matchesSearch && checked.includes(status)) ? '' : 'none';
    });
}