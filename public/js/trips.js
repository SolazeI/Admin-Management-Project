// ── Add Modal ────────────────────────────────────────────
document.getElementById('addTripBtn').addEventListener('click', () => {
    document.getElementById('addTripModal').classList.add('show');
});

function closeTripModal() {
    document.getElementById('addTripModal').classList.remove('show');
}

document.getElementById('addTripModal').addEventListener('click', function (e) {
    if (e.target === this) closeTripModal();
});

// ── Generic modal helpers ────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// ── Archived panel ───────────────────────────────────────
document.getElementById('archivedTripsBtn').addEventListener('click', () => {
    openModal('archivedTripsModal');
});

// Archived search filter
document.getElementById('archivedTripSearch').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#archivedTripsTableBody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});

// ── Trip Filter Panel ────────────────────────────────────
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

function applyTripFilters() {
    const query   = document.getElementById('tripSearch')?.value.trim().toLowerCase() ?? '';
    const checked = Array.from(document.querySelectorAll('.trip-status-filter:checked'))
        .map(cb => cb.value);
    document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;
        const truck       = cells[1]?.textContent.toLowerCase() ?? '';
        const driver      = cells[2]?.textContent.toLowerCase() ?? '';
        const destination = cells[4]?.textContent.toLowerCase() ?? '';
        const matchesSearch = !query
            || truck.includes(query)
            || driver.includes(query)
            || destination.includes(query);
        const badge  = row.querySelector('.status-badge');
        const status = Array.from(badge?.classList ?? [])
            .find(c => c.startsWith('status-') && c !== 'status-badge')
            ?.replace('status-', '') ?? '';
        row.style.display = (matchesSearch && checked.includes(status)) ? '' : 'none';
    });
}

// ── Search ───────────────────────────────────────────────
document.getElementById('tripSearch').addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;
        const truck       = cells[1]?.textContent.toLowerCase() ?? '';
        const driver      = cells[2]?.textContent.toLowerCase() ?? '';
        const destination = cells[4]?.textContent.toLowerCase() ?? '';
        const matches = !query || truck.includes(query) || driver.includes(query) || destination.includes(query);
        if (!matches) {
            row.style.display = 'none';
        } else {
            const badge  = row.querySelector('.status-badge');
            const status = Array.from(badge?.classList ?? [])
                .find(c => c.startsWith('status-') && c !== 'status-badge')
                ?.replace('status-', '') ?? '';
            const checked = Array.from(document.querySelectorAll('.trip-status-filter:checked'))
                .map(cb => cb.value);
            row.style.display = checked.includes(status) ? '' : 'none';
        }
    });
});

// ── Archive flow ─────────────────────────────────────────
let _archiveTripId = null;

function confirmArchiveTrip(id, tripNo) {
    _archiveTripId = id;
    document.getElementById('archiveTripLabel').textContent = tripNo;
    document.getElementById('tripArchivePassword').value = '';
    openModal('tripArchiveWarning1');
}
function proceedToArchivePassword() {
    closeModal('tripArchiveWarning1');
    openModal('tripArchiveWarning2');
}
function confirmArchiveAction() {
    const password = document.getElementById('tripArchivePassword').value.trim();
    if (!password) {
        document.getElementById('tripArchivePassword').focus();
        return;
    }
    const form = document.getElementById('archiveTripForm');
    form.action = `/trips/${_archiveTripId}/archive`;
    document.getElementById('archiveTripPasswordInput').value = password;
    closeModal('tripArchiveWarning2');
    form.submit();
}

// ── Delete flow (from archived panel) ────────────────────
let _deleteTripId = null;

function confirmDeleteTrip(id, tripNo) {
    _deleteTripId = id;
    document.getElementById('deleteTripLabel').textContent = tripNo;
    document.getElementById('tripDeletePassword').value = '';
    openModal('tripDeleteWarning1');
}
function proceedToDeletePassword() {
    closeModal('tripDeleteWarning1');
    openModal('tripDeleteWarning2');
}
function confirmDeleteAction() {
    const password = document.getElementById('tripDeletePassword').value.trim();
    if (!password) {
        document.getElementById('tripDeletePassword').focus();
        return;
    }
    const form = document.getElementById('deleteTripForm');
    form.action = `/trips/${_deleteTripId}/delete`;
    document.getElementById('deleteTripPasswordInput').value = password;
    closeModal('tripDeleteWarning2');
    form.submit();
}