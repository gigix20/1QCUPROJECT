// ===========================
// FILTER TABS
// ===========================
document.querySelectorAll('.filter-tabs').forEach(function(group) {
  group.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      group.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');

      // Assets page: filter table by status tab
      var tabText = this.textContent.trim();
      filterByTab(tabText);
    });
  });
});


// ===========================
// SETTINGS NAV (settings.html only)
// ===========================
var settingsNavItems = document.querySelectorAll('.settings-nav-item');
var settingsPanels   = document.querySelectorAll('.settings-panel-content');

settingsNavItems.forEach(function(item) {
  item.addEventListener('click', function() {
    settingsNavItems.forEach(function(n) { n.classList.remove('active'); });
    this.classList.add('active');

    settingsPanels.forEach(function(p) { p.classList.remove('active'); });
    var panel = document.getElementById('panel-' + this.getAttribute('data-panel'));
    if (panel) panel.classList.add('active');
  });
});

var saveBtn = document.getElementById('saveSettingsBtn');
if (saveBtn) {
  saveBtn.addEventListener('click', function() {
    openModal('saveModal');
  });
}


// ===========================
// MODAL FUNCTIONS
// ===========================
function openModal(id) {
  var modal = document.getElementById(id);
  if (modal) modal.classList.add('active');
}

function closeModal(id) {
  var modal = document.getElementById(id);
  if (modal) modal.classList.remove('active');
}

document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
  });
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
      m.classList.remove('active');
    });
  }
});


// ===========================
// SHARED ASSET STORE
// ===========================
var assets    = [];
var idCounter = 1;

var toastEl = document.getElementById('toast');

// Dashboard elements
var openModalBtn   = document.getElementById('openModalBtn');
var cancelBtn      = document.getElementById('cancelBtn');
var saveAssetBtn   = document.getElementById('saveAssetBtn');
var assetTableBody = document.getElementById('assetTableBody');
var searchInput    = document.getElementById('searchInput');

// Assets page elements
var assetsOpenModalBtn   = document.getElementById('assetsOpenModalBtn');
var assetsCancelBtn      = document.getElementById('assetsCancelBtn');
var assetsSaveBtn        = document.getElementById('assetsSaveBtn');
var assetsTableBody      = document.getElementById('assetsTableBody');
var assetsSearchInput    = document.getElementById('assetsSearchInput');

// Edit modal
var cancelEditBtn = document.getElementById('cancelEditBtn');
var saveEditBtn   = document.getElementById('saveEditBtn');

// QR modal
var closeQrViewBtn = document.getElementById('closeQrViewBtn');
var downloadQrBtn  = document.getElementById('downloadQrBtn');


// ===========================
// DASHBOARD EVENTS
// ===========================
if (openModalBtn) {
  openModalBtn.addEventListener('click', function() {
    openModal('modalOverlay');
    autoFillQR('qrCode');
    var f = document.getElementById('assetId');
    if (f) f.focus();
  });
}

if (cancelBtn) {
  cancelBtn.addEventListener('click', function() {
    closeModal('modalOverlay');
    clearForm(['assetId', 'description'], ['department', 'status'], 'qrCode');
  });
}

if (saveAssetBtn) {
  saveAssetBtn.addEventListener('click', function() { saveAsset('dashboard'); });
}

if (searchInput) {
  searchInput.addEventListener('input', function() {
    renderDashboardTable(this.value.toLowerCase());
  });
}


// ===========================
// ASSETS PAGE EVENTS
// ===========================
if (assetsOpenModalBtn) {
  assetsOpenModalBtn.addEventListener('click', function() {
    openModal('assetsModalOverlay');
    autoFillQR('assetsQrCode');
    var f = document.getElementById('assetsAssetId');
    if (f) f.focus();
  });
}

if (assetsCancelBtn) {
  assetsCancelBtn.addEventListener('click', function() {
    closeModal('assetsModalOverlay');
    clearForm(
      ['assetsAssetId', 'assetsDescription', 'assetsSerialNumber', 'assetsLocation'],
      ['assetsDepartment', 'assetsStatus', 'assetsCategory'],
      'assetsQrCode'
    );
    var certEl = document.getElementById('assetsCertified');
    if (certEl) certEl.checked = false;
  });
}

if (assetsSaveBtn) {
  assetsSaveBtn.addEventListener('click', function() { saveAsset('assets'); });
}

if (assetsSearchInput) {
  assetsSearchInput.addEventListener('input', function() {
    renderAssetsTable(this.value.toLowerCase(), getActiveTab());
  });
}


// ===========================
// EDIT MODAL EVENTS
// ===========================
if (cancelEditBtn) {
  cancelEditBtn.addEventListener('click', function() { closeModal('editModal'); });
}

