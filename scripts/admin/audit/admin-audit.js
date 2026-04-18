// =============================================================
//  admin-audit.js
//  Drop in:  /1QCUPROJECT/scripts/admin/audit/admin-audit.js
// =============================================================

var AUDIT_API = '/1QCUPROJECT/backend/routes/audit_route.php';

var auditPage   = 1;
var auditLimit  = 25;
var auditTotal  = 0;
var auditCache  = [];   // current page rows for CSV export

// ── Helpers ─────────────────────────────────────────────────
function showToast(msg) {
  var el = document.getElementById('toast');
  if (!el) return;
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(function() { el.classList.remove('show'); }, 3000);
}
function openModal(id)  { var el = document.getElementById(id); if (el) el.classList.add('active');    }
function closeModal(id) { var el = document.getElementById(id); if (el) el.classList.remove('active'); }

// Action badge color map
var ACTION_COLORS = {
  'LOGIN':              'background:#dcfce7;color:#166534',
  'LOGOUT':             'background:#f3f4f6;color:#374151',
  'LOGIN_FAIL':         'background:#fee2e2;color:#b91c1c',
  'ASSET_ADD':          'background:#dbeafe;color:#1d4ed8',
  'ASSET_EDIT':         'background:#e0f2fe;color:#0369a1',
  'ASSET_DELETE':       'background:#fee2e2;color:#b91c1c',
  'BORROW':             'background:#fef3c7;color:#b45309',
  'RETURN':             'background:#d1fae5;color:#065f46',
  'MAINTENANCE_ADD':    'background:#ede9fe;color:#6d28d9',
  'MAINTENANCE_UPDATE': 'background:#f3e8ff;color:#7c3aed',
  'REPORT_GENERATED':   'background:#fce7f3;color:#9d174d',
  'SCHEDULE_CREATE':    'background:#e0f2fe;color:#0369a1',
  'SCHEDULE_TOGGLE':    'background:#fef9c3;color:#854d0e',
  'SCHEDULE_DELETE':    'background:#fee2e2;color:#b91c1c',
};

function actionBadge(action) {
  var style = ACTION_COLORS[action] || 'background:#f3f4f6;color:#374151';
  return '<span class="badge" style="' + style + ';font-size:10px;padding:2px 8px;border-radius:9px;white-space:nowrap;">'
       + action + '</span>';
}

// ── Load stats ───────────────────────────────────────────────
function loadAuditStats() {
  fetch(AUDIT_API + '?resource=audit_stats&_=' + Date.now())
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.status !== 'success') return;
      var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
      set('statToday',    d.data.today);
      set('statMonth',    d.data.month);
      set('statTotal',    d.data.total);
      set('statCritical', d.data.critical);
    })
    .catch(function() {});
}

// ── Load logs ────────────────────────────────────────────────
function loadAuditLogs(page) {
  auditPage = page || 1;

  var search   = (document.getElementById('auditSearch')   || {}).value || '';
  var module   = (document.getElementById('auditModule')   || {}).value || '';
  var action   = (document.getElementById('auditAction')   || {}).value || '';
  var dateFrom = (document.getElementById('auditDateFrom') || {}).value || '';
  var dateTo   = (document.getElementById('auditDateTo')   || {}).value || '';

  var url = AUDIT_API
    + '?resource=audit_logs'
    + '&page='      + auditPage
    + '&limit='     + auditLimit
    + '&search='    + encodeURIComponent(search)
    + '&module='    + encodeURIComponent(module)
    + '&action='    + encodeURIComponent(action)
    + '&date_from=' + encodeURIComponent(dateFrom)
    + '&date_to='   + encodeURIComponent(dateTo)
    + '&_='         + Date.now();

  var tbody = document.getElementById('auditTableBody');
  if (tbody) tbody.innerHTML = '<tr class="empty-row"><td colspan="9">Loading…</td></tr>';

  fetch(url)
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.status !== 'success') {
        showToast('⚠ Failed to load audit logs.');
        return;
      }
      auditCache = d.data.logs || [];
      auditTotal = d.data.total || 0;
      renderAuditTable(auditCache);
      renderPagination(d.data.total_pages || 1);
    })
    .catch(function() { showToast('⚠ Network error loading audit logs.'); });
}

// ── Render table ─────────────────────────────────────────────
function renderAuditTable(logs) {
  var tbody = document.getElementById('auditTableBody');
  if (!tbody) return;

  if (!logs.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No audit events found.</td></tr>';
    return;
  }

  var offset = (auditPage - 1) * auditLimit;

  tbody.innerHTML = logs.map(function(l, i) {
    var rowNum  = offset + i + 1;
    var ts      = l.CREATED_AT || l.created_at || '—';
    var module  = l.MODULE     || l.module     || '—';
    var action  = l.ACTION_TYPE|| l.action_type|| '—';
    var by      = l.PERFORMED_BY|| l.performed_by|| '—';
    var role    = l.USER_ROLE  || l.user_role  || '—';
    var desc    = l.DESCRIPTION|| l.description|| '—';
    var ref     = l.REFERENCE_ID|| l.reference_id|| '—';
    var ip      = l.IP_ADDRESS || l.ip_address || '—';

    // highlight critical rows
    var rowStyle = '';
    if (action === 'ASSET_DELETE' || action === 'LOGIN_FAIL') {
      rowStyle = 'background:#fff5f5;';
    }

    return '<tr style="' + rowStyle + '">'
      + '<td style="color:#9ca3af;font-size:11px;">' + rowNum + '</td>'
      + '<td style="white-space:nowrap;font-size:11px;">' + ts + '</td>'
      + '<td><span class="badge" style="background:#f3f4f6;color:#374151;">' + module + '</span></td>'
      + '<td>' + actionBadge(action) + '</td>'
      + '<td style="font-weight:600;">' + escHtml(by) + '</td>'
      + '<td><span class="badge" style="background:#e0f2fe;color:#0369a1;">' + escHtml(role) + '</span></td>'
      + '<td style="max-width:280px;word-break:break-word;">' + escHtml(desc) + '</td>'
      + '<td style="font-size:11px;color:#6b7280;">' + escHtml(ref) + '</td>'
      + '<td style="font-size:11px;color:#9ca3af;">' + escHtml(ip) + '</td>'
      + '</tr>';
  }).join('');
}

function escHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

// ── Pagination ───────────────────────────────────────────────
function renderPagination(totalPages) {
  var container = document.getElementById('auditPagination');
  if (!container) return;

  if (totalPages <= 1) { container.innerHTML = ''; return; }

  var html = '<div style="display:flex;align-items:center;gap:6px;margin-top:16px;flex-wrap:wrap;">';

  // Prev
  html += '<button class="view-btn" onclick="loadAuditLogs(' + Math.max(1, auditPage - 1) + ')"'
        + (auditPage <= 1 ? ' disabled style="opacity:.4;"' : '') + '>‹ Prev</button>';

  // Page numbers (show max 7 around current)
  var start = Math.max(1, auditPage - 3);
  var end   = Math.min(totalPages, auditPage + 3);

  if (start > 1) html += '<span style="color:#9ca3af;">…</span>';
  for (var p = start; p <= end; p++) {
    var active = p === auditPage
      ? 'background:#1a1a2e;color:#fff;border-color:#1a1a2e;'
      : '';
    html += '<button class="view-btn" style="' + active + '" onclick="loadAuditLogs(' + p + ')">' + p + '</button>';
  }
  if (end < totalPages) html += '<span style="color:#9ca3af;">…</span>';

  // Next
  html += '<button class="view-btn" onclick="loadAuditLogs(' + Math.min(totalPages, auditPage + 1) + ')"'
        + (auditPage >= totalPages ? ' disabled style="opacity:.4;"' : '') + '>Next ›</button>';

  html += '<span style="font-size:12px;color:#6b7280;margin-left:8px;">Page ' + auditPage + ' of ' + totalPages
        + ' &nbsp;|&nbsp; ' + auditTotal + ' total events</span>';
  html += '</div>';

  container.innerHTML = html;
}

// ── CSV Export ───────────────────────────────────────────────
function exportAuditCsv() {
  if (!auditCache.length) { showToast('⚠ No data to export.'); return; }

  var cols    = ['LOG_ID','CREATED_AT','MODULE','ACTION_TYPE','PERFORMED_BY','USER_ROLE','DESCRIPTION','REFERENCE_ID','IP_ADDRESS'];
  var headers = ['Log ID','Timestamp','Module','Action','Performed By','Role','Description','Reference','IP Address'];

  var csv = headers.join(',') + '\n';
  auditCache.forEach(function(r) {
    csv += cols.map(function(c) {
      var v = r[c] || r[c.toLowerCase()] || '';
      return '"' + String(v).replace(/"/g,'""') + '"';
    }).join(',') + '\n';
  });

  var blob = new Blob([csv], {type:'text/csv'});
  var url  = URL.createObjectURL(blob);
  var a    = document.createElement('a');
  a.href     = url;
  a.download = 'audit_log_' + new Date().toISOString().replace(/[:.]/g,'-') + '.csv';
  a.click();
  URL.revokeObjectURL(url);
  showToast('✓ CSV exported!');
}

// ── Clear logs ───────────────────────────────────────────────
function clearAuditLogs() {
  fetch(AUDIT_API + '?resource=clear_audit', { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.status === 'success') {
        showToast('✓ Audit logs cleared.');
        loadAuditStats();
        loadAuditLogs(1);
      } else {
        showToast('⚠ ' + (d.message || 'Failed to clear logs.'));
      }
    })
    .catch(function() { showToast('⚠ Network error.'); });
}

// ── DOMContentLoaded ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {

  // Modal outside click + ESC
  document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target===this) this.classList.remove('active'); });
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) { m.classList.remove('active'); });
    }
  });

  // Filter buttons
  var applyBtn = document.getElementById('applyAuditFilter');
  var resetBtn = document.getElementById('resetAuditFilter');

  if (applyBtn) applyBtn.addEventListener('click', function() { loadAuditLogs(1); });
  if (resetBtn) resetBtn.addEventListener('click', function() {
    ['auditSearch','auditDateFrom','auditDateTo'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.value = '';
    });
    ['auditModule','auditAction'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.selectedIndex = 0;
    });
    loadAuditLogs(1);
  });

  // Search on Enter
  var searchEl = document.getElementById('auditSearch');
  if (searchEl) searchEl.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') loadAuditLogs(1);
  });

  // Export
  var exportBtn = document.getElementById('exportAuditBtn');
  if (exportBtn) exportBtn.addEventListener('click', exportAuditCsv);

  // Clear
  var clearBtn = document.getElementById('clearAuditBtn');
  if (clearBtn) clearBtn.addEventListener('click', function() { openModal('clearAuditModal'); });

  var cancelClear  = document.getElementById('cancelClearBtn');
  var confirmClear = document.getElementById('confirmClearBtn');
  if (cancelClear)  cancelClear.addEventListener('click',  function() { closeModal('clearAuditModal'); });
  if (confirmClear) confirmClear.addEventListener('click', function() {
    closeModal('clearAuditModal');
    clearAuditLogs();
  });

  // Init
  loadAuditStats();
  loadAuditLogs(1);

  // Auto-refresh every 60 s
  setInterval(function() {
    loadAuditStats();
    loadAuditLogs(auditPage);
  }, 60000);
});
