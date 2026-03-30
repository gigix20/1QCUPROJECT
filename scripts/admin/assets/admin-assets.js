// API ROUTE
var API = '/1QCUPROJECT/backend/routes/assets_route.php';

// ASSET STORE
var assets           = [];
var deletionRequests = [];


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

function getActiveTab() {
  var active = document.querySelector('.filter-tab.active');
  return active ? active.dataset.status || active.textContent.trim() : 'ALL';
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

function getLiablePerson(selectId) {
  var sel = document.getElementById(selectId);
  if (!sel) return '—';
  var opt = sel.options[sel.selectedIndex];
  return opt ? (opt.getAttribute('data-liable') || '—') : '—';
}

function updateLiableDropdown(deptSelectId, liableSelectId) {
  var deptSel   = document.getElementById(deptSelectId);
  var liableSel = document.getElementById(liableSelectId);
  if (!deptSel || !liableSel) return;

  var opt    = deptSel.options[deptSel.selectedIndex];
  var liable = opt ? opt.getAttribute('data-liable') : null;

  liableSel.innerHTML = '';

  if (!liable || liable === '—' || !deptSel.value) {
    liableSel.innerHTML = '<option value="">-- Select Department first --</option>';
    liableSel.disabled  = true;
    return;
  }

  liableSel.innerHTML = '<option value="' + liable + '">' + liable + '</option>';
  liableSel.disabled  = false;
}

function badgeClass(status) {
  return {
    'Available':   'available',
    'In Use':      'in-use',
    'Maintenance': 'maintenance'
  }[status] || 'available';
}


// ── RENDER MAIN ASSETS TABLE ──────────────────────────────────────────────────
function renderAssetsTable(filter, tabFilter) {
  var assetsTableBody = document.getElementById('assetsTableBody');
  if (!assetsTableBody) return;
  filter    = (filter    || '').toLowerCase();
  tabFilter =  tabFilter || 'ALL';

  // Exclude pending-deletion assets from the main table — they appear in the Pending Deletions section
  var filtered = assets.filter(function(a) {
    if (a.IS_DELETED == 1) return false;

    var match =
      (a.ASSET_ID        || '').toLowerCase().includes(filter) ||
      (a.DESCRIPTION     || '').toLowerCase().includes(filter) ||
      (a.SERIAL_NUMBER   || '').toLowerCase().includes(filter) ||
      (a.ITEM_TYPE_NAME  || '').toLowerCase().includes(filter) ||
      (a.CATEGORY_NAME   || '').toLowerCase().includes(filter) ||
      (a.DEPARTMENT_NAME || '').toLowerCase().includes(filter) ||
      (a.LOCATION        || '').toLowerCase().includes(filter) ||
      (a.QR_CODE         || '').toLowerCase().includes(filter) ||
      (a.LAST_NAME       || '').toLowerCase().includes(filter) ||
      (a.FIRST_NAME      || '').toLowerCase().includes(filter);

    var tab = true;
    if (tabFilter === 'Available')   tab = a.STATUS       === 'Available';
    if (tabFilter === 'In Use')      tab = a.STATUS       === 'In Use';
    if (tabFilter === 'Maintenance') tab = a.STATUS       === 'Maintenance';
    if (tabFilter === 'Certified')   tab = a.IS_CERTIFIED ==  1;

    return match && tab;
  });

  if (!filtered.length) {
    assetsTableBody.innerHTML = '<tr class="empty-row"><td colspan="12">No assets to display.</td></tr>';
    return;
  }

  assetsTableBody.innerHTML = filtered.map(function(a) {
    var cert = a.IS_CERTIFIED == 1
      ? '<span class="badge" style="background:#fef9c3;color:#854d0e;">Certified</span>'
      : '<span style="color:#bbb;font-size:12px;">—</span>';

    var statusCell = '<span class="badge ' + badgeClass(a.STATUS) + '">' + a.STATUS + '</span>';

    var liable = [a.FIRST_NAME, a.MIDDLE_NAME, a.LAST_NAME, a.SUFFIX]
                   .filter(Boolean).join(' ') || '—';

    return '<tr>'
      + '<td><strong>' + (a.ASSET_ID        || '—') + '</strong></td>'
      + '<td>'         + qrTagHTML(a)               + '</td>'
      + '<td>'         + (a.DESCRIPTION     || '—') + '</td>'
      + '<td>'         + (a.SERIAL_NUMBER   || '—') + '</td>'
      + '<td>'         + (a.ITEM_TYPE_NAME  || '—') + '</td>'
      + '<td>'         + (a.CATEGORY_NAME   || '—') + '</td>'
      + '<td>'         + (a.DEPARTMENT_NAME || '—') + '</td>'
      + '<td>'         + liable                     + '</td>'
      + '<td>'         + (a.LOCATION        || '—') + '</td>'
      + '<td>'         + statusCell                 + '</td>'
      + '<td>'         + cert                       + '</td>'
      + '<td>'         + actionBtns(a)              + '</td>'
    + '</tr>';
  }).join('');
}


// ── ADMIN ACTION BUTTONS ──────────────────────────────────────────────────────
function actionBtns(a) {
  var id = a.ASSET_ID;
  return '<button class="view-btn" onclick="viewQRById(\'' + id + '\')">View QR</button> '
       + '<button class="edit-btn" onclick="editRow(\''   + id + '\')">Edit</button> '
       + '<button class="del-btn"  onclick="adminDeleteRow(\'' + id + '\')">Delete</button>';
}


// ── RENDER PENDING DELETIONS TABLE ────────────────────────────────────────────
function renderPendingDeletionsTable(requests) {
  var tbody = document.getElementById('pendingDeletionsBody');
  if (!tbody) return;

  var badge = document.getElementById('pendingDeletionsBadge');
  if (badge) {
    badge.textContent    = requests.length || '';
    badge.style.display  = requests.length ? 'inline-flex' : 'none';
  }

  if (!requests.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No pending deletion requests.</td></tr>';
    return;
  }

  tbody.innerHTML = requests.map(function(r) {
    var id          = r.ASSET_ID          || r.asset_id          || '—';
    var desc        = r.DESCRIPTION       || r.description       || '—';
    var dept        = r.DEPARTMENT_NAME   || r.department_name   || '—';
    var requestedBy = r.REQUESTED_BY      || r.requested_by      || '—';
    var reason      = r.REASON            || r.reason            || '—';
    var dateStr     = r.CREATED_AT        || r.created_at        || null;

    return '<tr>'
      + '<td><strong>' + id          + '</strong></td>'
      + '<td>'         + desc        + '</td>'
      + '<td>'         + dept        + '</td>'
      + '<td>'         + requestedBy + '</td>'
      + '<td style="max-width:200px;white-space:normal;font-size:12px;color:#555;">'
      +   reason
      + '</td>'
      + '<td>'         + formatDate(dateStr) + '</td>'
      + '<td>'
      +   '<button class="edit-btn" style="border-color:#16a34a;color:#16a34a;" '
      +     'onclick="approveDeletion(\'' + id + '\')" title="Approve — permanently deletes this asset">✓ Approve</button> '
      +   '<button class="del-btn" '
      +     'onclick="rejectDeletion(\'' + id + '\')" title="Reject — restores the asset to normal">✕ Reject</button>'
      + '</td>'
    + '</tr>';
  }).join('');
}


// ── LOAD DROPDOWNS ────────────────────────────────────────────────────────────
function loadDropdowns() {
  fetch(API + '?resource=departments&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var addSel  = document.getElementById('assetsDepartment');
      var editSel = document.getElementById('editDepartment');
      data.data.forEach(function(d) {
        var liable = [d.FIRST_NAME, d.MIDDLE_NAME, d.LAST_NAME, d.SUFFIX]
                      .filter(Boolean).join(' ');
        var opt = '<option value="' + d.DEPARTMENT_ID + '" data-liable="' + (liable || '—') + '">'
                + d.DEPARTMENT_NAME + '</option>';
        if (addSel)  addSel.innerHTML  += opt;
        if (editSel) editSel.innerHTML += opt;
      });
    })
    .catch(function() { showToast('⚠ Failed to load departments.'); });

  fetch(API + '?resource=categories')
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var addSel  = document.getElementById('assetsCategory');
      var editSel = document.getElementById('editCategory');
      data.data.forEach(function(c) {
        var opt = '<option value="' + c.CATEGORY_ID + '">' + c.CATEGORY_NAME + '</option>';
        if (addSel)  addSel.innerHTML  += opt;
        if (editSel) editSel.innerHTML += opt;
      });
    })
    .catch(function() { showToast('⚠ Failed to load categories.'); });

  fetch(API + '?resource=item_types')
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var addSel  = document.getElementById('assetsItemType');
      var editSel = document.getElementById('editItemType');
      data.data.forEach(function(t) {
        var opt = '<option value="' + t.ITEM_TYPE_ID + '">' + t.ITEM_TYPE_NAME + '</option>';
        if (addSel)  addSel.innerHTML  += opt;
        if (editSel) editSel.innerHTML += opt;
      });
    })
    .catch(function() { showToast('⚠ Failed to load item types.'); });
}


