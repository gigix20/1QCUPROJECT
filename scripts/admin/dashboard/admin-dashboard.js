// API ROUTES
var ASSETS_API = '/1QCUPROJECT/backend/routes/assets_route.php';
var BORROW_API = '/1QCUPROJECT/backend/routes/borrows_route.php';
var MAINT_API  = '/1QCUPROJECT/backend/routes/maintenance_route.php';

// DATA STORES
var dashAssets      = [];
var dashBorrows     = [];
var dashMaintenance = [];


// UTILITIES
function showToast(msg) {
  var toastEl = document.getElementById('toast');
  if (!toastEl) return;
  toastEl.textContent = msg;
  toastEl.classList.add('show');
  setTimeout(function() { toastEl.classList.remove('show'); }, 3000);
}

function openModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add('active');
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove('active');
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  var d = new Date(dateStr);
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}


// DEPT COLORS
var DEPT_COLORS = {
  'CICS':          '#1d4ed8',
  'COENG':         '#b45309',
  'COED':          '#15803d',
  'CBA':           '#0f766e',
  'CAS':           '#7c3aed',
  'CAUP':          '#be185d',
  'OSAS':          '#c2410c',
  'Admin Office':  '#374151',
  'Library':       '#0369a1',
  'IT Department': '#065f46',
};

function getDeptColor(dept) {
  return DEPT_COLORS[dept] || '#2d1b47';
}

function assetBadgeClass(status) {
  return {
    'Available':   'available',
    'In Use':      'in-use',
    'Maintenance': 'maintenance'
  }[status] || 'available';
}

function borrowBadgeClass(status) {
  return {
    'Pending':   'pending',
    'Borrowed':  'in-use',
    'Returned':  'available',
    'Overdue':   'overdue',
    'Cancelled': 'maintenance'
  }[status] || 'pending';
}

function maintBadgeClass(status) {
  return {
    'Pending':     'pending',
    'In Progress': 'in-use',
    'Completed':   'available',
    'Cancelled':   'maintenance'
  }[status] || 'pending';
}

// LOAD ALL DATA
function loadDashboard() {
  loadDashAssets();
  loadDashBorrows();
  loadDashMaintenance();
}

// LOAD ASSETS
function loadDashAssets() {
  fetch(ASSETS_API + '?resource=assets&action=getAll&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        dashAssets = (data.data || []).filter(function(a) {
          return a.IS_DELETED == 0;
        });
        renderDashAssetTable();
        updateDashStats();
      }
    })
    .catch(function() { showToast('⚠ Failed to load assets.'); });
}



// LOAD BORROWS
function loadDashBorrows() {
  fetch(BORROW_API + '?resource=borrows&action=getAll&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        dashBorrows = data.data || [];
        renderDashBorrowTable();
        updateDashStats();
      }
    })
    .catch(function() { showToast('⚠ Failed to load borrows.'); });
}



// LOAD MAINTENANCE
function loadDashMaintenance() {
  fetch(MAINT_API + '?resource=maintenance&action=getAll&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        dashMaintenance = data.data || [];
        renderDashMaintTable();
        updateDashStats();
      }
    })
    .catch(function() { showToast('⚠ Failed to load maintenance.'); });
}



