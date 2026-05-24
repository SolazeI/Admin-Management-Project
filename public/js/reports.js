// ── Driver View Panel ─────────────────────────────────
var _panelDriverId = null;

function openDriverPanel(driverId) {
    _panelDriverId = driverId;

    const modal   = document.getElementById('driverModal');
    const overlay = document.getElementById('driverModalOverlay');
    const loader  = document.getElementById('panelLoader');
    const content = document.getElementById('panelContent');

    modal.classList.add('modal-open');
    overlay.style.display = 'block';
    loader.style.display  = 'flex';
    content.style.display = 'none';
    document.body.style.overflow = 'hidden';

    fetch(window.driverInfoBase.replace('__ID__', driverId), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => {
        if (!r.ok) throw new Error('Server error ' + r.status);
        return r.json();
    })
    .then(data => {
        renderDriverPanel(data);
        loader.style.display  = 'none';
        content.style.display = 'block';
    })
    .catch(err => {
        console.error('Modal fetch error:', err);
        loader.innerHTML =
            '<span style="color:#ef4444; font-size:13px;">Failed to load driver info. Please try again.</span>';
    });
}

function renderDriverPanel(d) {
    document.getElementById('panelDriverName').textContent = d.full_name ?? '—';
    document.getElementById('panelRefNo').textContent      = d.ref_no ? `Ref: ${d.ref_no}` : '';

    const statusKey   = (d.status ?? 'inactive').toLowerCase().replace(/[\s-]/g, '');
    const statusBadge = `<span class="status-badge status-${statusKey}">${d.status ?? 'Inactive'}</span>`;

    document.getElementById('panelInfoCards').innerHTML = `
        <div class="panel-info-card">
            <div class="panel-info-label">Status</div>
            <div class="panel-info-value">${statusBadge}</div>
        </div>
        <div class="panel-info-card">
            <div class="panel-info-label">Assigned Truck</div>
            <div class="panel-info-value">${d.assigned_truck ?? '—'}</div>
        </div>
        <div class="panel-info-card">
            <div class="panel-info-label">License No.</div>
            <div class="panel-info-value">${d.license_number ?? '—'}</div>
        </div>
        <div class="panel-info-card">
            <div class="panel-info-label">License Expiry</div>
            <div class="panel-info-value">${d.license_expiry_date ?? '—'}</div>
        </div>
        <div class="panel-info-card">
            <div class="panel-info-label">Phone</div>
            <div class="panel-info-value">${d.phone_number ?? '—'}</div>
        </div>
        <div class="panel-info-card">
            <div class="panel-info-label">Emergency Contact</div>
            <div class="panel-info-value">${d.emergency_contact ?? '—'}</div>
        </div>
    `;

    const trips          = d.trips ?? [];
    const totalTrips     = trips.length;
    const completedTrips = trips.filter(t => t.status === 'Completed').length;
    const totalRevenue   = trips
        .filter(t => t.status === 'Completed' && t.amount != null)
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);

    document.getElementById('panelStats').innerHTML = `
        <div class="panel-stat-card">
            <div class="panel-stat-label">Total Trips</div>
            <div class="panel-stat-value">${totalTrips}</div>
        </div>
        <div class="panel-stat-card">
            <div class="panel-stat-label">Completed</div>
            <div class="panel-stat-value">${completedTrips}</div>
        </div>
        <div class="panel-stat-card">
            <div class="panel-stat-label">Revenue</div>
            <div class="panel-stat-value" style="font-size:14px;">
                ₱${totalRevenue.toLocaleString('en-PH', { minimumFractionDigits: 0 })}
            </div>
        </div>
    `;

    if (!trips.length) {
        document.getElementById('panelTripTable').innerHTML =
            '<p style="font-size:12px; color:#94a3b8; text-align:center; padding:16px 0;">No trips recorded.</p>';
        return;
    }

    const rows = trips.map((t, i) => {
        const ts     = (t.status ?? '').toLowerCase().replace(/[\s-]/g, '');
        const amount = t.amount != null ? '₱' + Number(t.amount).toLocaleString('en-PH') : '—';
        const date   = t.created_at
            ? new Date(t.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
            : '—';
        return `
            <tr>
                <td>${i + 1}</td>
                <td>${t.trip_no ?? '—'}</td>
                <td>${t.truck?.truck_code ?? '—'}</td>
                <td>${date}</td>
                <td><span class="status-badge status-${ts}">${t.status ?? '—'}</span></td>
                <td style="text-align:right;">${amount}</td>
            </tr>`;
    }).join('');

    const revenueFormatted = '₱' + totalRevenue.toLocaleString('en-PH', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });

    document.getElementById('panelTripTable').innerHTML = `
        <table class="panel-trip-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Trip No.</th>
                    <th>Truck</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right; color:#64748b; font-weight:500;">
                        Total Revenue (Completed)
                    </td>
                    <td style="text-align:right;">${revenueFormatted}</td>
                </tr>
            </tfoot>
        </table>`;
}