if (saveEditBtn) {
  saveEditBtn.addEventListener('click', saveEdit);
}

if (closeQrViewBtn) {
  closeQrViewBtn.addEventListener('click', function() { closeModal('qrViewModal'); });
}

if (downloadQrBtn) {
  downloadQrBtn.addEventListener('click', downloadQR);
}


// ===========================
// DEPT COLOR MAP
// ===========================
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


// ===========================
// QR CODE GENERATION
// ===========================
function generateQRValue() {
  var now  = new Date();
  var y    = now.getFullYear();
  var m    = String(now.getMonth() + 1).padStart(2, '0');
  var d    = String(now.getDate()).padStart(2, '0');
  var rand = Math.random().toString(36).substring(2, 6).toUpperCase();
  return 'ONEQCU-' + y + m + d + '-' + rand;
}

function autoFillQR(fieldId) {
  var f = document.getElementById(fieldId);
  if (f) f.value = generateQRValue();
}

function showQRModal(asset) {
  var modal = document.getElementById('qrViewModal');
  if (!modal) return;

  var idLabel   = document.getElementById('qrModalAssetId');
  var descLabel = document.getElementById('qrModalDesc');
  var codeLabel = document.getElementById('qrModalCodeText');

  if (idLabel)   idLabel.textContent   = asset.assetId;
  if (descLabel) descLabel.textContent = asset.description;
  if (codeLabel) codeLabel.textContent = asset.qrCode;

  var deptColor = getDeptColor(asset.department);
  var divider   = document.querySelector('#qrViewModal .modal-divider');
  if (divider) divider.style.background = 'linear-gradient(to right, ' + deptColor + ', transparent)';

  var deptBadge = document.getElementById('qrModalDept');
  if (deptBadge) {
    deptBadge.textContent       = asset.department;
    deptBadge.style.background  = deptColor + '18';
    deptBadge.style.color       = deptColor;
    deptBadge.style.borderColor = deptColor + '44';
  }

  var container = document.getElementById('qrCanvas');
  if (container) {
    container.innerHTML = '';
    new QRCode(container, {
      text:         asset.qrCode,
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

  var canvas = container.querySelector('canvas');
  var codeLabel = document.getElementById('qrModalCodeText');
  var filename  = (codeLabel ? codeLabel.textContent : 'qrcode') + '.png';

  if (!canvas) {
    var img = container.querySelector('img');
    if (img) {
      var a = document.createElement('a');
      a.href = img.src;
      a.download = filename;
      a.click();
    }
    return;
  }

  var a = document.createElement('a');
  a.download = filename;
  a.href = canvas.toDataURL('image/png');
  a.click();
}


// ===========================
// HELPERS
// ===========================
function clearForm(textIds, selectIds, qrFieldId) {
  textIds.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  selectIds.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.selectedIndex = 0;
  });
  var qrField = document.getElementById(qrFieldId);
  if (qrField) qrField.value = '';
}

function showToast(msg) {
  if (!toastEl) return;
  toastEl.textContent = msg;
  toastEl.classList.add('show');
  setTimeout(function() { toastEl.classList.remove('show'); }, 3000);
}

function badgeClass(status) {
  var map = { 'Available': 'available', 'In Use': 'in-use', 'Maintenance': 'maintenance' };
  return map[status] || 'available';
}

function getActiveTab() {
  var active = document.querySelector('.filter-tab.active');
  return active ? active.textContent.trim() : 'ALL ASSETS';
}

function qrTagHTML(a) {
  var c = getDeptColor(a.department);
  return '<span class="qr-tag" title="Click to view QR" onclick="showQRModal(' +
    JSON.stringify(a).replace(/'/g, "\\'") + ')" style="border-left:3px solid ' +
    c + ';color:' + c + ';background:' + c + '12;">' + a.qrCode + '</span>';
}

function actionBtns(id) {
  return '<button class="view-btn" onclick="viewQRById(' + id + ')">View QR</button>' +
         '<button class="edit-btn" onclick="editRow(' + id + ')">Edit</button>' +
         '<button class="del-btn"  onclick="deleteRow(' + id + ')">Delete</button>';
}


// ===========================
// SAVE ASSET (dashboard + assets page)
// ===========================
function saveAsset(page) {
  var assetId, qrCode, description, department, status;
  var serialNumber = '', category = '', location = '', certified = false;

  if (page === 'assets') {
    assetId      = val('assetsAssetId');
    qrCode       = val('assetsQrCode');
    description  = val('assetsDescription');
    department   = val('assetsDepartment');
    status       = val('assetsStatus');
    serialNumber = val('assetsSerialNumber');
    category     = val('assetsCategory');
    location     = val('assetsLocation');
    certified    = document.getElementById('assetsCertified') ? document.getElementById('assetsCertified').checked : false;
  } else {
    assetId     = val('assetId');
    qrCode      = val('qrCode');
    description = val('description');
    department  = val('department');
    status      = val('status');
  }

  if (!assetId || !description || !department) {
    showToast('Please fill in all required fields.');
    return;
  }

  if (!qrCode) qrCode = generateQRValue();

  var updated  = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
  var newAsset = {
    _id: idCounter++,
    assetId: assetId, qrCode: qrCode, description: description,
    department: department, status: status, updated: updated,
    serialNumber: serialNumber, category: category,
    location: location, certified: certified
  };
  assets.push(newAsset);

  if (page === 'assets') {
    closeModal('assetsModalOverlay');
    clearForm(
      ['assetsAssetId', 'assetsDescription', 'assetsSerialNumber', 'assetsLocation'],
      ['assetsDepartment', 'assetsStatus', 'assetsCategory'],
      'assetsQrCode'
    );
    var certEl = document.getElementById('assetsCertified');
    if (certEl) certEl.checked = false;
    renderAssetsTable('', getActiveTab());
  } else {
    closeModal('modalOverlay');
    clearForm(['assetId', 'description'], ['department', 'status'], 'qrCode');
    renderDashboardTable('');
    updateStats();
  }

  showToast('Asset added! QR code generated.');
  setTimeout(function() { showQRModal(newAsset); }, 400);
}

function val(id) {
  var el = document.getElementById(id);
  return el ? el.value.trim() : '';
}


// ===========================
// EDIT ASSET
// ===========================
function editRow(id) {
  var asset = assets.find(function(a) { return a._id === id; });
  if (!asset) return;

  document.getElementById('editAssetId').value      = asset.assetId;
  document.getElementById('editQrCode').value        = asset.qrCode;
  document.getElementById('editDescription').value  = asset.description;
  document.getElementById('editDepartment').value   = asset.department;
  document.getElementById('editStatus').value       = asset.status;

  // Extra fields (only present on assets page edit modal)
  var snEl  = document.getElementById('editSerialNumber');
  var catEl = document.getElementById('editCategory');
  var locEl = document.getElementById('editLocation');
  var cerEl = document.getElementById('editCertified');
  if (snEl)  snEl.value    = asset.serialNumber || '';
  if (catEl) catEl.value   = asset.category     || '';
  if (locEl) locEl.value   = asset.location     || '';
  if (cerEl) cerEl.checked = asset.certified    || false;

  document.getElementById('editModal').setAttribute('data-edit-id', id);
  openModal('editModal');
}

function saveEdit() {
  var modal = document.getElementById('editModal');
  var id    = parseInt(modal.getAttribute('data-edit-id'));
  var asset = assets.find(function(a) { return a._id === id; });
  if (!asset) return;

  var assetId     = document.getElementById('editAssetId').value.trim();
  var description = document.getElementById('editDescription').value.trim();
  var department  = document.getElementById('editDepartment').value;
  var status      = document.getElementById('editStatus').value;

  if (!assetId || !description || !department) {
    showToast('Please fill in all required fields.');
    return;
  }

  asset.assetId     = assetId;
  asset.description = description;
  asset.department  = department;
  asset.status      = status;
  asset.updated     = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });

  var snEl  = document.getElementById('editSerialNumber');
  var catEl = document.getElementById('editCategory');
  var locEl = document.getElementById('editLocation');
  var cerEl = document.getElementById('editCertified');
  if (snEl)  asset.serialNumber = snEl.value.trim();
  if (catEl) asset.category     = catEl.value.trim();
  if (locEl) asset.location     = locEl.value.trim();
  if (cerEl) asset.certified    = cerEl.checked;

  closeModal('editModal');

  // Refresh whichever table is visible
  if (assetsTableBody) {
    renderAssetsTable(assetsSearchInput ? assetsSearchInput.value.toLowerCase() : '', getActiveTab());
  } else {
    renderDashboardTable(searchInput ? searchInput.value.toLowerCase() : '');
    updateStats();
  }

  showToast('Asset updated successfully!');
}