// ── LOAD ASSETS ───────────────────────────────────────────────────────────────
function loadAssets() {
  fetch(API + '?resource=assets&action=getAll')
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        assets = data.data || [];
        renderAssetsTable('', getActiveTab());
      } else {
        showToast('⚠ Failed to load assets.');
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// ── LOAD PENDING DELETION REQUESTS ────────────────────────────────────────────
function loadDeletionRequests() {
  fetch(API + '?resource=assets&action=getDeletionRequests&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        deletionRequests = data.data || [];
        renderPendingDeletionsTable(deletionRequests);
      }
    })
    .catch(function() { /* non-fatal */ });
}


// CLEAR FORM
function assetsClearForm() {
  ['assetsDescription', 'assetsSerialNumber', 'assetsLocation'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  ['assetsDepartment', 'assetsStatus', 'assetsCategory', 'assetsItemType'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.selectedIndex = 0;
  });
  var qty = document.getElementById('assetsQuantity');  if (qty) qty.value    = 1;
  var qr  = document.getElementById('assetsQrCode');    if (qr)  qr.value    = '';
  var aid = document.getElementById('assetsAssetId');   if (aid) aid.value   = '';
  var cer = document.getElementById('assetsCertified'); if (cer) cer.checked = false;

  var liableSel = document.getElementById('assetsLiablePerson');
  if (liableSel) {
    liableSel.innerHTML = '<option value="">-- Select Department first --</option>';
    liableSel.disabled  = true;
  }
}