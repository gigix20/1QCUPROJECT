
// API ROUTE
var API = '/1QCUPROJECT/backend/routes/assets_route.php';


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


// DEPT COLOR MAP
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


// QR GENERATION
function generateQRValue() {
  var now  = new Date();
  var y    = now.getFullYear();
  var m    = String(now.getMonth() + 1).padStart(2, '0');
  var d    = String(now.getDate()).padStart(2, '0');
  var rand = Math.random().toString(36).substring(2, 6).toUpperCase();
  return 'ONEQCU-' + y + m + d + '-' + rand;
}

function qrTagHTML(a) {
  var c  = getDeptColor(a.DEPARTMENT_NAME || a.department);
  var qr = a.QR_CODE || a.qrCode;
  return '<span class="qr-tag" title="Click to view QR" ' +
    'onclick="showQRModal(' + JSON.stringify(a).replace(/'/g, "\\'") + ')" ' +
    'style="border-left:3px solid ' + c + ';color:' + c + ';background:' + c + '12;">' +
    qr + '</span>';
}

function actionBtns(id) {
  return '<button class="view-btn"  onclick="viewQRById(\'' + id + '\')">View QR</button> ' +
         '<button class="edit-btn"  onclick="editRow(\''   + id + '\')">Edit</button> '    +
         '<button class="del-btn"   onclick="deleteRow(\'' + id + '\')">Delete</button>';
}


// QR MODAL
function showQRModal(asset) {
  var modal = document.getElementById('qrViewModal');
  if (!modal) return;

  var dept = asset.DEPARTMENT_NAME || asset.department;
  var qr   = asset.QR_CODE         || asset.qrCode;
  var id   = asset.ASSET_ID        || asset.assetId;
  var desc = asset.DESCRIPTION     || asset.description;

  var set = function(elId, val) {
    var el = document.getElementById(elId);
    if (el) el.textContent = val;
  };

  set('qrModalAssetId',  id);
  set('qrModalDesc',     desc);
  set('qrModalCodeText', qr);

  var deptColor = getDeptColor(dept);
  var divider   = document.querySelector('#qrViewModal .modal-divider');
  if (divider) divider.style.background = 'linear-gradient(to right, ' + deptColor + ', transparent)';

  var deptBadge = document.getElementById('qrModalDept');
  if (deptBadge) {
    deptBadge.textContent       = dept;
    deptBadge.style.background  = deptColor + '18';
    deptBadge.style.color       = deptColor;
    deptBadge.style.borderColor = deptColor + '44';
  }

  var container = document.getElementById('qrCanvas');
  if (container && typeof QRCode !== 'undefined') {
    container.innerHTML = '';
    new QRCode(container, {
      text:         qr,
      width:        200,
      height:       200,
      colorDark:    deptColor,
      colorLight:   '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  }

  openModal('qrViewModal');
}

function downloadQR() {
  var container = document.getElementById('qrCanvas');
  if (!container) return;
  var codeLabel = document.getElementById('qrModalCodeText');
  var filename  = (codeLabel ? codeLabel.textContent : 'qrcode') + '.png';
  var canvas    = container.querySelector('canvas');
  var img       = container.querySelector('img');
  var a         = document.createElement('a');
  a.download    = filename;
  if (canvas)   { a.href = canvas.toDataURL('image/png'); a.click(); }
  else if (img) { a.href = img.src; a.click(); }
}


// ASSET STORE
var assets = [];


// RENDER TABLE
function renderAssetsTable(filter, tabFilter) {
  var assetsTableBody = document.getElementById('assetsTableBody');
  if (!assetsTableBody) return;
  filter    = (filter    || '').toLowerCase();
  tabFilter =  tabFilter || 'ALL';

  var filtered = assets.filter(function(a) {
    var match =
      (a.ASSET_ID        || '').toLowerCase().includes(filter) ||
      (a.DESCRIPTION     || '').toLowerCase().includes(filter) ||
      (a.SERIAL_NUMBER   || '').toLowerCase().includes(filter) ||
      (a.CATEGORY_NAME   || '').toLowerCase().includes(filter) ||
      (a.DEPARTMENT_NAME || '').toLowerCase().includes(filter) ||
      (a.LOCATION        || '').toLowerCase().includes(filter) ||
      (a.QR_CODE         || '').toLowerCase().includes(filter);

    var tab = true;
    if (tabFilter === 'Available')   tab = a.STATUS       === 'Available';
    if (tabFilter === 'In Use')      tab = a.STATUS       === 'In Use';
    if (tabFilter === 'Maintenance') tab = a.STATUS       === 'Maintenance';
    if (tabFilter === 'Certified')   tab = a.IS_CERTIFIED ==  1;

    return match && tab;
  });

  if (!filtered.length) {
    assetsTableBody.innerHTML = '<tr class="empty-row"><td colspan="10">No assets to display.</td></tr>';
    return;
  }

  assetsTableBody.innerHTML = filtered.map(function(a) {
    var cert = a.IS_CERTIFIED == 1
      ? '<span class="badge" style="background:#fef9c3;color:#854d0e;">Certified</span>'
      : '<span style="color:#bbb;font-size:12px;">—</span>';

    return '<tr>'                                                                             +
      '<td><strong>' + (a.ASSET_ID        || '—') + '</strong></td>'                         +
      '<td>'         + qrTagHTML(a)               + '</td>'                                  +
      '<td>'         + (a.DESCRIPTION     || '—') + '</td>'                                  +
      '<td>'         + (a.SERIAL_NUMBER   || '—') + '</td>'                                  +
      '<td>'         + (a.CATEGORY_NAME   || '—') + '</td>'                                  +
      '<td>'         + (a.DEPARTMENT_NAME || '—') + '</td>'                                  +
      '<td>'         + (a.LOCATION        || '—') + '</td>'                                  +
      '<td><span class="badge ' + badgeClass(a.STATUS) + '">' + a.STATUS + '</span></td>'   +
      '<td>'         + cert                       + '</td>'                                  +
      '<td>'         + actionBtns(a.ASSET_ID)     + '</td>'                                  +
    '</tr>';
  }).join('');
}


// LOAD DROPDOWNS
function loadDropdowns() {

  // Departments
  fetch(API + '?resource=departments')
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

  // Categories
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
  ['assetsAssetId', 'assetsDescription',
   'assetsSerialNumber', 'assetsLocation'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  ['assetsDepartment', 'assetsStatus', 'assetsCategory'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.selectedIndex = 0;
  });
  var qr  = document.getElementById('assetsQrCode');   if (qr)  qr.value    = '';
  var cer = document.getElementById('assetsCertified'); if (cer) cer.checked = false;
}


// SAVE ASSET
function assetsSave() {
  var get = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var asset_id      = get('assetsAssetId');
  var qr_code       = get('assetsQrCode')      || generateQRValue();
  var description   = get('assetsDescription');
  var serial_number = get('assetsSerialNumber');
  var category_id   = get('assetsCategory');
  var department_id = get('assetsDepartment');
  var location      = get('assetsLocation');
  var status        = get('assetsStatus')       || 'Available';
  var certEl        = document.getElementById('assetsCertified');
  var is_certified  = certEl && certEl.checked ? 1 : 0;

  if (!asset_id || !description || !department_id) {
    showToast('⚠ Please fill in all required fields.');
    return;
  }

  var formData = new FormData();
  formData.append('resource',      'assets');
  formData.append('action',        'add');
  formData.append('asset_id',      asset_id);
  formData.append('qr_code',       qr_code);
  formData.append('description',   description);
  formData.append('serial_number', serial_number);
  formData.append('category_id',   category_id);
  formData.append('department_id', department_id);
  formData.append('location',      location);
  formData.append('status',        status);
  formData.append('is_certified',  is_certified);

  fetch(API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('assetsModalOverlay');
        assetsClearForm();
        loadAssets();
        showToast('✓ Asset added! QR code generated.');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// EDIT ROW
function editRow(asset_id) {
  fetch(API + '?resource=assets&action=getById&asset_id=' + asset_id)
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') { showToast('⚠ Asset not found.'); return; }
      var a   = data.data;
      var set = function(id, val) { var el = document.getElementById(id); if (el) el.value = val; };

      set('editAssetId',      a.ASSET_ID);
      set('editQrCode',       a.QR_CODE);
      set('editDescription',  a.DESCRIPTION);
      set('editSerialNumber', a.SERIAL_NUMBER   || '');
      set('editCategory',     a.CATEGORY_ID    || '');
      set('editDepartment',   a.DEPARTMENT_ID  || '');
      set('editLocation',     a.LOCATION        || '');
      set('editStatus',       a.STATUS);

      var cer = document.getElementById('editCertified');
      if (cer) cer.checked = a.IS_CERTIFIED == 1;

      document.getElementById('editModal').setAttribute('data-edit-id', a.ASSET_ID);
      openModal('editModal');
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// SAVE EDIT
function assetsSaveEdit() {
  var modal    = document.getElementById('editModal');
  var asset_id = modal.getAttribute('data-edit-id');
  var get      = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var description   = get('editDescription');
  var serial_number = get('editSerialNumber');
  var category_id   = get('editCategory');
  var department_id = get('editDepartment');
  var location      = get('editLocation');
  var status        = get('editStatus')         || 'Available';
  var certEl        = document.getElementById('editCertified');
  var is_certified  = certEl && certEl.checked ? 1 : 0;

  if (!description || !department_id) {
    showToast('⚠ Please fill in all required fields.');
    return;
  }

  var formData = new FormData();
  formData.append('resource',      'assets');
  formData.append('action',        'update');
  formData.append('asset_id',      asset_id);
  formData.append('description',   description);
  formData.append('serial_number', serial_number);
  formData.append('category_id',   category_id);
  formData.append('department_id', department_id);
  formData.append('location',      location);
  formData.append('status',        status);
  formData.append('is_certified',  is_certified);

  fetch(API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('editModal');
        loadAssets();
        showToast('✓ Asset updated!');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// DELETE ROW
function deleteRow(asset_id) {
  if (!confirm('Delete this asset?')) return;

  var formData = new FormData();
  formData.append('resource',  'assets');
  formData.append('action',    'delete');
  formData.append('asset_id',  asset_id);

  fetch(API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        loadAssets();
        showToast('🗑 Asset deleted.');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// VIEW QR BY ID
function viewQRById(asset_id) {
  var asset = assets.find(function(a) { return a.ASSET_ID === asset_id; });
  if (asset) showQRModal(asset);
}


// BUTTON HOOKS
document.addEventListener('DOMContentLoaded', function() {

  // Open add modal
  var openBtn = document.getElementById('assetsOpenModalBtn');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      var qr = document.getElementById('assetsQrCode');
      if (qr) qr.value = generateQRValue();
      openModal('assetsModalOverlay');
      var f = document.getElementById('assetsAssetId');
      if (f) f.focus();
    });
  }

  // Cancel add
  var cancelBtn = document.getElementById('assetsCancelBtn');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      closeModal('assetsModalOverlay');
      assetsClearForm();
    });
  }

  // Save add
  var saveBtn = document.getElementById('assetsSaveBtn');
  if (saveBtn) saveBtn.addEventListener('click', assetsSave);

  // Cancel edit
  var cancelEditBtn = document.getElementById('cancelEditBtn');
  if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', function() { closeModal('editModal'); });
  }

  // Save edit
  var saveEditBtn = document.getElementById('saveEditBtn');
  if (saveEditBtn) saveEditBtn.addEventListener('click', assetsSaveEdit);

  // Close QR modal
  var closeQrBtn = document.getElementById('closeQrViewBtn');
  if (closeQrBtn) {
    closeQrBtn.addEventListener('click', function() { closeModal('qrViewModal'); });
  }

  // Download QR
  var downloadQrBtn = document.getElementById('downloadQrBtn');
  if (downloadQrBtn) downloadQrBtn.addEventListener('click', downloadQR);

  // Search
  var searchInput = document.getElementById('assetsSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      renderAssetsTable(this.value, getActiveTab());
    });
  }

  // Filter tabs
  document.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
      var s = document.getElementById('assetsSearchInput');
      renderAssetsTable(s ? s.value : '', this.dataset.status || 'ALL');
    });
  });

  // Close modal on outside click
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('active');
    });
  });

  // Close modal on Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
        m.classList.remove('active');
      });
    }
  });

  loadDropdowns();
  loadAssets();

});