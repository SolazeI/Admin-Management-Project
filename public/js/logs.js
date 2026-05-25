// ── CSRF Token ────────────────────────────────────────────────────────────────
function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

// ── State ─────────────────────────────────────────────────────────────────────
let _searchDebounce = null;

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    const logSearch = document.getElementById('logSearch');
    if (logSearch) {
        logSearch.addEventListener('input', function () {
            clearTimeout(_searchDebounce);
            _searchDebounce = setTimeout(applyClientFilters, 180);
        });
    }

    const logFilterBtn   = document.getElementById('logFilterBtn');
    const logFilterPanel = document.getElementById('logFilterPanel');
    if (logFilterBtn && logFilterPanel) {
        logFilterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            logFilterPanel.style.display = logFilterPanel.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function (e) {
            if (!logFilterPanel.contains(e.target) && e.target !== logFilterBtn) {
                logFilterPanel.style.display = 'none';
            }
        });
    }

    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) closeModal(this.id);
        });
    });
});

// ── Client-side Row Filtering ─────────────────────────────────────────────────
function applyClientFilters() {
    const q = (document.getElementById('logSearch')?.value ?? '').trim().toLowerCase();
    document.querySelectorAll('.log-row').forEach(function (row) {
        const label   = row.dataset.label   || '';
        const notes   = row.dataset.notes   || '';
        const action  = row.dataset.action  || '';
        const subject = row.dataset.subject || '';
        const matchesSearch = !q
            || label.includes(q)
            || notes.includes(q)
            || action.replace(/_/g, ' ').includes(q)
            || subject.replace(/_/g, ' ').includes(q);
        row.style.display = matchesSearch ? '' : 'none';
    });
    updateEmptyState();
}

function updateEmptyState() {
    const tbody = document.getElementById('logsTableBody');
    if (!tbody) return;
    const visible  = tbody.querySelectorAll('.log-row:not([style*="display: none"])');
    const existing = tbody.querySelector('.no-data-client');
    if (visible.length === 0) {
        if (!existing) {
            const cols = tbody.closest('table')?.querySelectorAll('thead th').length || 7;
            const tr   = document.createElement('tr');
            tr.className = 'no-data-client';
            tr.innerHTML = `<td colspan="${cols}" class="no-data">No logs match your search.</td>`;
            tbody.appendChild(tr);
        }
    } else {
        existing?.remove();
    }
}

// ── Server-side Filter Apply ──────────────────────────────────────────────────
function applyLogFilters() {
    const subjects = Array.from(document.querySelectorAll('.log-subject-filter:checked')).map(cb => cb.value);
    const actions  = Array.from(document.querySelectorAll('.log-action-filter:checked')).map(cb => cb.value);
    const dateFrom = document.getElementById('filterDateFrom')?.value || '';
    const dateTo   = document.getElementById('filterDateTo')?.value   || '';
    const q        = document.getElementById('logSearch')?.value      || '';

    const params = new URLSearchParams();
    if (q)        params.set('q', q);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo)   params.set('date_to', dateTo);

    const allSubjects = ['driver','truck','trip_ticket','maintenance_record','report_compilation','admin','admin_settings'];
    const allActions  = ['created','updated','deleted','archived','restored','status_changed','compiled','login','login_failed','logout','password_changed'];

    if (subjects.length && subjects.length < allSubjects.length) {
        subjects.forEach(s => params.append('subject_type[]', s));
    }
    if (actions.length && actions.length < allActions.length) {
        actions.forEach(a => params.append('action[]', a));
    }
    window.location.href = '/logs?' + params.toString();
}

function clearLogFilters() {
    document.querySelectorAll('.log-subject-filter, .log-action-filter').forEach(cb => cb.checked = true);
    const dateFrom = document.getElementById('filterDateFrom');
    const dateTo   = document.getElementById('filterDateTo');
    if (dateFrom) dateFrom.value = '';
    if (dateTo)   dateTo.value   = '';
    window.location.href = '/logs';
}

