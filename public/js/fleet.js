const FV = () => window.FormValidation;

// ── CSRF Token ────────────────────────────────────────────
function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

// ── Notice Line ───────────────────────────────────────────

function showNoticeError(message) {
    let notice = document.getElementById('fleetNoticeError');

    if (!notice) {
        notice = document.createElement('div');
        notice.id = 'fleetNoticeError';
        notice.className = 'notice-line';
        notice.style.cssText = 'border-left-color:#dc2626; background:#fef2f2; color:#991b1b; margin-bottom:14px;';

        // Insert it right after the content-header divider
        const header = document.querySelector('.content-header.app-divider');
        header.insertAdjacentElement('afterend', notice);
    }

    notice.textContent = message;
    notice.style.display = '';
    notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Auto-dismiss after 6 seconds
    clearTimeout(notice._dismissTimer);
    notice._dismissTimer = setTimeout(() => {
        notice.style.display = 'none';
    }, 6000);
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

// Inline error box helpers for fleet-edit-form panels
function showInlineError(form, errors) {
    const box  = form.querySelector('.fleet-form-error');
    const list = form.querySelector('.fleet-form-error-list');
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
    const box = form.querySelector('.fleet-form-error');
    if (box) box.style.display = 'none';
}

// ── Add Fleet Modal ───────────────────────────────────────

document.getElementById('addFleetBtn').addEventListener('click', () => {
    clearFormError('addFleetError');
    document.getElementById('addFleetModal').classList.add('show');
});

function closeFleetModal(id = 'addFleetModal') {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('show');

    if (id === 'addFleetModal') {
        const form = document.getElementById('addFleetForm');
        form?.reset();
        clearFormError('addFleetError');
        FV()?.clearFormFieldNotices(form);
    }

    if (id === 'deleteTruckModal2') {
        document.getElementById('deleteTruckPassword').value = '';
        const errEl = document.getElementById('deleteTruckPasswordError');
        if (errEl) errEl.style.display = 'none';
        pendingDeleteId = null;
    }
}

document.getElementById('addFleetModal').addEventListener('click', function (e) {
    if (e.target === this) closeFleetModal('addFleetModal');
});

document.getElementById('deleteTruckModal1').addEventListener('click', function (e) {
    if (e.target === this) closeFleetModal('deleteTruckModal1');
});

// ── Add Fleet Submit ──────────────────────────────────────

const addFleetForm = document.getElementById('addFleetForm');
if (addFleetForm) FV()?.setupRequiredBubbles(addFleetForm);

document.querySelectorAll('.fleet-edit-form').forEach(form => FV()?.setupRequiredBubbles(form));

addFleetForm?.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearFormError('addFleetError');

    if (!FV()?.validateFormBubbles(this)) {
        showFormError('addFleetError', 'addFleetErrorList', [
            'Please fix the highlighted fields before submitting.',
        ]);
        return;
    }

    const formData = new FormData(this);
    const payload  = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/fleet', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            closeFleetModal('addFleetModal');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            if (data?.errors) {
                showFormError('addFleetError', 'addFleetErrorList', data.errors);
            } else {
                showFormError('addFleetError', 'addFleetErrorList', [
                    data?.message || 'Something went wrong. Please try again.',
                ]);
            }
        }
    } catch (error) {
        console.error('Error adding truck:', error);
        showFormError('addFleetError', 'addFleetErrorList', [
            'Unable to connect to the server. Please check your connection and try again.',
        ]);
    }
});

// ── Edit Fleet Submit ─────────────────────────────────────

