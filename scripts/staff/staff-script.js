// ============================================================
//  ONEQCU STAFF SCRIPT
//  Single file — uses PAGE detection to run only relevant code
// ============================================================

// ------ Detect current page ------
var PAGE = (function() {
  var path = window.location.pathname.toLowerCase();
  if (path.includes('dashboard'))   return 'dashboard';
  if (path.includes('assets'))      return 'assets';
  if (path.includes('borrow'))      return 'borrow';
  if (path.includes('maintenance')) return 'maintenance';
  if (path.includes('reports'))     return 'reports';
  if (path.includes('departments')) return 'departments';
  if (path.includes('settings'))    return 'settings';
  return 'dashboard'; // fallback
})();


// ============================================================
//  SHARED UTILITIES  (run on every page)
// ============================================================

var toastEl = document.getElementById('toast');

function showToast(msg) {
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

// Close modal on outside click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
  });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
      m.classList.remove('active');
    });
  }
});

function formatDate(dateStr) {
  if (!dateStr) return '—';
  var d = new Date(dateStr);
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getActiveTab() {
  var active = document.querySelector('.filter-tab.active');
  return active ? active.textContent.trim() : '';
}

function hookFilterTabs(onTabClick) {
  document.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
      onTabClick(this.textContent.trim());
    });
  });
}


// ============================================================
//  SETTINGS PAGE
// ============================================================
if (PAGE === 'settings') {
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

  var saveSettingsBtn = document.getElementById('saveSettingsBtn');
  if (saveSettingsBtn) {
    saveSettingsBtn.addEventListener('click', function() { openModal('saveModal'); });
  }
}


// ============================================================
//  DEPT COLOR MAP  (used by dashboard + assets)
// ============================================================
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
  return { 'Available': 'available', 'In Use': 'in-use', 'Maintenance': 'maintenance' }[status] || 'available';
}