// ── Modal Helpers ─────────────────────────────────────────────────────────────
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// ── Log Detail Modal ──────────────────────────────────────────────────────────
async function openLogDetail(id) {
    const body = document.getElementById('logDetailBody');
    body.innerHTML = `
        <div class="log-loading-state">
            <span class="material-symbols-outlined log-spin">sync</span>
            Loading…
        </div>`;
    openModal('logDetailModal');
    try {
        const res = await fetch(`/logs/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() }
        });
        const log = await res.json();
        body.innerHTML = buildDetailHtml(log);
    } catch (err) {
        body.innerHTML = `<p class="log-detail-error">Failed to load log details. Please try again.</p>`;
    }
}

function buildDetailHtml(log) {
    const fmt = v => (v === null || v === undefined)
        ? '<span style="color:#94a3b8; font-size:13px;">—</span>'
        : escHtml(String(v));

    // ── Meta grid ──────────────────────────────────────────────────────────────
    let html = `<div class="log-detail-meta">`;

    html += metaField('Module', ucwords(log.subject_type?.replace(/_/g, ' ') ?? '—'));
    html += metaField('Action', ucwords(log.action?.replace(/_/g, ' ') ?? '—'));

    const subjectVal = log.subject_label
        ? escHtml(log.subject_label) + (log.subject_id
            ? ` <span style="color:#94a3b8; font-size:11px; font-weight:400; margin-left:3px;">#${log.subject_id}</span>`
            : '')
        : '—';
    html += metaField('Subject', subjectVal, true);

    const dateVal = log.logged_at
        ? escHtml(new Date(log.logged_at).toLocaleString())
        : '—';
    html += metaField('Date & Time', dateVal, true);

    if (log.ip_address) {
        html += metaField('IP Address', log.ip_address, false, 'is-muted');
    }

    if (log.notes) {
        html += `
            <div class="log-detail-field log-detail-field--full">
                <p class="log-detail-field-label">Notes</p>
                <p class="log-detail-field-value is-muted">${escHtml(log.notes)}</p>
            </div>`;
    }

    html += `</div>`;

    // ── Diff table ─────────────────────────────────────────────────────────────
    const oldVals = typeof log.old_values === 'string' ? JSON.parse(log.old_values) : log.old_values;
    const newVals = typeof log.new_values === 'string' ? JSON.parse(log.new_values) : log.new_values;

    if (oldVals || newVals) {
        const keys = new Set([
            ...Object.keys(oldVals ?? {}),
            ...Object.keys(newVals ?? {}),
        ]);
        keys.delete('password');
        keys.delete('password_hash');
        keys.delete('value');

        if (keys.size > 0) {
            html += `
                <p class="log-detail-section-label">Changes</p>
                <div style="border-radius:8px; border:1px solid #e2e8f0; overflow:hidden; overflow-x:auto;">
                    <table style="width:100%; min-width:400px; border-collapse:collapse; font-size:13px; font-family:'Poppins',sans-serif;">
                        <thead>
                            <tr>
                                <th style="width:35%; text-align:left; padding:9px 14px; font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid #e2e8f0; background:#f8fafc;">Field</th>
                                <th style="width:32.5%; text-align:left; padding:9px 14px; font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid #e2e8f0; background:#f8fafc;">Before</th>
                                <th style="width:32.5%; text-align:left; padding:9px 14px; font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid #e2e8f0; background:#f8fafc;">After</th>
                            </tr>
                        </thead>
                        <tbody>`;

            for (const key of keys) {
                const before  = oldVals?.[key] ?? null;
                const after   = newVals?.[key] ?? null;
                const changed = JSON.stringify(before) !== JSON.stringify(after);
                html += `
                    <tr style="${changed ? 'background:#fefce8;' : ''}">
                        <td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; font-weight:600; color:#334155; font-size:13px; text-transform:capitalize; max-width:160px; word-break:break-word;">
                            ${escHtml(key.replace(/_/g, ' '))}
                        </td>
                        <td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; max-width:200px; word-break:break-word; overflow-wrap:break-word;">
                            ${before !== null
                                ? `<span style="font-size:13px; font-weight:500; color:#b91c1c; text-decoration:line-through; text-decoration-color:#f87171;">${fmt(before)}</span>`
                                : '<span style="color:#94a3b8; font-size:13px;">—</span>'}
                        </td>
                        <td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; max-width:200px; word-break:break-word; overflow-wrap:break-word;">
                            ${after !== null
                                ? `<span style="font-size:13px; font-weight:700; color:#15803d;">${fmt(after)}</span>`
                                : '<span style="color:#94a3b8; font-size:13px;">—</span>'}
                        </td>
                    </tr>`;
            }

            html += `</tbody></table></div>`;
        }
    }

    return html;
}

// Helper — single meta field cell
function metaField(label, valueHtml, rawHtml = false, extraClass = '') {
    const val = rawHtml ? valueHtml : escHtml(valueHtml);
    const cls = ['log-detail-field-value', extraClass].filter(Boolean).join(' ');
    return `
        <div class="log-detail-field">
            <p class="log-detail-field-label">${label}</p>
            <p class="${cls}">${val}</p>
        </div>`;
}

function ucwords(str) {
    return str.replace(/\b\w/g, c => c.toUpperCase());
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#39;');
}