document.querySelectorAll('.fleet-edit-form').forEach(form => {
    // Clear inline error whenever the details panel is opened
    const details = form.closest('details');
    if (details) {
        details.addEventListener('toggle', () => {
            if (details.open) {
            clearInlineError(form);
            FV()?.clearFormFieldNotices(form);
        }
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearInlineError(this);

        if (!FV()?.validateFormBubbles(this)) {
            showInlineError(this, 'Please fix the highlighted fields before submitting.');
            return;
        }

        const truckId  = this.dataset.id;
        const formData = new FormData(this);
        const payload  = Object.fromEntries(formData.entries());

        // Remove Laravel _token from JSON payload — sent via header instead
        delete payload._token;

        const saveBtn = this.querySelector('.fleet-save-btn');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

        try {
            const response = await fetch(`/fleet/${truckId}`, {
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
                    showInlineError(this, data.errors);
                } else {
                    showInlineError(this, [
                        data?.message || 'Something went wrong. Please try again.',
                    ]);
                }
            }
        } catch (error) {
            console.error('Error updating truck:', error);
            showInlineError(this, [
                'Unable to connect to the server. Please check your connection and try again.',
            ]);
        } finally {
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
        }
    });
});

// ── Delete Truck ──────────────────────────────────────────
let pendingDeleteId = null;

function confirmDeleteTruck(truckId, truckCode) {
    pendingDeleteId = truckId;
    document.getElementById('deleteTruckCode').textContent = truckCode;
    document.getElementById('deleteTruckModal1').classList.add('show');
}

function proceedToDeleteTruckPassword() {
    closeFleetModal('deleteTruckModal1');

    // Reset password field and error before opening
    document.getElementById('deleteTruckPassword').value = '';
    const errEl = document.getElementById('deleteTruckPasswordError');
    if (errEl) errEl.style.display = 'none';

    document.getElementById('deleteTruckModal2').classList.add('show');

    // Auto-focus the password input
    setTimeout(() => document.getElementById('deleteTruckPassword').focus(), 100);
}

async function confirmTruckDeleteAction() {
    const password = document.getElementById('deleteTruckPassword').value.trim();
    const errEl    = document.getElementById('deleteTruckPasswordError');
    const errText  = document.getElementById('deleteTruckPasswordErrorText');

    if (!password) {
        errText.textContent = 'Please enter the admin password.';
        errEl.style.display = 'flex';
        return;
    }

    errEl.style.display = 'none';

    try {
        const response = await fetch(`/fleet/${pendingDeleteId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ password }),
        });

        if (response.ok) {
            closeFleetModal('deleteTruckModal2');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);

            if (response.status === 403) {
                // Wrong password — stay on modal, show inline error
                errText.textContent = data?.message || 'Incorrect admin password.';
                errEl.style.display = 'flex';
                document.getElementById('deleteTruckPassword').value = '';
                document.getElementById('deleteTruckPassword').focus();
                return;
            }

            // 422 / 404 / 500 — close modal, show notice line
            closeFleetModal('deleteTruckModal2');
            showNoticeError(data?.message || 'Could not delete this truck. Please try again.');
        }
    } catch (error) {
        console.error('Error deleting truck:', error);
        errText.textContent = 'Unable to connect to the server. Please try again.';
        errEl.style.display = 'flex';
    } finally {
        if (!document.getElementById('deleteTruckModal2').classList.contains('show')) {
            pendingDeleteId = null;
        }
    }
}

// Allow Enter key to confirm from the password field
document.getElementById('deleteTruckPassword').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') confirmTruckDeleteAction();
});

// Close on backdrop click
document.getElementById('deleteTruckModal2').addEventListener('click', function (e) {
    if (e.target === this) closeFleetModal('deleteTruckModal2');
});

// ── Search ────────────────────────────────────────────────
// ── Server Error Banner ───────────────────────────────────
function showFleetServerError(message, sub = '') {
    const banner = document.getElementById('fleetServerError');
    const text   = document.getElementById('fleetServerErrorText');
    const subEl  = document.getElementById('fleetServerErrorSub');
    if (!banner || !text) return;
    text.textContent = message;
    if (subEl) subEl.textContent = sub;
    banner.style.display = 'flex';
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function hideFleetServerError() {
    const banner = document.getElementById('fleetServerError');
    if (banner) banner.style.display = 'none';
}

// ── Empty State ───────────────────────────────────────────
function showFleetEmptyState(message) {
    document.querySelectorAll('.fleet-card').forEach(c => c.style.display = 'none');
    const el  = document.getElementById('fleetEmptyState');
    const msg = document.getElementById('fleetEmptyStateMsg');
    if (msg) msg.textContent = message;
    if (el)  el.style.display = '';
}
function hideFleetEmptyState() {
    const el = document.getElementById('fleetEmptyState');
    if (el) el.style.display = 'none';
}

// ── HTTP Error Subtitle ───────────────────────────────────
function httpFleetErrorSubtitle(status) {
    switch (status) {
        case 422: return 'The request contained invalid data.';
        case 500: return 'An unexpected server error occurred. Please try again later.';
        default:  return `Server responded with status ${status}.`;
    }
}

// ── Status value → proper-cased label ────────────────────
function normalizeFleetStatusValue(value) {
    const map = {
        available:  'Available',
        intransit:  'In-Transit',
        maintenance:'Maintenance',
        inactive:   'Inactive',
    };
    return map[value] ?? value;
}

// ── Restore all cards ─────────────────────────────────────
function restoreAllFleetCards() {
    document.querySelectorAll('.fleet-card').forEach(c => c.style.display = '');
}

// ── Search (debounced) ────────────────────────────────────
let _fleetFilterDebounce = null;
document.getElementById('truckSearch').addEventListener('input', function () {
    clearTimeout(_fleetFilterDebounce);
    _fleetFilterDebounce = setTimeout(applyFilters, 300);
});

// ── Filter Panel toggle ───────────────────────────────────
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

// ── Checkboxes ────────────────────────────────────────────
document.querySelectorAll('.status-filter').forEach(cb => {
    cb.addEventListener('change', applyFilters);
});

function clearFilters() {
    document.querySelectorAll('.status-filter').forEach(cb => cb.checked = true);
    document.getElementById('truckSearch').value = '';
    applyFilters();
}

// ── Core filter logic ─────────────────────────────────────
async function applyFilters() {
    hideFleetServerError();
    hideFleetEmptyState();
    restoreAllFleetCards();

    const query   = document.getElementById('truckSearch').value.trim();
    const checked = Array.from(document.querySelectorAll('.status-filter:checked'))
        .map(cb => normalizeFleetStatusValue(cb.value));

    if (checked.length === 0) {
        showFleetEmptyState('No statuses selected. Use the filter to choose at least one.');
        return;
    }

    // ── Status filter only ────────────────────────────────
    if (!query) {
        try {
            const params   = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
            const response = await fetch(`/fleet/filter-status?${params}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            const data = await response.json().catch(() => null);
            if (response.status === 404) {
                document.querySelectorAll('.fleet-card').forEach(c => c.style.display = 'none');
                showFleetEmptyState(data?.message || `No trucks found for: ${checked.join(', ')}.`);
                return;
            }
            if (!response.ok) {
                showFleetServerError(data?.message || 'Filter failed.', httpFleetErrorSubtitle(response.status));
                return;
            }
            const resultIds = new Set((data.data ?? []).map(t => String(t.id)));
            let anyVisible  = false;
            document.querySelectorAll('.fleet-card').forEach(card => {
                const visible = resultIds.has(String(card.dataset.fleetId ?? ''));
                card.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });
            if (!anyVisible) showFleetEmptyState(`No trucks found for: ${checked.join(', ')}.`);
        } catch (err) {
            console.error('Fleet filter error:', err);
            showFleetServerError('Unable to connect to the server.', 'Please check your connection and try again.');
        }
        return;
    }

    // ── Search + status filter ────────────────────────────
    try {
        const response = await fetch(`/fleet/search?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const data = await response.json().catch(() => null);
        if (response.status === 404) {
            document.querySelectorAll('.fleet-card').forEach(c => c.style.display = 'none');
            showFleetEmptyState(data?.message || `No results found for "${query}".`);
            return;
        }
        if (response.status === 422) {
            showFleetServerError(data?.message || 'Invalid search input.', httpFleetErrorSubtitle(422));
            return;
        }
        if (!response.ok) {
            showFleetServerError(data?.message || 'Search failed.', httpFleetErrorSubtitle(response.status));
            return;
        }
        const resultIds = new Set((data.data ?? []).map(t => String(t.id)));
        let anyVisible  = false;
        document.querySelectorAll('.fleet-card').forEach(card => {
            const fleetId        = String(card.dataset.fleetId ?? '');
            const cardStatus     = card.dataset.status ?? '';
            const inSearch       = resultIds.has(fleetId);
            const inStatusFilter = Boolean(
                checked.find(s => s.toLowerCase().replace(/-/g, '') === cardStatus)
            );
            const visible = inSearch && inStatusFilter;
            card.style.display = visible ? '' : 'none';
            if (visible) anyVisible = true;
        });
        if (!anyVisible) {
            const statusLabel = checked.length < 4 ? checked.join(' / ') : null;
            showFleetEmptyState(
                statusLabel
                    ? `No "${statusLabel}" trucks match "${query}".`
                    : `No results found for "${query}".`
            );
        }
    } catch (err) {
        console.error('Fleet filter/search error:', err);
        showFleetServerError('Unable to connect to the server.', 'Please check your connection and try again.');
    }
}