function qrTagHTML(a) {
  var c = getDeptColor(a.department);
  return '<span class="qr-tag" title="Click to view QR" onclick="showQRModal(' +
    JSON.stringify(a).replace(/'/g, "\\'") + ')" style="border-left:3px solid ' +
    c + ';color:' + c + ';background:' + c + '12;">' + a.qrCode + '</span>';
}

function actionBtns(id) {
  return '<button class="view-btn" onclick="viewQRById(' + id + ')">View QR</button> ' +
         '<button class="edit-btn" onclick="editRow(' + id + ')">Edit</button> ' +
         '<button class="del-btn"  onclick="deleteRow(' + id + ')">Delete</button>';
}

// QR generation
function generateQRValue() {
  var now  = new Date();
  var y    = now.getFullYear();
  var m    = String(now.getMonth() + 1).padStart(2, '0');
  var d    = String(now.getDate()).padStart(2, '0');
  var rand = Math.random().toString(36).substring(2, 6).toUpperCase();
  return 'ONEQCU-' + y + m + d + '-' + rand;
}

function showQRModal(asset) {
  var modal = document.getElementById('qrViewModal');
  if (!modal) return;

  var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
  set('qrModalAssetId',  asset.assetId);
  set('qrModalDesc',     asset.description);
  set('qrModalCodeText', asset.qrCode);

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
  if (container && typeof QRCode !== 'undefined') {
    container.innerHTML = '';
    new QRCode(container, {
      text: asset.qrCode, width: 200, height: 200,
      colorDark: deptColor, colorLight: '#ffffff',
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

  var a = document.createElement('a');
  a.download = filename;
  if (canvas)     { a.href = canvas.toDataURL('image/png'); a.click(); }
  else if (img)   { a.href = img.src; a.click(); }
}


// ============================================================
//  SHARED ASSET STORE  (dashboard + assets share same array)
// ============================================================
var assets    = [];
var idCounter = 1;


// ============================================================
//  DASHBOARD PAGE
// ============================================================
if (PAGE === 'dashboard') {

  var openModalBtn   = document.getElementById('openModalBtn');
  var cancelBtn      = document.getElementById('cancelBtn');
  var saveAssetBtn   = document.getElementById('saveAssetBtn');
  var assetTableBody = document.getElementById('assetTableBody');
  var searchInput    = document.getElementById('searchInput');
  var cancelEditBtn  = document.getElementById('cancelEditBtn');
  var saveEditBtn    = document.getElementById('saveEditBtn');
  var closeQrViewBtn = document.getElementById('closeQrViewBtn');
  var downloadQrBtn  = document.getElementById('downloadQrBtn');

  if (openModalBtn) {
    openModalBtn.addEventListener('click', function() {
      var qr = document.getElementById('qrCode');
      if (qr) qr.value = generateQRValue();
      openModal('modalOverlay');
      var f = document.getElementById('assetId');
      if (f) f.focus();
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      closeModal('modalOverlay');
      dashClearForm();
    });
  }

  if (saveAssetBtn)   saveAssetBtn.addEventListener('click',  dashSaveAsset);
  if (cancelEditBtn)  cancelEditBtn.addEventListener('click', function() { closeModal('editModal'); });
  if (saveEditBtn)    saveEditBtn.addEventListener('click',   dashSaveEdit);
  if (closeQrViewBtn) closeQrViewBtn.addEventListener('click', function() { closeModal('qrViewModal'); });
  if (downloadQrBtn)  downloadQrBtn.addEventListener('click', downloadQR);

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      renderDashTable(this.value.toLowerCase());
    });
  }

  function dashClearForm() {
    ['assetId', 'qrCode', 'description'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = '';
    });
    ['department', 'status'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.selectedIndex = 0;
    });
  }

  function dashSaveAsset() {
    var assetId    = document.getElementById('assetId')     ? document.getElementById('assetId').value.trim()    : '';
    var qrCode     = document.getElementById('qrCode')      ? document.getElementById('qrCode').value.trim()     : '';
    var desc       = document.getElementById('description') ? document.getElementById('description').value.trim(): '';
    var department = document.getElementById('department')  ? document.getElementById('department').value        : '';
    var status     = document.getElementById('status')      ? document.getElementById('status').value            : 'Available';

    if (!assetId || !desc || !department) { showToast('⚠ Please fill in all required fields.'); return; }
    if (!qrCode) qrCode = generateQRValue();

    var newAsset = {
      _id: idCounter++, assetId: assetId, qrCode: qrCode,
      description: desc, department: department, status: status,
      updated: formatDate(new Date().toISOString().split('T')[0]),
      serialNumber: '', category: '', location: '', certified: false
    };
    assets.push(newAsset);

    closeModal('modalOverlay');
    dashClearForm();
    renderDashTable('');
    updateDashStats();
    showToast('✓ Asset added! QR code generated.');
    setTimeout(function() { showQRModal(newAsset); }, 400);
  }

  function dashSaveEdit() {
    var modal  = document.getElementById('editModal');
    var id     = parseInt(modal.getAttribute('data-edit-id'));
    var asset  = assets.find(function(a) { return a._id === id; });
    if (!asset) return;

    var assetId = document.getElementById('editAssetId')    ? document.getElementById('editAssetId').value.trim()    : '';
    var desc    = document.getElementById('editDescription') ? document.getElementById('editDescription').value.trim(): '';
    var dept    = document.getElementById('editDepartment') ? document.getElementById('editDepartment').value        : '';
    var status  = document.getElementById('editStatus')     ? document.getElementById('editStatus').value            : 'Available';

    if (!assetId || !desc || !dept) { showToast('⚠ Please fill in all required fields.'); return; }

    asset.assetId = assetId; asset.description = desc;
    asset.department = dept; asset.status = status;
    asset.updated = formatDate(new Date().toISOString().split('T')[0]);

    closeModal('editModal');
    renderDashTable(searchInput ? searchInput.value.toLowerCase() : '');
    updateDashStats();
    showToast('✓ Asset updated!');
  }

  function updateDashStats() {
    var total    = assets.length;
    var inUse    = assets.filter(function(a) { return a.status === 'In Use'; }).length;
    var avail    = assets.filter(function(a) { return a.status === 'Available'; }).length;
    var maint    = assets.filter(function(a) { return a.status === 'Maintenance'; }).length;
    var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
    set('totalCount',     total);
    set('inUseCount',     inUse);
    set('inUsePct',       total ? Math.round(inUse / total * 100) + '% total' : '0% total');
    set('availableCount', avail);
    set('maintenanceCount', maint);
  }

  function renderDashTable(filter) {
    if (!assetTableBody) return;
    var filtered = assets.filter(function(a) {
      return a.assetId.toLowerCase().includes(filter)     ||
             a.description.toLowerCase().includes(filter) ||
             a.department.toLowerCase().includes(filter)  ||
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

  function viewQRById(id) {
    var asset = assets.find(function(a) { return a._id === id; });
    if (asset) showQRModal(asset);
  }

  function editRow(id) {
    var asset = assets.find(function(a) { return a._id === id; });
    if (!asset) return;
    var set = function(elId, val) { var el = document.getElementById(elId); if (el) el.value = val; };
    set('editAssetId',    asset.assetId);
    set('editQrCode',     asset.qrCode);
    set('editDescription', asset.description);
    set('editDepartment', asset.department);
    set('editStatus',     asset.status);
    document.getElementById('editModal').setAttribute('data-edit-id', id);
    openModal('editModal');
  }

  function deleteRow(id) {
    if (!confirm('Delete this asset?')) return;
    assets = assets.filter(function(a) { return a._id !== id; });
    renderDashTable(searchInput ? searchInput.value.toLowerCase() : '');
    updateDashStats();
    showToast('🗑 Asset deleted.');
  }
}


// ============================================================
//  ASSETS PAGE
// ============================================================
if (PAGE === 'assets') {

  var assetsOpenModalBtn = document.getElementById('assetsOpenModalBtn');
  var assetsCancelBtn    = document.getElementById('assetsCancelBtn');
  var assetsSaveBtn      = document.getElementById('assetsSaveBtn');
  var assetsTableBody    = document.getElementById('assetsTableBody');
  var assetsSearchInput  = document.getElementById('assetsSearchInput');
  var cancelEditBtn      = document.getElementById('cancelEditBtn');
  var saveEditBtn        = document.getElementById('saveEditBtn');
  var closeQrViewBtn     = document.getElementById('closeQrViewBtn');
  var downloadQrBtn      = document.getElementById('downloadQrBtn');

  if (assetsOpenModalBtn) {
    assetsOpenModalBtn.addEventListener('click', function() {
      var qr = document.getElementById('assetsQrCode');
      if (qr) qr.value = generateQRValue();
      openModal('assetsModalOverlay');
      var f = document.getElementById('assetsAssetId');
      if (f) f.focus();
    });
  }

  if (assetsCancelBtn) {
    assetsCancelBtn.addEventListener('click', function() {
      closeModal('assetsModalOverlay');
      assetsClearForm();
    });
  }

  if (assetsSaveBtn)   assetsSaveBtn.addEventListener('click',   assetsSave);
  if (cancelEditBtn)   cancelEditBtn.addEventListener('click',   function() { closeModal('editModal'); });
  if (saveEditBtn)     saveEditBtn.addEventListener('click',     assetsSaveEdit);
  if (closeQrViewBtn)  closeQrViewBtn.addEventListener('click',  function() { closeModal('qrViewModal'); });
  if (downloadQrBtn)   downloadQrBtn.addEventListener('click',   downloadQR);

  if (assetsSearchInput) {
    assetsSearchInput.addEventListener('input', function() {
      renderAssetsTable(this.value.toLowerCase(), getActiveTab());
    });
  }

  hookFilterTabs(function(tab) {
    renderAssetsTable(assetsSearchInput ? assetsSearchInput.value.toLowerCase() : '', tab);
  });

  function assetsClearForm() {
    ['assetsAssetId','assetsDescription','assetsSerialNumber','assetsLocation'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.value = '';
    });
    ['assetsDepartment','assetsStatus','assetsCategory'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.selectedIndex = 0;
    });
    var qr = document.getElementById('assetsQrCode'); if (qr) qr.value = '';
    var cer = document.getElementById('assetsCertified'); if (cer) cer.checked = false;
  }

  function assetsSave() {
    var assetId  = document.getElementById('assetsAssetId')    ? document.getElementById('assetsAssetId').value.trim()    : '';
    var desc     = document.getElementById('assetsDescription') ? document.getElementById('assetsDescription').value.trim(): '';
    var dept     = document.getElementById('assetsDepartment') ? document.getElementById('assetsDepartment').value        : '';
    var status   = document.getElementById('assetsStatus')     ? document.getElementById('assetsStatus').value            : 'Available';
    var qrCode   = document.getElementById('assetsQrCode')     ? document.getElementById('assetsQrCode').value.trim()    : '';
    var serial   = document.getElementById('assetsSerialNumber') ? document.getElementById('assetsSerialNumber').value.trim() : '';
    var category = document.getElementById('assetsCategory')   ? document.getElementById('assetsCategory').value         : '';
    var location = document.getElementById('assetsLocation')   ? document.getElementById('assetsLocation').value.trim()  : '';
    var certified= document.getElementById('assetsCertified')  ? document.getElementById('assetsCertified').checked      : false;

    if (!assetId || !desc || !dept) { showToast('⚠ Please fill in all required fields.'); return; }
    if (!qrCode) qrCode = generateQRValue();

    var newAsset = {
      _id: idCounter++, assetId: assetId, qrCode: qrCode, description: desc,
      department: dept, status: status, updated: formatDate(new Date().toISOString().split('T')[0]),
      serialNumber: serial, category: category, location: location, certified: certified
    };
    assets.push(newAsset);

    closeModal('assetsModalOverlay');
    assetsClearForm();
    renderAssetsTable('', getActiveTab());
    showToast('✓ Asset added! QR code generated.');
    setTimeout(function() { showQRModal(newAsset); }, 400);
  }

  function assetsSaveEdit() {
    var modal = document.getElementById('editModal');
    var id    = parseInt(modal.getAttribute('data-edit-id'));
    var asset = assets.find(function(a) { return a._id === id; });
    if (!asset) return;

    var assetId  = document.getElementById('editAssetId')     ? document.getElementById('editAssetId').value.trim()     : '';
    var desc     = document.getElementById('editDescription')  ? document.getElementById('editDescription').value.trim() : '';
    var dept     = document.getElementById('editDepartment')  ? document.getElementById('editDepartment').value         : '';
    var status   = document.getElementById('editStatus')      ? document.getElementById('editStatus').value             : 'Available';
    var serial   = document.getElementById('editSerialNumber') ? document.getElementById('editSerialNumber').value.trim(): '';
    var category = document.getElementById('editCategory')    ? document.getElementById('editCategory').value           : '';
    var location = document.getElementById('editLocation')    ? document.getElementById('editLocation').value.trim()    : '';
    var certified= document.getElementById('editCertified')   ? document.getElementById('editCertified').checked        : false;

    if (!assetId || !desc || !dept) { showToast('⚠ Please fill in all required fields.'); return; }

    asset.assetId = assetId; asset.description = desc; asset.department = dept;
    asset.status = status; asset.serialNumber = serial; asset.category = category;
    asset.location = location; asset.certified = certified;
    asset.updated = formatDate(new Date().toISOString().split('T')[0]);

    closeModal('editModal');
    renderAssetsTable(assetsSearchInput ? assetsSearchInput.value.toLowerCase() : '', getActiveTab());
    showToast('✓ Asset updated!');
  }

  function renderAssetsTable(filter, tabFilter) {
    if (!assetsTableBody) return;
    filter    = filter    || '';
    tabFilter = tabFilter || 'ALL ASSETS';

    var filtered = assets.filter(function(a) {
      var s = filter;
      var match = a.assetId.toLowerCase().includes(s)     || a.description.toLowerCase().includes(s)  ||
                  a.department.toLowerCase().includes(s)  || a.qrCode.toLowerCase().includes(s)       ||
                  (a.serialNumber||'').toLowerCase().includes(s) || (a.category||'').toLowerCase().includes(s) ||
                  (a.location||'').toLowerCase().includes(s);
      var tab = true;
      if (tabFilter === 'AVAILABLE')   tab = a.status === 'Available';
      if (tabFilter === 'IN USE')      tab = a.status === 'In Use';
      if (tabFilter === 'MAINTENANCE') tab = a.status === 'Maintenance';
      if (tabFilter === 'CERTIFIED')   tab = a.certified === true;
      return match && tab;
    }).slice().reverse();

    if (!filtered.length) {
      assetsTableBody.innerHTML = '<tr class="empty-row"><td colspan="10">No assets to display.</td></tr>';
      return;
    }

    assetsTableBody.innerHTML = filtered.map(function(a) {
      var cert = a.certified
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
        '<td>' + cert + '</td>' +
        '<td>' + actionBtns(a._id) + '</td>' +
      '</tr>';
    }).join('');
  }

  function viewQRById(id) {
    var asset = assets.find(function(a) { return a._id === id; });
    if (asset) showQRModal(asset);
  }

  function editRow(id) {
    var asset = assets.find(function(a) { return a._id === id; });
    if (!asset) return;
    var set = function(elId, val) { var el = document.getElementById(elId); if (el) el.value = val; };
    set('editAssetId',    asset.assetId);
    set('editQrCode',     asset.qrCode);
    set('editDescription', asset.description);
    set('editDepartment', asset.department);
    set('editStatus',     asset.status);
    set('editSerialNumber', asset.serialNumber || '');
    set('editCategory',   asset.category || '');
    set('editLocation',   asset.location || '');
    var cer = document.getElementById('editCertified'); if (cer) cer.checked = asset.certified || false;
    document.getElementById('editModal').setAttribute('data-edit-id', id);
    openModal('editModal');
  }

  function deleteRow(id) {
    if (!confirm('Delete this asset?')) return;
    assets = assets.filter(function(a) { return a._id !== id; });
    renderAssetsTable(assetsSearchInput ? assetsSearchInput.value.toLowerCase() : '', getActiveTab());
    showToast('🗑 Asset deleted.');
  }
}