function closeDriverPanel() {
    document.getElementById('driverModal').classList.remove('modal-open');
    document.getElementById('driverModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
    _panelDriverId = null;
}

function exportPanelDriver() {
    if (!_panelDriverId) return;
    window.open(`${window.exportDriverBase}/${_panelDriverId}`, '_blank');
}

// ── Server Error Banner ───────────────────────────────────
function showReportServerError(message, sub = '') {
    let banner = document.getElementById('reportServerError');
    if (!banner) return;
    document.getElementById('reportServerErrorText').textContent = message;
    const subEl = document.getElementById('reportServerErrorSub');
    if (subEl) subEl.textContent = sub;
    banner.style.display = 'flex';
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function hideReportServerError() {
    const banner = document.getElementById('reportServerError');
    if (banner) banner.style.display = 'none';
}

// ── Empty State ───────────────────────────────────────────
function showReportEmptyState(section, message) {
    // section: 'driver' | 'maintenance'
    const tableBody = section === 'driver'
        ? document.querySelector('#driverRecordsSection .drivers-table tbody')
        : document.querySelector('#maintenanceRecordsSection .drivers-table tbody');

    // Hide all real rows
    if (tableBody) {
        tableBody.querySelectorAll('tr:not(.report-empty-state-row)').forEach(r => r.style.display = 'none');
    }

    // Inject or update empty state row
    let emptyRow = tableBody?.querySelector('.report-empty-state-row');
    if (!emptyRow) {
        emptyRow = document.createElement('tr');
        emptyRow.className = 'report-empty-state-row';
        const colspan = section === 'driver' ? 6 : 6;
        emptyRow.innerHTML = `<td colspan="${colspan}" class="no-data" id="${section}EmptyStateMsg"></td>`;
        tableBody?.appendChild(emptyRow);
    }
    emptyRow.style.display = '';
    const msgEl = document.getElementById(`${section}EmptyStateMsg`);
    if (msgEl) msgEl.textContent = message;
}
function hideReportEmptyState(section) {
    const tableBody = section === 'driver'
        ? document.querySelector('#driverRecordsSection .drivers-table tbody')
        : document.querySelector('#maintenanceRecordsSection .drivers-table tbody');
    if (!tableBody) return;

    const emptyRow = tableBody.querySelector('.report-empty-state-row');
    if (emptyRow) emptyRow.style.display = 'none';

    tableBody.querySelectorAll('tr:not(.report-empty-state-row)').forEach(r => r.style.display = '');
}

// ── HTTP Error Subtitle ───────────────────────────────────
function httpReportErrorSubtitle(status) {
    switch (status) {
        case 422: return 'The request contained invalid data.';
        case 500: return 'An unexpected server error occurred. Please try again later.';
        default:  return `Server responded with status ${status}.`;
    }
}

// ── Core filter logic ─────────────────────────────────────
let _reportSearchDebounce = null;

function scheduleReportFilter() {
    clearTimeout(_reportSearchDebounce);
    _reportSearchDebounce = setTimeout(applyReportFilters, 300);
}

async function applyReportFilters() {
    hideReportServerError();
    const tab   = window._reportTab || 'driver';
    const query = document.querySelector('.search-input')?.value.trim() ?? '';

    if (tab === 'driver') {
        await applyDriverFilters(query);
    } else {
        await applyMaintenanceFilters(query);
        updateMaintenanceExportLink();
    }
}

// ── Driver tab ────────────────────────────────────────────
async function applyDriverFilters(query) {
    const checked = Array.from(document.querySelectorAll('.driver-report-filter:checked'))
        .map(cb => cb.value);

    hideReportEmptyState('driver');

    if (checked.length === 0) {
        showReportEmptyState('driver', 'No statuses selected. Use the filter to choose at least one.');
        return;
    }

    // Status-only filter (no search query) — client-side, data already in DOM
    if (!query) {
        let anyVisible = false;
        document.querySelectorAll('#driverRecordsSection .drivers-table tbody tr:not(.report-empty-state-row)')
            .forEach(row => {
                const badge = row.querySelector('.status-badge');
                if (!badge) { row.style.display = ''; anyVisible = true; return; }
                const visible = checked.includes(badge.textContent.trim());
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });
        if (!anyVisible) {
            showReportEmptyState('driver', `No drivers found with status: ${checked.join(', ')}.`);
        }
        return;
    }

    // Search + status filter — hit the server
    try {
        const res  = await fetch(`/reports/driver/search?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const data = await res.json().catch(() => null);

        if (res.status === 404) {
            showReportEmptyState('driver', data?.message || `No drivers found matching "${query}".`);
            return;
        }
        if (res.status === 422) {
            showReportServerError(data?.message || 'Invalid search input.', httpReportErrorSubtitle(422));
            return;
        }
        if (!res.ok) {
            showReportServerError(data?.message || 'Search failed.', httpReportErrorSubtitle(res.status));
            return;
        }

        const resultIds = new Set((data.data ?? []).map(d => String(d.id)));
        let anyVisible  = false;

        document.querySelectorAll('#driverRecordsSection .drivers-table tbody tr:not(.report-empty-state-row)')
            .forEach(row => {
                const driverId  = String(row.dataset.driverId ?? '');
                const badge     = row.querySelector('.status-badge');
                const rowStatus = badge?.textContent.trim() ?? '';
                const visible   = resultIds.has(driverId) && checked.includes(rowStatus);
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });

        if (!anyVisible) {
            const statusLabel = checked.length < 2 ? checked.join(' / ') : null;
            showReportEmptyState('driver',
                statusLabel
                    ? `No "${statusLabel}" drivers match "${query}".`
                    : `No drivers found matching "${query}".`
            );
        }
    } catch (err) {
        console.error('Driver search error:', err);
        showReportServerError('Unable to connect to the server.', 'Please check your connection and try again.');
    }
}

// ── Maintenance tab ───────────────────────────────────────
async function applyMaintenanceFilters(query) {
    const checked = Array.from(document.querySelectorAll('.maint-report-filter:checked'))
        .map(cb => cb.value);

    hideReportEmptyState('maintenance');

    if (checked.length === 0) {
        showReportEmptyState('maintenance', 'No statuses selected. Use the filter to choose at least one.');
        return;
    }

    // Status-only — client-side
    if (!query) {
        const statusMap = { pending: 'Pending', inprogress: 'In-Progress', completed: 'Completed', cancelled: 'Cancelled' };
        let anyVisible  = false;
        document.querySelectorAll('#maintenanceRecordsSection .drivers-table tbody tr:not(.report-empty-state-row)')
            .forEach(row => {
                const badge = row.querySelector('.status-badge');
                if (!badge) { row.style.display = ''; anyVisible = true; return; }
                const rowKey = Array.from(badge.classList)
                    .find(c => c.startsWith('status-') && c !== 'status-badge')
                    ?.replace('status-', '') ?? '';
                const visible = checked.includes(rowKey);
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });
        if (!anyVisible) {
            showReportEmptyState('maintenance', 'No maintenance records match the selected filters.');
        }
        return;
    }

    // Search + status — hit the server
    try {
        const params = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
        const res    = await fetch(`/reports/maintenance/search?q=${encodeURIComponent(query)}&${params}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const data = await res.json().catch(() => null);

        if (res.status === 404) {
            showReportEmptyState('maintenance', data?.message || `No maintenance records match "${query}".`);
            return;
        }
        if (!res.ok) {
            showReportServerError(data?.message || 'Search failed.', httpReportErrorSubtitle(res.status));
            return;
        }

        const resultIds = new Set((data.data ?? []).map(r => String(r.id)));
        let anyVisible  = false;

        document.querySelectorAll('#maintenanceRecordsSection .drivers-table tbody tr:not(.report-empty-state-row)')
            .forEach(row => {
                const recordId = String(row.dataset.recordId ?? '');
                const badge    = row.querySelector('.status-badge');
                const rowKey   = Array.from(badge?.classList ?? [])
                    .find(c => c.startsWith('status-') && c !== 'status-badge')
                    ?.replace('status-', '') ?? '';
                const visible  = resultIds.has(recordId) && checked.includes(rowKey);
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });

        if (!anyVisible) {
            showReportEmptyState('maintenance', `No maintenance records match "${query}".`);
        }
    } catch (err) {
        console.error('Maintenance search error:', err);
        showReportServerError('Unable to connect to the server.', 'Please check your connection and try again.');
    }
}

function clearReportFilters() {
    document.querySelectorAll('.driver-report-filter, .maint-report-filter').forEach(cb => cb.checked = true);
    document.querySelector('.search-input').value = '';
    applyReportFilters();
    updateMaintenanceExportLink();
}

// ── Maintenance Export (filter-aware) ────────────────
function updateMaintenanceExportLink() {
    const exportBtn = document.getElementById('maintenanceExportBtn');
    if (!exportBtn) return;
    const checked = Array.from(document.querySelectorAll('.maint-report-filter:checked'))
        .map(cb => cb.value);
    const params = checked.map(s => `statuses[]=${encodeURIComponent(s)}`).join('&');
    exportBtn.href = `${window.exportMaintenanceBase}?${params}`;
}

// ── Tab switching ────────────────────────────────────
function setTab(tab) {
    window._reportTab = tab;
    var isDriver = (tab === 'driver');
    document.getElementById('driverRecordsSection').style.display     = isDriver ? '' : 'none';
    document.getElementById('maintenanceRecordsSection').style.display = isDriver ? 'none' : '';
    document.getElementById('tabDriverBtn').className                  = isDriver ? 'btn btn-primary' : 'btn btn-secondary';
    document.getElementById('tabMaintenanceBtn').className             = isDriver ? 'btn btn-secondary' : 'btn btn-primary';
    document.getElementById('driverFilterOptions').style.display       = isDriver ? '' : 'none';
    document.getElementById('maintFilterOptions').style.display        = isDriver ? 'none' : '';
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

// ── Search ────────────────────────────────────────────────
document.getElementById('reportSearch').addEventListener('input', scheduleReportFilter);

// ── Checkboxes ────────────────────────────────────────────
document.querySelectorAll('.driver-report-filter').forEach(cb => {
    cb.addEventListener('change', applyReportFilters);
});
document.querySelectorAll('.maint-report-filter').forEach(cb => {
    cb.addEventListener('change', () => {
        applyReportFilters();
        updateMaintenanceExportLink();
    });
});

// ── Init ─────────────────────────────────────────────
setTab('driver');