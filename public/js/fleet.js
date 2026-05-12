// ── Search ──────────────────────────────────────────────
document.getElementById('truckSearch').addEventListener('input', applyFilters);

// ── Filter Panel toggle ──────────────────────────────────
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

// ── Checkboxes ───────────────────────────────────────────
document.querySelectorAll('.status-filter').forEach(cb => {
    cb.addEventListener('change', applyFilters);
});

function clearFilters() {
    document.querySelectorAll('.status-filter').forEach(cb => cb.checked = true);
    applyFilters();
}

// ── Core filter logic ────────────────────────────────────
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

// ── Add Fleet Modal ──────────────────────────────────────
document.getElementById('addFleetBtn').addEventListener('click', () => {
    document.getElementById('addFleetModal').classList.add('show');
});

function closeFleetModal() {
    document.getElementById('addFleetModal').classList.remove('show');
}

document.getElementById('addFleetModal').addEventListener('click', function (e) {
    if (e.target === this) closeFleetModal();
});