// ============================================================
//  BORROW / RETURN PAGE
// ============================================================
if (PAGE === 'borrow') {

  var borrows       = [];
  var borrowCounter = 1;

  var borrowOpenModalBtn = document.getElementById('borrowOpenModalBtn');
  var borrowCancelBtn    = document.getElementById('borrowCancelBtn');
  var borrowSaveBtn      = document.getElementById('borrowSaveBtn');
  var borrowTableBody    = document.getElementById('borrowTableBody');
  var borrowSearchInput  = document.getElementById('borrowSearchInput');
  var closeViewBorrowBtn = document.getElementById('closeViewBorrowBtn');

  if (borrowOpenModalBtn) {
    borrowOpenModalBtn.addEventListener('click', function() {
      openModal('borrowModalOverlay');
      var f = document.getElementById('borrowerName'); if (f) f.focus();
    });
  }

  if (borrowCancelBtn) {
    borrowCancelBtn.addEventListener('click', function() {
      closeModal('borrowModalOverlay');
      clearBorrowForm();
    });
  }

  if (borrowSaveBtn)      borrowSaveBtn.addEventListener('click',      saveBorrowRequest);
  if (closeViewBorrowBtn) closeViewBorrowBtn.addEventListener('click', function() { closeModal('viewBorrowModal'); });

  if (borrowSearchInput) {
    borrowSearchInput.addEventListener('input', function() {
      renderBorrowTable(this.value.toLowerCase(), getActiveTab());
    });
  }

  hookFilterTabs(function(tab) {
    renderBorrowTable(borrowSearchInput ? borrowSearchInput.value.toLowerCase() : '', tab);
  });

  function clearBorrowForm() {
    ['borrowerName','borrowAssetId','borrowDate','dueDate','borrowPurpose'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.value = '';
    });
    var d = document.getElementById('borrowDepartment'); if (d) d.selectedIndex = 0;
  }

  function saveBorrowRequest() {
    var borrowerName = document.getElementById('borrowerName')    ? document.getElementById('borrowerName').value.trim()    : '';
    var assetId      = document.getElementById('borrowAssetId')   ? document.getElementById('borrowAssetId').value.trim()   : '';
    var department   = document.getElementById('borrowDepartment')? document.getElementById('borrowDepartment').value       : '';
    var borrowDate   = document.getElementById('borrowDate')      ? document.getElementById('borrowDate').value             : '';
    var dueDate      = document.getElementById('dueDate')         ? document.getElementById('dueDate').value                : '';
    var purpose      = document.getElementById('borrowPurpose')   ? document.getElementById('borrowPurpose').value.trim()  : '';

    if (!borrowerName || !assetId || !department || !borrowDate || !dueDate) {
      showToast('⚠ Please fill in all required fields.'); return;
    }

    borrows.push({
      _id:          borrowCounter,
      requestId:    'REQ-' + String(borrowCounter).padStart(4, '0'),
      borrowerName: borrowerName,
      assetId:      assetId,
      department:   department,
      borrowDate:   formatDate(borrowDate),
      dueDate:      formatDate(dueDate),
      dueDateRaw:   dueDate,
      purpose:      purpose,
      status:       'Pending',
      returned:     null
    });
    borrowCounter++;

    closeModal('borrowModalOverlay');
    clearBorrowForm();
    renderBorrowTable('', getActiveTab());
    updateBorrowStats();
    showToast('✓ Borrow request submitted!');
  }

  function isOverdue(b) {
    if (b.status === 'Returned') return false;
    return new Date(b.dueDateRaw) < new Date();
  }

  function borrowBadgeClass(status) {
    return { 'Active': 'in-use', 'Pending': 'pending', 'Overdue': 'overdue', 'Returned': 'returned' }[status] || 'pending';
  }

  function approveBorrow(id) {
    var b = borrows.find(function(x) { return x._id === id; });
    if (b) { b.status = 'Active'; renderBorrowTable('', getActiveTab()); updateBorrowStats(); showToast('✓ Request approved!'); }
  }

  function returnBorrow(id) {
    var b = borrows.find(function(x) { return x._id === id; });
    if (b) {
      b.status   = 'Returned';
      b.returned = formatDate(new Date().toISOString().split('T')[0]);
      renderBorrowTable('', getActiveTab()); updateBorrowStats(); showToast('✓ Asset returned!');
    }
  }

  function rejectBorrow(id) {
    if (!confirm('Reject this borrow request?')) return;
    borrows = borrows.filter(function(x) { return x._id !== id; });
    renderBorrowTable('', getActiveTab()); updateBorrowStats(); showToast('🗑 Request rejected.');
  }

  function viewBorrow(id) {
    var b = borrows.find(function(x) { return x._id === id; });
    if (!b) return;
    var set = function(elId, val) { var el = document.getElementById(elId); if (el) el.textContent = val; };
    set('viewReqId',     b.requestId);
    set('viewBorrower',  b.borrowerName);
    set('viewDept',      b.department);
    set('viewAsset',     b.assetId);
    set('viewBorrowDate', b.borrowDate);
    set('viewDueDate',   b.dueDate);
    set('viewPurpose',   b.purpose || '—');
    var statusEl = document.getElementById('viewStatus');
    if (statusEl) { statusEl.textContent = b.status; statusEl.className = 'badge ' + borrowBadgeClass(b.status); }
    openModal('viewBorrowModal');
  }

  function updateBorrowStats() {
    var now      = new Date();
    var active   = borrows.filter(function(b) { return b.status === 'Active'; }).length;
    var pending  = borrows.filter(function(b) { return b.status === 'Pending'; }).length;
    var overdue  = borrows.filter(function(b) { return isOverdue(b); }).length;
    var returned = borrows.filter(function(b) {
      if (b.status !== 'Returned' || !b.returned) return false;
      var d = new Date(b.returned); return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }).length;
    var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
    set('statActiveBorrows', active);
    set('statPendingReqs',   pending);
    set('statOverdue',       overdue);
    set('statReturnedMonth', returned);
  }

  function renderBorrowTable(filter, tabFilter) {
    if (!borrowTableBody) return;
    filter    = filter    || '';
    tabFilter = tabFilter || 'ALL';

    var filtered = borrows.filter(function(b) {
      if (b.status !== 'Returned' && isOverdue(b)) b.status = 'Overdue';
      var match = b.requestId.toLowerCase().includes(filter)    ||
                  b.borrowerName.toLowerCase().includes(filter) ||
                  b.assetId.toLowerCase().includes(filter)      ||
                  b.department.toLowerCase().includes(filter);
      var tab = true;
      if (tabFilter === 'ACTIVE')   tab = b.status === 'Active';
      if (tabFilter === 'PENDING')  tab = b.status === 'Pending';
      if (tabFilter === 'OVERDUE')  tab = b.status === 'Overdue';
      if (tabFilter === 'RETURNED') tab = b.status === 'Returned';
      return match && tab;
    }).slice().reverse();

    if (!filtered.length) {
      borrowTableBody.innerHTML = '<tr class="empty-row"><td colspan="8">No borrow requests to display.</td></tr>';
      return;
    }

    borrowTableBody.innerHTML = filtered.map(function(b) {
      var actions = '<button class="view-btn" onclick="viewBorrow(' + b._id + ')">View</button> ';
      if (b.status === 'Pending') {
        actions += '<button class="edit-btn" onclick="approveBorrow(' + b._id + ')">Approve</button> ';
        actions += '<button class="del-btn"  onclick="rejectBorrow(' + b._id + ')">Reject</button>';
      } else if (b.status === 'Active' || b.status === 'Overdue') {
        actions += '<button class="return-btn" onclick="returnBorrow(' + b._id + ')">Return</button>';
      }
      return '<tr>' +
        '<td><strong>' + b.requestId + '</strong></td>' +
        '<td>' + b.borrowerName + '</td>' +
        '<td>' + b.assetId + '</td>' +
        '<td>' + b.department + '</td>' +
        '<td>' + b.borrowDate + '</td>' +
        '<td>' + b.dueDate + '</td>' +
        '<td><span class="badge ' + borrowBadgeClass(b.status) + '">' + b.status + '</span></td>' +
        '<td>' + actions + '</td>' +
      '</tr>';
    }).join('');
  }
}


