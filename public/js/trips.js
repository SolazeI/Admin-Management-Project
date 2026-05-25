const FV = () => window.FormValidation;

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

function runTripFormValidation(form) {
    const fv = FV();
    if (!fv || typeof fv.validateTripForm !== 'function') {
        return fv?.validateFormBubbles?.(form) ?? true;
    }
    return fv.validateTripForm(form);
}

function mapServerErrorsToForm(form, errors) {
    if (!form || !errors || typeof errors !== 'object') return;
    const fv = FV();
    Object.entries(errors).forEach(([field, messages]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const msg = Array.isArray(messages) ? messages[0] : messages;
        if (input && msg && fv) fv.showFieldNotice(input, msg);
    });
}

/** Omit empty optionals so nullable Laravel rules pass on JSON requests. */
function buildTripPayload(form) {
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    delete payload._token;

    ['date_issued', 'origin', 'destination', 'departure_time', 'arrival_time', 'remarks', 'trip_no'].forEach((field) => {
        if (payload[field] === '' || payload[field] == null) delete payload[field];
    });

    ['distance_km', 'amount'].forEach((field) => {
        const raw = (payload[field] ?? '').toString().trim();
        if (!raw) {
            delete payload[field];
        } else {
            const cleaned = raw.replace(/[^\d.]/g, '');
            if (!cleaned) delete payload[field];
            else payload[field] = cleaned;
        }
    });

    return payload;
}

// ── Server Error Banner ───────────────────────────────────

function showTripServerError(message, sub = '') {
    const banner = document.getElementById('tripServerError');
    const text   = document.getElementById('tripServerErrorText');
    const subEl  = document.getElementById('tripServerErrorSub');
    if (!banner || !text) return;
    text.textContent = message;
    if (subEl) subEl.textContent = sub;
    banner.style.display = 'flex';
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideTripServerError() {
    const banner = document.getElementById('tripServerError');
    if (banner) banner.style.display = 'none';
}

// ── Empty State Row ───────────────────────────────────────

function showTripEmptyState(message) {
    document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
        .forEach(r => r.style.display = 'none');

    const emptyRow = document.getElementById('tripEmptyStateRow');
    const emptyMsg = document.getElementById('tripEmptyStateMsg');
    const noData   = document.getElementById('tripNoDataRow');

    if (noData)   noData.style.display = 'none';
    if (emptyMsg) emptyMsg.textContent  = message;
    if (emptyRow) emptyRow.style.display = '';
}

function hideTripEmptyState() {
    const emptyRow = document.getElementById('tripEmptyStateRow');
    if (emptyRow) emptyRow.style.display = 'none';

    // Restore the "no data" row only if there are no real data rows
    const hasRows = document.querySelectorAll(
        '.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)'
    ).length > 0;

    const noData = document.getElementById('tripNoDataRow');
    if (noData) noData.style.display = hasRows ? 'none' : '';
}

// ── HTTP Error Subtitle ───────────────────────────────────

function httpErrorSubtitle(status) {
    switch (status) {
        case 403: return 'You do not have permission to perform this action.';
        case 404: return 'The requested resource could not be found.';
        case 422: return 'The request contained invalid data. Please check your inputs.';
        case 500: return 'An unexpected server error occurred. Please try again later.';
        default:  return `Server responded with status ${status}.`;
    }
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
    const form = document.getElementById('addTripForm');
    form?.reset();
    clearFormError('addTripError');
    FV()?.clearFormFieldNotices(form);
    closeModal('addTripModal');
}

// ── Add Trip Submit ───────────────────────────────────────

const addTripForm = document.getElementById('addTripForm');

addTripForm?.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearFormError('addTripError');

    if (!runTripFormValidation(this)) {
        showFormError('addTripError', 'addTripErrorList', [
            'Please fix the highlighted fields before submitting.',
        ]);
        return;
    }

    const payload = buildTripPayload(this);

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
                mapServerErrorsToForm(this, data.errors);
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
    FV()?.clearFormFieldNotices(document.getElementById('editTripForm'));
    openModal('editTripModal');
}

document.getElementById('editTripForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearFormError('editTripError');

    if (!runTripFormValidation(this)) {
        showFormError('editTripError', 'editTripErrorList', [
            'Please fix the highlighted fields before submitting.',
        ]);
        return;
    }

    const id = document.getElementById('editTripId')?.value;
    const payload = buildTripPayload(this);

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
            if (data?.errors) {
                mapServerErrorsToForm(this, data.errors);
                showFormError('editTripError', 'editTripErrorList', data.errors);
            } else {
                showFormError('editTripError', 'editTripErrorList', [
                    data?.message || 'Something went wrong.',
                ]);
            }
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
                    showTripServerError(
                        data?.message || 'Transition failed. Please try again.',
                        httpErrorSubtitle(response.status)
                    );
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
    document.getElementById('tripSearch').value = '';
    applyTripFilters();
}

