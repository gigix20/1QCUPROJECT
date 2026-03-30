// API ROUTE
var API = '/1QCUPROJECT/backend/routes/assets_route.php';

// ASSET STORE
var assets = [];


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

function badgeClass(status) {
  return {
    'Available':   'available',
    'In Use':      'in-use',
    'Maintenance': 'maintenance'
  }[status] || 'available';
}


// ── FETCH CUSTODIANS BY DEPARTMENT ───────────────────────────────────────────
function fetchCustodiansByDept(dept_id, liableSelectId, selectedCustodianId) {
  var liableSel = document.getElementById(liableSelectId);
  if (!liableSel) return;

  if (!dept_id) {
    liableSel.innerHTML = '<option value="">-- Select Department first --</option>';
    liableSel.disabled  = true;
    return;
  }

  liableSel.innerHTML = '<option value="">Loading...</option>';
  liableSel.disabled  = true;

  fetch(API + '?resource=custodians&department_id=' + encodeURIComponent(dept_id) + '&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success' || !data.data.length) {
        liableSel.innerHTML = '<option value="">No custodians found</option>';
        liableSel.disabled  = true;
        return;
      }

      liableSel.innerHTML = '<option value="">-- Select Liable Person --</option>';
      data.data.forEach(function(c) {
        var name = [c.FIRST_NAME, c.MIDDLE_NAME, c.LAST_NAME, c.SUFFIX]
                    .filter(Boolean).join(' ');
        var opt  = document.createElement('option');
        opt.value       = c.CUSTODIAN_ID;
        opt.textContent = name;
        if (selectedCustodianId && c.CUSTODIAN_ID == selectedCustodianId) {
          opt.selected = true;
        }
        liableSel.appendChild(opt);
      });

      liableSel.disabled = false;
    })
    .catch(function() {
      liableSel.innerHTML = '<option value="">⚠ Failed to load</option>';
      liableSel.disabled  = true;
    });
}


// RENDER TABLE
function renderAssetsTable(filter, tabFilter) {
  var assetsTableBody = document.getElementById('assetsTableBody');
  if (!assetsTableBody) return;
  filter    = (filter    || '').toLowerCase();
  tabFilter =  tabFilter || 'ALL';

  var filtered = assets.filter(function(a) {
    var liable = [a.CUSTODIAN_FIRST, a.CUSTODIAN_MIDDLE, a.CUSTODIAN_LAST, a.CUSTODIAN_SUFFIX]
                   .filter(Boolean).join(' ');

    var match =
      (a.ASSET_ID        || '').toLowerCase().includes(filter) ||
      (a.DESCRIPTION     || '').toLowerCase().includes(filter) ||
      (a.SERIAL_NUMBER   || '').toLowerCase().includes(filter) ||
      (a.ITEM_TYPE_NAME  || '').toLowerCase().includes(filter) ||
      (a.CATEGORY_NAME   || '').toLowerCase().includes(filter) ||
      (a.DEPARTMENT_NAME || '').toLowerCase().includes(filter) ||
      (a.LOCATION        || '').toLowerCase().includes(filter) ||
      (a.QR_CODE         || '').toLowerCase().includes(filter) ||
      liable.toLowerCase().includes(filter);

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

    var statusCell = a.IS_DELETED == 1
      ? '<span class="badge" style="background:#fee2e2;color:#dc2626;">Pending Deletion</span>'
      : '<span class="badge ' + badgeClass(a.STATUS) + '">' + a.STATUS + '</span>';

    var liable = [a.CUSTODIAN_FIRST, a.CUSTODIAN_MIDDLE, a.CUSTODIAN_LAST, a.CUSTODIAN_SUFFIX]
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

// ACTION BUTTONS
function actionBtns(a) {
  var id      = a.ASSET_ID;
  var pending = a.IS_DELETED == 1;
  if (pending) {
    return '<span style="font-size:11px;color:#dc2626;font-weight:600;' +
           'background:#fee2e2;padding:3px 8px;border-radius:4px;">' +
           'Awaiting Admin Approval</span>';
  }

  return '<button class="view-btn"  onclick="viewQRById(\'' + id + '\')">View QR</button> ' +
         '<button class="edit-btn"  onclick="editRow(\''   + id + '\')">Edit</button> '    +
         '<button class="del-btn"   onclick="deleteRow(\'' + id + '\')">Delete</button>';
}


// LOAD DROPDOWNS
function loadDropdowns() {
  fetch(API + '?resource=departments&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var addSel  = document.getElementById('assetsDepartment');
      var editSel = document.getElementById('editDepartment');
      data.data.forEach(function(d) {
        var opt = '<option value="' + d.DEPARTMENT_ID + '">' + d.DEPARTMENT_NAME + '</option>';
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

// LOAD ASSETS
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

// CLEAR FORM
function assetsClearForm() {
  ['assetsDescription', 'assetsSerialNumber', 'assetsLocation'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  ['assetsDepartment', 'assetsStatus',
   'assetsCategory',   'assetsItemType'].forEach(function(id) {
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