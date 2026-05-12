document.getElementById('addMaintenanceBtn').addEventListener('click', () => {
    document.getElementById('addMaintenanceModal').classList.add('show');
});

function closeMaintenanceModal() {
    document.getElementById('addMaintenanceModal').classList.remove('show');
}

document.getElementById('addMaintenanceModal').addEventListener('click', function (e) {
    if (e.target === this) closeMaintenanceModal();
});

// ── Maintenance Filter Panel ─────────────────────────────
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

function applyMaintFilters() {
    const query   = document.getElementById('maintSearch')?.value.trim().toLowerCase() ?? '';
    const checked = Array.from(document.querySelectorAll('.maint-status-filter:checked'))
        .map(cb => cb.value);

    document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
        const truck = row.dataset.truck ?? '';
        const issue = row.dataset.issue ?? '';
        const notes = row.dataset.notes ?? '';

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

// ── Search ───────────────────────────────────────────────
document.getElementById('maintSearch').addEventListener('input', function () {
    applyMaintFilters();
});

function applyMaintFilters() {
    const query   = document.getElementById('maintSearch')?.value.trim().toLowerCase() ?? '';
    const checked = Array.from(document.querySelectorAll('.maint-status-filter:checked'))
        .map(cb => cb.value);

    document.querySelectorAll('.drivers-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;

        const truck   = cells[0]?.textContent.trim().toLowerCase() ?? '';
        const issue   = cells[1]?.textContent.trim().toLowerCase() ?? '';
        const notes   = cells[4]?.textContent.trim().toLowerCase() ?? '';

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