// ===========================
// DELETE ASSET
// ===========================
function deleteRow(id) {
  if (!confirm('Delete this asset?')) return;
  var idx = assets.findIndex(function(a) { return a._id === id; });
  if (idx > -1) assets.splice(idx, 1);

  if (assetsTableBody) {
    renderAssetsTable(assetsSearchInput ? assetsSearchInput.value.toLowerCase() : '', getActiveTab());
  } else {
    renderDashboardTable(searchInput ? searchInput.value.toLowerCase() : '');
    updateStats();
  }

  showToast('Asset deleted.');
}

function viewQRById(id) {
  var asset = assets.find(function(a) { return a._id === id; });
  if (asset) showQRModal(asset);
}


// ===========================
// DASHBOARD TABLE
// ===========================
function renderDashboardTable(filter) {
  if (!assetTableBody) return;
  filter = filter || '';

  var filtered = assets.filter(function(a) {
    return a.assetId.toLowerCase().includes(filter) ||
           a.description.toLowerCase().includes(filter) ||
           a.department.toLowerCase().includes(filter) ||
           a.qrCode.toLowerCase().includes(filter);
  }).slice().reverse().slice(0, 10);

  if (!filtered.length) {
    assetTableBody.innerHTML = '<tr class="empty-row"><td colspan="7">No assets to display.</td></tr>';
    return;
  }

  assetTableBody.innerHTML = filtered.map(function(a) {
    return '<tr>' +
      '<td><strong>' + a.assetId + '</strong></td>' +
      '<td>' + qrTagHTML(a) + '</td>' +
      '<td>' + a.description + '</td>' +
      '<td>' + a.department + '</td>' +
      '<td><span class="badge ' + badgeClass(a.status) + '">' + a.status + '</span></td>' +
      '<td>' + a.updated + '</td>' +
      '<td>' + actionBtns(a._id) + '</td>' +
    '</tr>';
  }).join('');
}