// UPDATE STATS
function updateDashStats() {
  var total       = dashAssets.length;
  var available   = dashAssets.filter(function(a) { return a.STATUS === 'Available';   }).length;
  var inUse       = dashAssets.filter(function(a) { return a.STATUS === 'In Use';      }).length;
  var maintenance = dashAssets.filter(function(a) { return a.STATUS === 'Maintenance'; }).length;

  var pendingBorrows  = dashBorrows.filter(function(b) { return b.STATUS === 'Pending'; }).length;
  var activeBorrows   = dashBorrows.filter(function(b) { return b.STATUS === 'Borrowed';}).length;
  var overdueBorrows  = dashBorrows.filter(function(b) {
    if (b.STATUS === 'Overdue') return true;
    if (b.STATUS === 'Returned' && b.RETURN_DATE && b.DUE_DATE) {
      return b.RETURN_DATE > b.DUE_DATE;
    }
    return false;
  }).length;

  var pendingMaint    = dashMaintenance.filter(function(m) { return m.STATUS === 'Pending';     }).length;
  var inProgressMaint = dashMaintenance.filter(function(m) { return m.STATUS === 'In Progress'; }).length;

  var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
  set('dashStatTotal',          total);
  set('dashStatAvailable',      available);
  set('dashStatInUse',          inUse);
  set('dashStatMaintenance',    maintenance);
  set('dashStatPendingBorrows', pendingBorrows);
  set('dashStatActiveBorrows',  activeBorrows);
  set('dashStatOverdue',        overdueBorrows);
  set('dashStatPendingMaint',   pendingMaint);
  set('dashStatInProgressMaint',inProgressMaint);
}



// RENDER ASSET TABLE (latest 5)
function renderDashAssetTable() {
  var tbody = document.getElementById('dashAssetTableBody');
  if (!tbody) return;

  var latest = dashAssets.slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="5">No assets to display.</td></tr>';
    return;
  }

  tbody.innerHTML = latest.map(function(a) {
    var deptColor = getDeptColor(a.DEPARTMENT_NAME);
    return '<tr>'                                                                                    +
      '<td><strong>' + (a.ASSET_ID        || '—') + '</strong></td>'                               +
      '<td>'         + (a.DESCRIPTION     || '—') + '</td>'                                        +
      '<td><span style="color:' + deptColor + ';font-weight:600;">'
                     + (a.DEPARTMENT_NAME || '—') + '</span></td>'                                 +
      '<td><span class="badge ' + assetBadgeClass(a.STATUS) + '">' + a.STATUS + '</span></td>'     +
      '<td>'         + formatDate(a.CREATED_AT)   + '</td>'                                        +
    '</tr>';
  }).join('');
}

// RENDER BORROW TABLE (latest 5)
function renderDashBorrowTable() {
  var tbody = document.getElementById('dashBorrowTableBody');
  if (!tbody) return;

  var latest = dashBorrows.slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="5">No borrow requests to display.</td></tr>';
    return;
  }

  tbody.innerHTML = latest.map(function(b) {
    var borrower = [b.FIRST_NAME, b.MIDDLE_NAME, b.LAST_NAME, b.SUFFIX]
                    .filter(Boolean).join(' ') || '—';
    return '<tr>'                                                                                    +
      '<td><strong>' + (b.BORROW_ID      || '—') + '</strong></td>'                               +
      '<td>'         + borrower                   + '</td>'                                        +
      '<td>'         + (b.ASSET_ID       || '—') + '</td>'                                        +
      '<td>'         + formatDate(b.BORROW_DATE)  + '</td>'                                        +
      '<td><span class="badge ' + borrowBadgeClass(b.STATUS) + '">' + b.STATUS + '</span></td>'   +
    '</tr>';
  }).join('');
}

// RENDER MAINTENANCE TABLE (latest 5)
function renderDashMaintTable() {
  var tbody = document.getElementById('dashMaintTableBody');
  if (!tbody) return;

  var latest = dashMaintenance.slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="5">No maintenance records to display.</td></tr>';
    return;
  }

  tbody.innerHTML = latest.map(function(m) {
    return '<tr>'                                                                                    +
      '<td><strong>' + (m.MAINTENANCE_ID    || '—') + '</strong></td>'                            +
      '<td>'         + (m.ASSET_ID          || '—') + '</td>'                                     +
      '<td>'         + (m.MAINTENANCE_TYPE  || '—') + '</td>'                                     +
      '<td>'         + formatDate(m.SCHEDULED_DATE) + '</td>'                                      +
      '<td><span class="badge ' + maintBadgeClass(m.STATUS) + '">' + m.STATUS + '</span></td>'    +
    '</tr>';
  }).join('');
}