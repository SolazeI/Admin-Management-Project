// ── Filter logic ─────────────────────────────────────
function applyReportFilters() {
    var currentTab = window._reportTab || 'driver';

    if (currentTab === 'driver') {
        const checked = Array.from(document.querySelectorAll('.driver-report-filter:checked'))
            .map(cb => cb.value);

        fetch('/drivers/filter-status?statuses[]=' + checked.join('&statuses[]='))
            .then(r => r.json())
            .then(drivers => {
                const tbody = document.querySelector('#driverRecordsSection .drivers-table tbody');
                tbody.innerHTML = '';

                if (!drivers.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="no-data">No driver records found.</td></tr>';
                    return;
                }

                drivers.forEach(driver => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${driver.full_name}</td>
                            <td>${driver.assigned_truck ?? '—'}</td>
                            <td>${driver.total_trips_count ?? 0}</td>
                            <td class="metric-red">₱0</td>
                            <td class="metric-blue">₱0</td>
                            <td>
                                <button class="action-btn">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                            </td>
                        </tr>`;
                });
            })
            .catch(err => console.error('Filter error:', err));

    } else {
        const checked = Array.from(document.querySelectorAll('.maint-report-filter:checked'))
            .map(cb => cb.value);

        document.querySelectorAll('#maintenanceRecordsSection .drivers-table tbody tr').forEach(row => {
            const badge = row.querySelector('.status-badge');
            if (!badge) { row.style.display = ''; return; }
            const status = Array.from(badge.classList)
                .find(c => c.startsWith('status-') && c !== 'status-badge')
                ?.replace('status-', '') ?? '';
            row.style.display = checked.includes(status) ? '' : 'none';
        });
    }
}

function clearReportFilters() {
    document.querySelectorAll('.driver-report-filter, .maint-report-filter').forEach(cb => cb.checked = true);
    applyReportFilters();
}

// ── Tab switching ────────────────────────────────────
function setTab(tab) {
    window._reportTab = tab;
    var isDriver = (tab === 'driver');

    document.getElementById('driverRecordsSection').style.display      = isDriver ? '' : 'none';
    document.getElementById('maintenanceRecordsSection').style.display  = isDriver ? 'none' : '';
    document.getElementById('tabDriverBtn').className   = isDriver ? 'btn btn-primary' : 'btn btn-secondary';
    document.getElementById('tabMaintenanceBtn').className = isDriver ? 'btn btn-secondary' : 'btn btn-primary';
    document.getElementById('driverFilterOptions').style.display = isDriver ? '' : 'none';
    document.getElementById('maintFilterOptions').style.display  = isDriver ? 'none' : '';

    clearReportFilters();
}

document.getElementById('tabDriverBtn').addEventListener('click', function () { setTab('driver'); });
document.getElementById('tabMaintenanceBtn').addEventListener('click', function () { setTab('maintenance'); });

// ── Filter panel toggle ──────────────────────────────
const filterBtn   = document.getElementById('reportFilterBtn');
const filterPanel = document.getElementById('reportFilterPanel');

filterBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    filterPanel.style.display = filterPanel.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
    if (!filterPanel.contains(e.target) && e.target !== filterBtn) {
        filterPanel.style.display = 'none';
    }
});

document.querySelectorAll('.driver-report-filter, .maint-report-filter').forEach(cb => {
    cb.addEventListener('change', applyReportFilters);
});

// ── Init ─────────────────────────────────────────────
setTab('driver');