// ── Search Input ──────────────────────────────────────────

let _filterDebounce = null;

document.getElementById('tripSearch').addEventListener('input', function () {
    clearTimeout(_filterDebounce);
    _filterDebounce = setTimeout(applyTripFilters, 300);
});

// ── Status value → proper cased label ────────────────────

function normalizeStatusValue(value) {
    const map = {
        draft:      'Draft',
        intransit:  'In-Transit',
        completed:  'Completed',
        cancelled:  'Cancelled',
    };
    return map[value] ?? value;
}

// ── Restore all data rows (used before re-filtering) ──────

function restoreAllTripRows() {
    document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
        .forEach(r => r.style.display = '');
}

// ── Main Filter Function ──────────────────────────────────

async function applyTripFilters() {
    hideTripServerError();
    hideTripEmptyState();
    restoreAllTripRows();

    const query = document.getElementById('tripSearch').value.trim();

    const checked = Array.from(
        document.querySelectorAll('.trip-status-filter:checked')
    ).map(cb => normalizeStatusValue(cb.value));

    // Nothing checked — short circuit immediately, no round-trip needed
    if (checked.length === 0) {
        showTripEmptyState('No statuses selected. Use the filter to choose at least one.');
        return;
    }

    // ── No search query: status filter ───────────────────────
    if (!query) {
        try {
            const params = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
            const response = await fetch(`/trips/filter-status?${params}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            const data = await response.json().catch(() => null);

            if (response.status === 404) {
                document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
                    .forEach(r => r.style.display = 'none');
                showTripEmptyState(data?.message || `No trip tickets found for: ${checked.join(', ')}.`);
                return;
            }

            if (!response.ok) {
                showTripServerError(data?.message || 'Filter failed.', httpErrorSubtitle(response.status));
                return;
            }

            const resultIds = new Set((data.data ?? []).map(t => String(t.id)));
            let anyVisible = false;
            document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
                .forEach(row => {
                    const visible = resultIds.has(String(row.dataset.tripId ?? ''));
                    row.style.display = visible ? '' : 'none';
                    if (visible) anyVisible = true;
                });
            if (!anyVisible) {
                showTripEmptyState(`No trip tickets found for: ${checked.join(', ')}.`);
            }
        } catch (err) {
            console.error('Trip filter error:', err);
            showTripServerError('Unable to connect to the server.', 'Please check your connection and try again.');
        }
        return;
    }

    // ── Search query present ──────────────────────────────────
    try {
        const response = await fetch(`/trips/search?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const data = await response.json().catch(() => null);

        if (response.status === 404) {
            document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
                .forEach(r => r.style.display = 'none');
            showTripEmptyState(data?.message || `No results found for "${query}".`);
            return;
        }

        if (response.status === 422) {
            showTripServerError(data?.message || 'Invalid search input.', httpErrorSubtitle(422));
            return;
        }

        if (!response.ok) {
            showTripServerError(data?.message || 'Search failed.', httpErrorSubtitle(response.status));
            return;
        }

        const resultIds = new Set((data.data ?? []).map(t => String(t.id)));
        let anyVisible = false;
        document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
            .forEach(row => {
                const tripId = String(row.dataset.tripId ?? '');
                const badge  = row.querySelector('.status-badge');
                const status = Array.from(badge?.classList ?? [])
                    .find(c => c.startsWith('status-') && c !== 'status-badge')
                    ?.replace('status-', '') ?? '';
                const inSearchResults = resultIds.has(tripId);
                const inStatusFilter  = Boolean(
                    checked.find(s => s.toLowerCase().replace(/-/g, '') === status)
                );
                const visible = inSearchResults && inStatusFilter;
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });

        if (!anyVisible) {
            document.querySelectorAll('.drivers-table tbody tr:not(#tripEmptyStateRow):not(#tripNoDataRow)')
                .forEach(r => r.style.display = 'none');
            const statusLabel = checked.length < 4 ? checked.join(' / ') : null;
            showTripEmptyState(
                statusLabel
                    ? `No "${statusLabel}" trips match "${query}".`
                    : `No results found for "${query}".`
            );
        }
    } catch (err) {
        console.error('Trip filter/search error:', err);
        showTripServerError('Unable to connect to the server.', 'Please check your connection and try again.');
    }
}