function updateStats() {
  var total       = assets.length;
  var inUse       = assets.filter(function(a) { return a.status === 'In Use'; }).length;
  var available   = assets.filter(function(a) { return a.status === 'Available'; }).length;
  var maintenance = assets.filter(function(a) { return a.status === 'Maintenance'; }).length;

  var totalEl = document.getElementById('totalCount');
  var inUseEl = document.getElementById('inUseCount');
  var pctEl   = document.getElementById('inUsePct');
  var availEl = document.getElementById('availableCount');
  var maintEl = document.getElementById('maintenanceCount');

  if (totalEl) totalEl.textContent = total;
  if (inUseEl) inUseEl.textContent = inUse;
  if (pctEl)   pctEl.textContent   = total ? Math.round(inUse / total * 100) + '% total' : '0% total';
  if (availEl) availEl.textContent = available;
  if (maintEl) maintEl.textContent = maintenance;
}


// ===========================
// ASSETS PAGE TABLE
// ===========================
function filterByTab(tabText) {
  var search = assetsSearchInput ? assetsSearchInput.value.toLowerCase() : '';
  renderAssetsTable(search, tabText);
}

function renderAssetsTable(filter, tabFilter) {
  if (!assetsTableBody) return;
  filter    = filter    || '';
  tabFilter = tabFilter || 'ALL ASSETS';

  var filtered = assets.filter(function(a) {
    var matchSearch =
      a.assetId.toLowerCase().includes(filter)      ||
      a.description.toLowerCase().includes(filter)  ||
      a.department.toLowerCase().includes(filter)   ||
      a.qrCode.toLowerCase().includes(filter)       ||
      (a.serialNumber || '').toLowerCase().includes(filter) ||
      (a.category     || '').toLowerCase().includes(filter) ||
      (a.location     || '').toLowerCase().includes(filter);

    var matchTab = true;
    if (tabFilter === 'AVAILABLE')   matchTab = a.status === 'Available';
    if (tabFilter === 'IN USE')      matchTab = a.status === 'In Use';
    if (tabFilter === 'MAINTENANCE') matchTab = a.status === 'Maintenance';
    if (tabFilter === 'CERTIFIED')   matchTab = a.certified === true;

    return matchSearch && matchTab;
  }).slice().reverse();

  if (!filtered.length) {
    assetsTableBody.innerHTML = '<tr class="empty-row"><td colspan="9">No assets to display.</td></tr>';
    return;
  }

  assetsTableBody.innerHTML = filtered.map(function(a) {
    var certBadge = a.certified
      ? '<span class="badge" style="background:#fef9c3;color:#854d0e;">Certified</span>'
      : '<span style="color:#bbb;font-size:12px;">—</span>';
    return '<tr>' +
      '<td><strong>' + a.assetId + '</strong></td>' +
      '<td>' + qrTagHTML(a) + '</td>' +
      '<td>' + a.description + '</td>' +
      '<td>' + (a.serialNumber || '—') + '</td>' +
      '<td>' + (a.category     || '—') + '</td>' +
      '<td>' + a.department + '</td>' +
      '<td>' + (a.location     || '—') + '</td>' +
      '<td><span class="badge ' + badgeClass(a.status) + '">' + a.status + '</span></td>' +
      '<td>' + certBadge + '</td>' +
      '<td>' + actionBtns(a._id) + '</td>' +
    '</tr>';
  }).join('');
}
