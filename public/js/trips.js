document.getElementById('addTripBtn').addEventListener('click', () => {
    document.getElementById('addTripModal').classList.add('show');
});

function closeTripModal() {
    document.getElementById('addTripModal').classList.remove('show');
}

document.getElementById('addTripModal').addEventListener('click', function (e) {
    if (e.target === this) closeTripModal();
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

        const matches = !query
            || truck.includes(query)
            || driver.includes(query)
            || destination.includes(query);

        // Respect active filter — only show if both search and filter pass
        if (!matches) {
            row.style.display = 'none';
        } else {
            // Re-check filter state before showing
            const badge = row.querySelector('.status-badge');
            const status = Array.from(badge?.classList ?? [])
                .find(c => c.startsWith('status-') && c !== 'status-badge')
                ?.replace('status-', '') ?? '';

            const checked = Array.from(document.querySelectorAll('.trip-status-filter:checked'))
                .map(cb => cb.value);

            row.style.display = checked.includes(status) ? '' : 'none';
        }
    });
});