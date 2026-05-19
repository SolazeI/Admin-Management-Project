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
                    const status    = driver.status ?? 'Inactive';
                    const statusKey = status.toLowerCase().replace(/[\s-]/g, '');
                    const truck     = driver.assigned_truck ?? '—';
                    const revenue   = (driver.total_revenue ?? 0) > 0
                        ? '₱' + Number(driver.total_revenue).toLocaleString('en-PH')
                        : '—';
                    tbody.innerHTML += `
                        <tr>
                            <td>${driver.full_name}</td>
                            <td><span class="status-badge status-${statusKey}">${status}</span></td>
                            <td>${truck}</td>
                            <td>${driver.total_trips_count ?? 0}</td>
                            <td>${revenue}</td>
                            <td>
                                <button type="button"
                                    onclick="window.open('${window.exportDriverBase}/${driver.id}', '_blank')"
                                    style="font-size:12px; gap:4px; display:inline-flex; align-items:center; padding:6px 12px;
                                        background:#0f1a2e; color:#fff; border-radius:6px; border:none;
                                        font-family:'Poppins',sans-serif; font-weight:500; cursor:pointer;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">picture_as_pdf</span>
                                    Export Report
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
        updateMaintenanceExportLink();
    }
}

function clearReportFilters() {
    document.querySelectorAll('.driver-report-filter, .maint-report-filter').forEach(cb => cb.checked = true);
    applyReportFilters();
    updateMaintenanceExportLink();
}

// ── Maintenance Export (filter-aware) ────────────────
function updateMaintenanceExportLink() {
    const checked = Array.from(document.querySelectorAll('.maint-report-filter:checked'))
        .map(cb => cb.value);
    const exportBtn = document.getElementById('maintenanceExportBtn');
    if (!exportBtn) return;
    const params = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
    exportBtn.href = `${window.exportMaintenanceBase}?${params}`;
}

// ── Tab switching ────────────────────────────────────
function setTab(tab) {
    window._reportTab = tab;
    var isDriver = (tab === 'driver');
    document.getElementById('driverRecordsSection').style.display      = isDriver ? '' : 'none';
    document.getElementById('maintenanceRecordsSection').style.display  = isDriver ? 'none' : '';
    document.getElementById('tabDriverBtn').className     = isDriver ? 'btn btn-primary' : 'btn btn-secondary';
    document.getElementById('tabMaintenanceBtn').className  = isDriver ? 'btn btn-secondary' : 'btn btn-primary';
    document.getElementById('driverFilterOptions').style.display = isDriver ? '' : 'none';
    document.getElementById('maintFilterOptions').style.display  = isDriver ? 'none' : '';
    clearReportFilters();
}

document.getElementById('tabDriverBtn').addEventListener('click',      function () { setTab('driver'); });
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

document.querySelectorAll('.driver-report-filter').forEach(cb => {
    cb.addEventListener('change', applyReportFilters);
});

document.querySelectorAll('.maint-report-filter').forEach(cb => {
    cb.addEventListener('change', () => {
        applyReportFilters();
        updateMaintenanceExportLink();
    });
});

// ── PDF Export ────────────────────────────────────────
function buildPrintHTML(title, rows) {
    const tableRows = rows.map(([label, value]) => `
        <tr>
            <td class="label">${label}</td>
            <td class="value">${value}</td>
        </tr>`).join('');
    return `
        <div class="print-report">
            <div class="print-header">
                <div class="print-logo">Fleet Management System</div>
                <div class="print-meta">
                    <span>Generated: ${new Date().toLocaleDateString('en-PH', {
                        year: 'numeric', month: 'long', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    })}</span>
                </div>
            </div>
            <h2 class="print-title">${title}</h2>
            <table class="print-table">
                <tbody>${tableRows}</tbody>
            </table>
            <div class="print-footer">This report is system-generated and does not require a signature.</div>
        </div>`;
}

function triggerPrint(html) {
    const container = document.getElementById('printContainer');
    container.innerHTML = html;
    container.style.display = 'block';
    window.print();
    window.addEventListener('afterprint', function onAfterPrint() {
        container.style.display = 'none';
        container.innerHTML = '';
        window.removeEventListener('afterprint', onAfterPrint);
    });
}

// Driver export
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.export-driver-btn');
    if (!btn) return;
    const rows = [
        ['Driver Name',  btn.dataset.name],
        ['Status',       btn.dataset.status],
        ['Latest Truck', btn.dataset.truck],
        ['Total Trips',  btn.dataset.trips],
        ['Revenue',      btn.dataset.revenue],
    ];
    triggerPrint(buildPrintHTML('Driver Trip Record', rows));
});

// ── Maintenance Export (filter-aware, JS print) ───────
function updateMaintenanceExportLink() {
    const exportBtn = document.getElementById('maintenanceExportBtn');
    if (!exportBtn) return;
    const checked = Array.from(document.querySelectorAll('.maint-report-filter:checked'))
        .map(cb => cb.value);
    const params = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
    exportBtn.href = `${window.exportMaintenanceBase}?${params}`;
}

// ── Init ─────────────────────────────────────────────
setTab('driver');