// ============================================================
//  MAINTENANCE PAGE
// ============================================================
if (PAGE === 'maintenance') {

  var maintenances      = [];
  var maintCounter      = 1;

  var maintOpenModalBtn = document.getElementById('maintOpenModalBtn');
  var maintCancelBtn    = document.getElementById('maintCancelBtn');
  var maintSaveBtn      = document.getElementById('maintSaveBtn');
  var maintTableBody    = document.getElementById('maintTableBody');
  var maintSearchInput  = document.getElementById('maintSearchInput');
  var closeViewMaintBtn = document.getElementById('closeViewMaintBtn');

  if (maintOpenModalBtn) {
    maintOpenModalBtn.addEventListener('click', function() {
      openModal('maintModalOverlay');
      var f = document.getElementById('maintAssetId'); if (f) f.focus();
    });
  }

  if (maintCancelBtn) {
    maintCancelBtn.addEventListener('click', function() {
      closeModal('maintModalOverlay');
      clearMaintForm();
    });
  }

  if (maintSaveBtn)      maintSaveBtn.addEventListener('click',      saveMaintRequest);
  if (closeViewMaintBtn) closeViewMaintBtn.addEventListener('click', function() { closeModal('viewMaintModal'); });

  if (maintSearchInput) {
    maintSearchInput.addEventListener('input', function() {
      renderMaintTable(this.value.toLowerCase(), getActiveTab());
    });
  }

  hookFilterTabs(function(tab) {
    renderMaintTable(maintSearchInput ? maintSearchInput.value.toLowerCase() : '', tab);
  });

  function clearMaintForm() {
    ['maintAssetId','maintDescription','maintTechnician','maintScheduledDate','maintNotes'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.value = '';
    });
    var t = document.getElementById('maintType'); if (t) t.selectedIndex = 0;
  }

  function isMaintOverdue(m) {
    if (m.status === 'Completed') return false;
    return new Date(m.scheduledRaw) < new Date();
  }

  function maintBadgeClass(status) {
    return { 'Pending': 'pending', 'In Progress': 'in-use', 'Completed': 'available', 'Overdue': 'overdue' }[status] || 'pending';
  }

  function saveMaintRequest() {
    var assetId  = document.getElementById('maintAssetId')       ? document.getElementById('maintAssetId').value.trim()      : '';
    var desc     = document.getElementById('maintDescription')   ? document.getElementById('maintDescription').value.trim()  : '';
    var type     = document.getElementById('maintType')          ? document.getElementById('maintType').value                : '';
    var tech     = document.getElementById('maintTechnician')    ? document.getElementById('maintTechnician').value.trim()   : '';
    var sched    = document.getElementById('maintScheduledDate') ? document.getElementById('maintScheduledDate').value       : '';
    var notes    = document.getElementById('maintNotes')         ? document.getElementById('maintNotes').value.trim()        : '';

    if (!assetId || !desc || !type || !sched) { showToast('⚠ Please fill in all required fields.'); return; }

    maintenances.push({
      _id:           maintCounter,
      requestId:     'MNT-' + String(maintCounter).padStart(4, '0'),
      assetId:       assetId,
      description:   desc,
      type:          type,
      technician:    tech || '—',
      scheduledDate: formatDate(sched),
      scheduledRaw:  sched,
      notes:         notes,
      status:        'Pending',
      completedDate: null
    });
    maintCounter++;

    closeModal('maintModalOverlay');
    clearMaintForm();
    renderMaintTable('', getActiveTab());
    updateMaintStats();
    showToast('✓ Maintenance request created!');
  }

  function startMaint(id) {
    var m = maintenances.find(function(x) { return x._id === id; });
    if (m) { m.status = 'In Progress'; renderMaintTable('', getActiveTab()); updateMaintStats(); showToast('Maintenance started.'); }
  }

  function completeMaint(id) {
    var m = maintenances.find(function(x) { return x._id === id; });
    if (m) {
      m.status = 'Completed';
      m.completedDate = formatDate(new Date().toISOString().split('T')[0]);
      renderMaintTable('', getActiveTab()); updateMaintStats(); showToast('✓ Maintenance completed!');
    }
  }

  function deleteMaint(id) {
    if (!confirm('Delete this record?')) return;
    maintenances = maintenances.filter(function(x) { return x._id !== id; });
    renderMaintTable('', getActiveTab()); updateMaintStats(); showToast('🗑 Record deleted.');
  }

  function viewMaint(id) {
    var m = maintenances.find(function(x) { return x._id === id; });
    if (!m) return;
    var set = function(elId, val) { var el = document.getElementById(elId); if (el) el.textContent = val; };
    set('vmReqId',     m.requestId);
    set('vmAssetId',   m.assetId);
    set('vmDesc',      m.description);
    set('vmType',      m.type);
    set('vmTech',      m.technician);
    set('vmSched',     m.scheduledDate);
    set('vmNotes',     m.notes || '—');
    set('vmCompleted', m.completedDate || '—');
    var statusEl = document.getElementById('vmStatus');
    if (statusEl) { statusEl.textContent = m.status; statusEl.className = 'badge ' + maintBadgeClass(m.status); }
    openModal('viewMaintModal');
  }

  function updateMaintStats() {
    var now      = new Date();
    var pending  = maintenances.filter(function(m) { return m.status === 'Pending'; }).length;
    var inProg   = maintenances.filter(function(m) { return m.status === 'In Progress'; }).length;
    var overdue  = maintenances.filter(function(m) { return isMaintOverdue(m); }).length;
    var completed= maintenances.filter(function(m) {
      if (m.status !== 'Completed' || !m.completedDate) return false;
      var d = new Date(m.completedDate); return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }).length;
    var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
    set('maintStatPending',    pending);
    set('maintStatInProgress', inProg);
    set('maintStatCompleted',  completed);
    set('maintStatOverdue',    overdue);
  }

  function renderMaintTable(filter, tabFilter) {
    if (!maintTableBody) return;
    filter    = filter    || '';
    tabFilter = tabFilter || 'ALL';

    var filtered = maintenances.filter(function(m) {
      if (m.status !== 'Completed' && isMaintOverdue(m)) m.status = 'Overdue';
      var match = m.requestId.toLowerCase().includes(filter)   ||
                  m.assetId.toLowerCase().includes(filter)     ||
                  m.description.toLowerCase().includes(filter) ||
                  m.technician.toLowerCase().includes(filter)  ||
                  m.type.toLowerCase().includes(filter);
      var tab = true;
      if (tabFilter === 'PENDING')     tab = m.status === 'Pending';
      if (tabFilter === 'IN PROGRESS') tab = m.status === 'In Progress';
      if (tabFilter === 'COMPLETED')   tab = m.status === 'Completed';
      if (tabFilter === 'OVERDUE')     tab = m.status === 'Overdue';
      return match && tab;
    }).slice().reverse();

    if (!filtered.length) {
      maintTableBody.innerHTML = '<tr class="empty-row"><td colspan="8">No maintenance records to display.</td></tr>';
      return;
    }

    maintTableBody.innerHTML = filtered.map(function(m) {
      var actions = '<button class="view-btn" onclick="viewMaint(' + m._id + ')">View</button> ';
      if (m.status === 'Pending') {
        actions += '<button class="edit-btn" onclick="startMaint(' + m._id + ')">Start</button> ';
        actions += '<button class="del-btn"  onclick="deleteMaint(' + m._id + ')">Delete</button>';
      } else if (m.status === 'In Progress' || m.status === 'Overdue') {
        actions += '<button class="return-btn" onclick="completeMaint(' + m._id + ')">Complete</button>';
      }
      return '<tr>' +
        '<td><strong>' + m.requestId + '</strong></td>' +
        '<td>' + m.assetId + '</td>' +
        '<td>' + m.description + '</td>' +
        '<td>' + m.type + '</td>' +
        '<td>' + m.technician + '</td>' +
        '<td>' + m.scheduledDate + '</td>' +
        '<td><span class="badge ' + maintBadgeClass(m.status) + '">' + m.status + '</span></td>' +
        '<td>' + actions + '</td>' +
      '</tr>';
    }).join('');
  }
}


