// ── Add Modal ────────────────────────────────────────────
document.getElementById('addMaintenanceBtn').addEventListener('click', () => {
    document.getElementById('addMaintenanceModal').classList.add('show');
});

function closeMaintenanceModal() {
    document.getElementById('addMaintenanceModal').classList.remove('show');
}

document.getElementById('addMaintenanceModal').addEventListener('click', function (e) {
    if (e.target === this) closeMaintenanceModal();
});

// ── Generic modal helpers ────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// ── Archived panel ───────────────────────────────────────
document.getElementById('archivedMaintenanceBtn').addEventListener('click', () => {
    openModal('archivedMaintenanceModal');
});

document.getElementById('archivedMaintSearch').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#archivedMaintTableBody tr').forEach(row => {
        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
});

// ── Filter Panel ─────────────────────────────────────────
const maintFilterBtn   = document.getElementById('maintFilterBtn');
const maintFilterPanel = document.getElementById('maintFilterPanel');

maintFilterBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    maintFilterPanel.style.display = maintFilterPanel.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
    if (!maintFilterPanel.contains(e.target) && e.target !== maintFilterBtn) {
        maintFilterPanel.style.display = 'none';
    }
});

document.querySelectorAll('.maint-status-filter').forEach(cb => {
    cb.addEventListener('change', applyMaintFilters);
});

function clearMaintFilters() {
    document.querySelectorAll('.maint-status-filter').forEach(cb => cb.checked = true);
    applyMaintFilters();
}

// ── Search + Filter ──────────────────────────────────────
document.getElementById('maintSearch').addEventListener('input', applyMaintFilters);

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

// ── Archive flow ─────────────────────────────────────────
let _archiveMaintId = null;

function confirmArchiveMaint(id, issue) {
    _archiveMaintId = id;
    document.getElementById('archiveMaintLabel').textContent = issue;
    document.getElementById('maintArchivePassword').value = '';
    openModal('maintArchiveWarning1');
}
function proceedToMaintArchivePassword() {
    closeModal('maintArchiveWarning1');
    openModal('maintArchiveWarning2');
}
function confirmMaintArchiveAction() {
    const password = document.getElementById('maintArchivePassword').value.trim();
    if (!password) {
        document.getElementById('maintArchivePassword').focus();
        return;
    }
    const form = document.getElementById('archiveMaintForm');
    form.action = `/maintenance/${_archiveMaintId}/archive`;
    document.getElementById('archiveMaintPasswordInput').value = password;
    closeModal('maintArchiveWarning2');
    form.submit();
}

// ── Delete flow (from archived panel) ────────────────────
let _deleteMaintId = null;

function confirmDeleteMaint(id, issue) {
    _deleteMaintId = id;
    document.getElementById('deleteMaintLabel').textContent = issue;
    document.getElementById('maintDeletePassword').value = '';
    openModal('maintDeleteWarning1');
}
function proceedToMaintDeletePassword() {
    closeModal('maintDeleteWarning1');
    openModal('maintDeleteWarning2');
}
function confirmMaintDeleteAction() {
    const password = document.getElementById('maintDeletePassword').value.trim();
    if (!password) {
        document.getElementById('maintDeletePassword').focus();
        return;
    }
    const form = document.getElementById('deleteMaintForm');
    form.action = `/maintenance/${_deleteMaintId}/delete`;
    document.getElementById('deleteMaintPasswordInput').value = password;
    closeModal('maintDeleteWarning2');
    form.submit();
}