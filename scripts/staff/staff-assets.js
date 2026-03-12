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



// QR TAG
function qrTagHTML(a) {
  var c  = getDeptColor(a.DEPARTMENT_NAME || a.department);
  var id = a.ASSET_ID || a.assetId;
  return '<span class="qr-tag" title="Click to view QR" ' +
    'onclick="showQRModal(' + JSON.stringify(a).replace(/'/g, "\\'") + ')" ' +
    'style="border-left:3px solid ' + c + ';color:' + c + ';background:' + c + '12;">' +
    id + '</span>';
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
      (a.ITEM_TYPE_NAME  || '').toLowerCase().includes(filter) ||
      (a.CATEGORY_NAME   || '').toLowerCase().includes(filter) ||
      (a.DEPARTMENT_NAME || '').toLowerCase().includes(filter) ||
      (a.LOCATION        || '').toLowerCase().includes(filter) ||
      (a.QR_CODE         || '').toLowerCase().includes(filter) ||
      (a.LAST_NAME       || '').toLowerCase().includes(filter) ||  // ← NEW
      (a.FIRST_NAME      || '').toLowerCase().includes(filter);    // ← NEW

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

    // Build liable person full name
    var liable = [a.FIRST_NAME, a.MIDDLE_NAME, a.LAST_NAME, a.SUFFIX]
                   .filter(Boolean).join(' ') || '—';

    return '<tr>'                                                        +
      '<td><strong>' + (a.ASSET_ID        || '—') + '</strong></td>'    +
      '<td>'         + qrTagHTML(a)               + '</td>'             +
      '<td>'         + (a.DESCRIPTION     || '—') + '</td>'             +
      '<td>'         + (a.SERIAL_NUMBER   || '—') + '</td>'             +
      '<td>'         + (a.ITEM_TYPE_NAME  || '—') + '</td>'             +
      '<td>'         + (a.CATEGORY_NAME   || '—') + '</td>'             +
      '<td>'         + (a.DEPARTMENT_NAME || '—') + '</td>'             +
      '<td>'         + liable                     + '</td>'             + // ← NEW
      '<td>'         + (a.LOCATION        || '—') + '</td>'             +
      '<td>'         + statusCell                 + '</td>'             +
      '<td>'         + cert                       + '</td>'             +
      '<td>'         + actionBtns(a)              + '</td>'             +
    '</tr>';
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

  // Departments
  fetch(API + '?resource=departments&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var addSel  = document.getElementById('assetsDepartment');
      var editSel = document.getElementById('editDepartment');
      data.data.forEach(function(d) {
        var liable = [d.FIRST_NAME, d.MIDDLE_NAME, d.LAST_NAME, d.SUFFIX]
                      .filter(Boolean).join(' ');
        var opt = '<option value="' + d.DEPARTMENT_ID + '" ' +
                  'data-liable="'  + (liable || '—')  + '">' +
                  d.DEPARTMENT_NAME + '</option>';
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

  // Item Types
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

  // Reset liable dropdown
  var liableSel = document.getElementById('assetsLiablePerson');
  if (liableSel) {
    liableSel.innerHTML = '<option value="">-- Select Department first --</option>';
    liableSel.disabled  = true;
  }
}



// SAVE ASSET
function assetsSave() {
  var get = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var description   = get('assetsDescription');
  var serial_number = get('assetsSerialNumber');
  var category_id   = get('assetsCategory');
  var department_id = get('assetsDepartment');
  var item_type_id  = get('assetsItemType');
  var location      = get('assetsLocation');
  var status        = get('assetsStatus')  || 'Available';
  var quantity      = parseInt(document.getElementById('assetsQuantity').value) || 1;
  var certEl        = document.getElementById('assetsCertified');
  var is_certified  = certEl && certEl.checked ? 1 : 0;

  if (!description || !department_id || !item_type_id) {
    showToast('⚠ Please fill in all required fields.');
    return;
  }

  var formData = new FormData();
  formData.append('resource',      'assets');
  formData.append('action',        'add');
  formData.append('description',   description);
  formData.append('serial_number', serial_number);
  formData.append('category_id',   category_id);
  formData.append('department_id', department_id);
  formData.append('item_type_id',  item_type_id);
  formData.append('location',      location);
  formData.append('status',        status);
  formData.append('is_certified',  is_certified);
  formData.append('quantity',      quantity);

  fetch(API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('assetsModalOverlay');
        assetsClearForm();
        loadAssets();
        var generated = data.data && data.data.generated ? data.data.generated : [];
        var msg = '✓ ' + generated.length + ' asset(s) added: ' + generated.join(', ');
        showToast(msg);
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
      set('editSerialNumber', a.SERIAL_NUMBER || '');
      set('editCategory',     a.CATEGORY_ID  || '');
      set('editDepartment',   a.DEPARTMENT_ID || '');
      updateLiableDropdown('editDepartment', 'editLiablePerson'); // ← populate liable
      set('editItemType',     a.ITEM_TYPE_ID  || '');
      set('editLocation',     a.LOCATION      || '');
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
  var item_type_id  = get('editItemType');
  var location      = get('editLocation');
  var status        = get('editStatus') || 'Available';
  var certEl        = document.getElementById('editCertified');
  var is_certified  = certEl && certEl.checked ? 1 : 0;

  if (!description || !department_id || !item_type_id) {
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
  formData.append('item_type_id',  item_type_id);
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
  if (!confirm('Request deletion of this asset?')) return;

  var formData = new FormData();
  formData.append('resource',   'assets');
  formData.append('action',     'delete');
  formData.append('asset_id',   asset_id);
  formData.append('deleted_by', 'staff');

  fetch(API, { method: 'POST', body: formData })
    .then(function(res) {
      var contentType = res.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return res.json();
      }
      if (res.ok) return { status: 'success' };
      return { status: 'error', message: 'Server error.' };
    })
    .then(function(data) {
      if (data.status === 'success') {
        loadAssets();
        showToast('🗑 Deletion request submitted.');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() {
      loadAssets();
      showToast('🗑 Deletion request submitted.');
    });
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
      openModal('assetsModalOverlay');
      var f = document.getElementById('assetsDescription');
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

  // Liable person dropdown — Add modal
  var deptSelect = document.getElementById('assetsDepartment');
  if (deptSelect) {
    deptSelect.addEventListener('change', function() {
      updateLiableDropdown('assetsDepartment', 'assetsLiablePerson');
    });
  }

  // Liable person dropdown — Edit modal
  var editDeptSelect = document.getElementById('editDepartment');
  if (editDeptSelect) {
    editDeptSelect.addEventListener('change', function() {
      updateLiableDropdown('editDepartment', 'editLiablePerson');
    });
  }

  loadDropdowns();
  loadAssets();
  handleQRScan();

});