// ============================================================
//  REPORTS PAGE
// ============================================================
if (PAGE === 'reports') {

  var reportsList    = [];
  var reportCounter  = 1;
  var scheduledList  = [];
  var totalDownloads = 0;

  var customReportBtn       = document.getElementById('customReportBtn');
  var scheduleReportBtn     = document.getElementById('scheduleReportBtn');
  var cancelCustomReportBtn = document.getElementById('cancelCustomReportBtn');
  var saveCustomReportBtn   = document.getElementById('saveCustomReportBtn');
  var cancelScheduleBtn     = document.getElementById('cancelScheduleBtn');
  var saveScheduleBtn       = document.getElementById('saveScheduleBtn');
  var closePreviewBtn       = document.getElementById('closePreviewBtn');
  var downloadReportBtn     = document.getElementById('downloadReportBtn');
  var reportsTableBody      = document.getElementById('reportsTableBody');

  if (customReportBtn) {
    customReportBtn.addEventListener('click', function() {
      openModal('customReportModal');
      var f = document.getElementById('reportName'); if (f) f.focus();
    });
  }

  if (scheduleReportBtn) {
    scheduleReportBtn.addEventListener('click', function() {
      openModal('scheduleReportModal');
      var f = document.getElementById('schedReportName'); if (f) f.focus();
    });
  }

  if (cancelCustomReportBtn) {
    cancelCustomReportBtn.addEventListener('click', function() {
      closeModal('customReportModal'); clearCustomReportForm();
    });
  }

  if (cancelScheduleBtn) {
    cancelScheduleBtn.addEventListener('click', function() {
      closeModal('scheduleReportModal'); clearScheduleForm();
    });
  }

  if (saveCustomReportBtn) saveCustomReportBtn.addEventListener('click', saveCustomReport);
  if (saveScheduleBtn)     saveScheduleBtn.addEventListener('click',     saveSchedule);

  if (closePreviewBtn) {
    closePreviewBtn.addEventListener('click', function() { closeModal('reportPreviewModal'); });
  }

  if (downloadReportBtn) {
    downloadReportBtn.addEventListener('click', function() {
      totalDownloads++;
      updateReportStats();
      showToast('Report downloaded!');
      closeModal('reportPreviewModal');
    });
  }

  function clearCustomReportForm() {
    ['reportName','reportDateFrom','reportDateTo'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.value = '';
    });
    ['reportType','reportFormat','reportDepartment'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.selectedIndex = 0;
    });
  }

  function clearScheduleForm() {
    ['schedReportName','schedStartDate'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.value = '';
    });
    ['schedReportType','schedFrequency','schedFormat'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.selectedIndex = 0;
    });
  }

  function saveCustomReport() {
    var name   = document.getElementById('reportName')       ? document.getElementById('reportName').value.trim()   : '';
    var type   = document.getElementById('reportType')       ? document.getElementById('reportType').value           : '';
    var format = document.getElementById('reportFormat')     ? document.getElementById('reportFormat').value         : 'PDF';
    var dept   = document.getElementById('reportDepartment') ? document.getElementById('reportDepartment').value     : 'All';

    if (!name || !type) { showToast('Please fill in all required fields.'); return; }

    var report = {
      _id: reportCounter++, name: name, type: type,
      generatedBy: 'Staff User',
      date: formatDate(new Date().toISOString().split('T')[0]),
      format: format, dept: dept
    };
    reportsList.unshift(report);

    closeModal('customReportModal');
    clearCustomReportForm();
    renderReportsTable();
    updateReportStats();
    showToast('Report generated successfully!');
    setTimeout(function() { showReportPreview(report); }, 400);
  }

  function saveSchedule() {
    var name      = document.getElementById('schedReportName') ? document.getElementById('schedReportName').value.trim() : '';
    var type      = document.getElementById('schedReportType') ? document.getElementById('schedReportType').value        : '';
    var frequency = document.getElementById('schedFrequency')  ? document.getElementById('schedFrequency').value         : '';
    var startDate = document.getElementById('schedStartDate')  ? document.getElementById('schedStartDate').value         : '';

    if (!name || !type || !frequency || !startDate) { showToast('Please fill in all required fields.'); return; }

    scheduledList.push({ name: name, type: type, frequency: frequency, startDate: formatDate(startDate) });
    closeModal('scheduleReportModal');
    clearScheduleForm();
    updateReportStats();
    showToast('Report scheduled: ' + frequency + ' starting ' + formatDate(startDate));
  }

  function showReportPreview(report) {
    var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
    set('previewReportTitle', report.name);
    set('previewReportName',  report.type + ' Report');
    set('previewReportMeta',  'Generated on ' + report.date + ' · ' + report.format + ' format · ' + report.dept);
    openModal('reportPreviewModal');
  }

  window.generateReport = function(templateName) {
    var report = {
      _id: reportCounter++, name: templateName, type: 'Template',
      generatedBy: 'Staff User',
      date: formatDate(new Date().toISOString().split('T')[0]),
      format: 'PDF', dept: 'All'
    };
    reportsList.unshift(report);
    renderReportsTable();
    updateReportStats();
    showToast('Report generated: ' + templateName);
    setTimeout(function() { showReportPreview(report); }, 400);
  };

  function viewReport(id) {
    var report = reportsList.find(function(r) { return r._id === id; });
    if (report) showReportPreview(report);
  }

  function deleteReport(id) {
    if (!confirm('Delete this report?')) return;
    reportsList = reportsList.filter(function(r) { return r._id !== id; });
    renderReportsTable();
    updateReportStats();
    showToast('Report deleted.');
  }

  function updateReportStats() {
    var now = new Date();
    var generated = reportsList.filter(function(r) {
      var d = new Date(r.date); return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }).length;
    var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
    set('statReportsGenerated', generated);
    set('statScheduled',        scheduledList.length);
    set('statDownloads',        totalDownloads);
  }

  function renderReportsTable() {
    if (!reportsTableBody) return;

    if (!reportsList.length) {
      reportsTableBody.innerHTML = '<tr class="empty-row"><td colspan="6">No reports to display.</td></tr>';
      return;
    }

    reportsTableBody.innerHTML = reportsList.map(function(r) {
      var fmtBadge = r.format === 'PDF'
        ? '<span class="badge" style="background:#fee2e2;color:#b91c1c;">PDF</span>'
        : r.format === 'Excel'
        ? '<span class="badge" style="background:#dcfce7;color:#15803d;">Excel</span>'
        : '<span class="badge" style="background:#e0f2fe;color:#0369a1;">CSV</span>';
      return '<tr>' +
        '<td><strong>' + r.name + '</strong></td>' +
        '<td>' + r.type + '</td>' +
        '<td>' + r.generatedBy + '</td>' +
        '<td>' + r.date + '</td>' +
        '<td>' + fmtBadge + '</td>' +
        '<td>' +
          '<button class="view-btn" onclick="viewReport(' + r._id + ')">View</button> ' +
          '<button class="del-btn"  onclick="deleteReport(' + r._id + ')">Delete</button>' +
        '</td>' +
      '</tr>';
    }).join('');
  }
}


