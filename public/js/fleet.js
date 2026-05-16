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
        document.getElementById('addFleetForm').reset();
        clearFormError('addFleetError');
    }
}

document.getElementById('addFleetModal').addEventListener('click', function (e) {
    if (e.target === this) closeFleetModal('addFleetModal');
});

document.getElementById('deleteTruckModal1').addEventListener('click', function (e) {
    if (e.target === this) closeFleetModal('deleteTruckModal1');
});

// ── Add Fleet Submit ──────────────────────────────────────

document.getElementById('addFleetForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    clearFormError('addFleetError');

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
            if (details.open) clearInlineError(form);
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearInlineError(this);

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

async function proceedToDeleteTruck() {
    if (!pendingDeleteId) return;

    try {
        const response = await fetch(`/fleet/${pendingDeleteId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
        });

        if (response.ok) {
            closeFleetModal('deleteTruckModal1');
            location.reload();
        } else {
            const data = await response.json().catch(() => null);
            closeFleetModal('deleteTruckModal1');

            // Surface the error in the relevant card's edit panel
            const card = document.querySelector(`.fleet-card[data-id="${pendingDeleteId}"]`);
            if (card) {
                const details = card.querySelector('details');
                const form    = card.querySelector('.fleet-edit-form');
                if (details && form) {
                    details.open = true;
                    showInlineError(form, [data?.message || 'Could not delete this truck. Please try again.']);
                }
            }
        }
    } catch (error) {
        console.error('Error deleting truck:', error);
        closeFleetModal('deleteTruckModal1');
    } finally {
        pendingDeleteId = null;
    }
}

// ── Search ────────────────────────────────────────────────

document.getElementById('truckSearch').addEventListener('input', applyFilters);

// ── Filter Panel toggle ───────────────────────────────────

const filterBtn   = document.getElementById('filterBtn');
const filterPanel = document.getElementById('filterPanel');

filterBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = filterPanel.style.display === 'block';
    filterPanel.style.display = isOpen ? 'none' : 'block';
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
    applyFilters();
}

// ── Core filter logic ─────────────────────────────────────

function applyFilters() {
    const query = document.getElementById('truckSearch').value.trim().toLowerCase();

    const checked = Array.from(document.querySelectorAll('.status-filter:checked'))
        .map(cb => cb.value);

    document.querySelectorAll('.fleet-card').forEach(card => {
        const code   = card.querySelector('.fleet-code')?.textContent.toLowerCase() ?? '';
        const model  = card.querySelector('.fleet-sub')?.textContent.toLowerCase() ?? '';
        const status = card.querySelector('.status-badge')?.className
            .split(' ')
            .find(c => c.startsWith('status-') && c !== 'status-badge')
            ?.replace('status-', '') ?? '';

        const matchesSearch = !query || code.includes(query) || model.includes(query);
        const matchesStatus = checked.includes(status);

        card.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}