// ============================================================
//  DEPARTMENTS PAGE
// ============================================================
if (PAGE === 'departments') {

  var departments   = [];

  var addDeptBtn    = document.getElementById('addDeptBtn');
  var closeDeptBtn  = document.getElementById('closeDeptModal');
  var cancelDeptBtn = document.getElementById('cancelDeptModal');
  var deptForm      = document.getElementById('deptForm');
  var deptTableBody = document.getElementById('deptTableBody');
  var deptSearch    = document.getElementById('deptSearch');
  var deptFormError = document.getElementById('deptFormError');

  if (addDeptBtn) {
    addDeptBtn.addEventListener('click', function() {
      if (deptForm) deptForm.reset();
      if (deptFormError) deptFormError.textContent = '';
      openModal('deptModal');
    });
  }

  if (closeDeptBtn) {
    closeDeptBtn.addEventListener('click', function() { closeModal('deptModal'); });
  }

  if (cancelDeptBtn) {
    cancelDeptBtn.addEventListener('click', function() { closeModal('deptModal'); });
  }

  if (deptForm) {
    deptForm.addEventListener('submit', function(e) {
      e.preventDefault();
      if (deptFormError) deptFormError.textContent = '';

      var deptName    = document.getElementById('field-deptName')    ? document.getElementById('field-deptName').value.trim()    : '';
      var building    = document.getElementById('field-building')    ? document.getElementById('field-building').value.trim()    : '';
      var location    = document.getElementById('field-location')    ? document.getElementById('field-location').value.trim()    : '';
      var deptHead    = document.getElementById('field-deptHead')    ? document.getElementById('field-deptHead').value.trim()    : '';
      var custodian   = document.getElementById('field-custodian')   ? document.getElementById('field-custodian').value.trim()   : '';
      var totalAssets = document.getElementById('field-totalAssets') ? parseInt(document.getElementById('field-totalAssets').value) || 0 : 0;
      var status      = document.getElementById('field-deptStatus')  ? document.getElementById('field-deptStatus').value         : 'Active';

      if (!deptName || !building || !deptHead || !custodian) {
        if (deptFormError) deptFormError.textContent = 'Please fill in all required fields.';
        return;
      }

      departments.push({
        deptName: deptName, building: building, location: location,
        deptHead: deptHead, custodian: custodian,
        totalAssets: totalAssets, status: status
      });

      closeModal('deptModal');
      renderDeptTable();
      updateDeptStats();
      showToast('✓ ' + deptName + ' added successfully!');
    });
  }

  if (deptSearch) {
    deptSearch.addEventListener('input', function() { renderDeptTable(); });
  }

  function renderDeptTable() {
    if (!deptTableBody) return;
    var q = deptSearch ? deptSearch.value.toLowerCase().trim() : '';

    var rows = departments.filter(function(d) {
      return !q ||
        d.deptName.toLowerCase().includes(q)   ||
        d.building.toLowerCase().includes(q)   ||
        d.custodian.toLowerCase().includes(q);
    });

    if (!rows.length) {
      deptTableBody.innerHTML = '<tr class="empty-row"><td colspan="7">No departments to display.</td></tr>';
      return;
    }

    deptTableBody.innerHTML = rows.map(function(d) {
      var idx         = departments.indexOf(d);
      var fullLoc     = d.location ? d.building + ', ' + d.location : d.building;
      var statusClass = d.status === 'Active' ? 'available' : 'maintenance';
      return '<tr>' +
        '<td><strong>' + escDept(d.deptName) + '</strong></td>' +
        '<td>' + escDept(fullLoc) + '</td>' +
        '<td>' + escDept(d.deptHead) + '</td>' +
        '<td>' + escDept(d.custodian) + '</td>' +
        '<td>' + d.totalAssets + '</td>' +
        '<td><span class="badge ' + statusClass + '">' + d.status + '</span></td>' +
        '<td><div class="action-group">' +
          '<button class="edit-btn" onclick="editDept(' + idx + ')">Edit</button> ' +
          '<button class="del-btn"  onclick="deleteDept(' + idx + ')">Delete</button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  function editDept(idx) {
    var d = departments[idx];
    if (!d) return;
    var set = function(id, val) { var el = document.getElementById(id); if (el) el.value = val; };
    set('field-deptName',    d.deptName);
    set('field-building',    d.building);
    set('field-location',    d.location);
    set('field-deptHead',    d.deptHead);
    set('field-custodian',   d.custodian);
    set('field-totalAssets', d.totalAssets);
    set('field-deptStatus',  d.status);
    departments.splice(idx, 1);
    if (deptFormError) deptFormError.textContent = '';
    openModal('deptModal');
  }

  function deleteDept(idx) {
    if (!confirm('Delete ' + departments[idx].deptName + '?')) return;
    departments.splice(idx, 1);
    renderDeptTable();
    updateDeptStats();
    showToast('🗑 Department deleted.');
  }

  function updateDeptStats() {
    var buildings  = [];
    var custodians = [];
    var totalAss   = 0;
    departments.forEach(function(d) {
      if (d.building  && buildings.indexOf(d.building)   === -1) buildings.push(d.building);
      if (d.custodian && custodians.indexOf(d.custodian) === -1) custodians.push(d.custodian);
      totalAss += d.totalAssets || 0;
    });
    var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
    set('statTotal',      departments.length);
    set('statBuildings',  buildings.length);
    set('statCustodians', custodians.length);
    set('statAssets',     totalAss);
  }

  function escDept(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  renderDeptTable();
  updateDeptStats();
}