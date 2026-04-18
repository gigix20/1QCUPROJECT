
// SAVE BORROW
function saveBorrow() {
  var get = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var first_name    = get('borrowFirstName');
  var middle_name   = get('borrowMiddleName');
  var last_name     = get('borrowLastName');
  var suffix        = get('borrowSuffix');
  var department_id = get('borrowDepartment');
  var asset_id      = get('borrowAssetId');
  var borrow_date   = get('borrowDate');
  var due_date      = get('dueDate');
  var purpose       = get('borrowPurpose');

  if (!first_name || !last_name || !department_id || !asset_id || !borrow_date || !due_date) {
    showToast('⚠ Please fill in all required fields.');
    return;
  }

  var formData = new FormData();
  formData.append('resource',      'borrows');
  formData.append('action',        'add');
  formData.append('first_name',    first_name);
  formData.append('middle_name',   middle_name);
  formData.append('last_name',     last_name);
  formData.append('suffix',        suffix);
  formData.append('department_id', department_id);
  formData.append('asset_id',      asset_id);
  formData.append('borrow_date',   borrow_date);
  formData.append('due_date',      due_date);
  formData.append('purpose',       purpose);

  fetch(BORROW_API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('borrowModalOverlay');
        clearBorrowForm();
        loadBorrows();
        showToast('✓ Borrow request submitted!');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}

// AUTO-FILL CURRENT DATE
var openBtn = document.getElementById('borrowOpenModalBtn');
if (openBtn) {
  openBtn.addEventListener('click', function() {
    openModal('borrowModalOverlay');
    // auto-fill today
    var borrowDateEl = document.getElementById('borrowDate');
    if (borrowDateEl) borrowDateEl.value = new Date().toISOString().split('T')[0];
    var f = document.getElementById('borrowFirstName');
    if (f) f.focus();
  });
}

// VIEW BORROW
function viewBorrow(borrow_id) {
  var b = borrows.find(function(x) { return x.BORROW_ID == borrow_id; });
  if (!b) return;

  var set = function(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  var borrowerName = [b.FIRST_NAME, b.MIDDLE_NAME, b.LAST_NAME, b.SUFFIX]
                      .filter(Boolean).join(' ') || '—';
  var liableName   = [b.LIABLE_FIRST, b.LIABLE_MIDDLE, b.LIABLE_LAST, b.LIABLE_SUFFIX]
                      .filter(Boolean).join(' ') || '—';

  set('viewReqId',       b.BORROW_ID);
  set('viewBorrower',    borrowerName);
  set('viewDept',        b.DEPARTMENT_NAME  || '—');
  set('viewAsset',       b.ASSET_ID         || '—');
  set('viewAssetDesc',   b.ASSET_DESCRIPTION|| '—');
  set('viewLiable',      liableName);
  set('viewBorrowDate',  formatDate(b.BORROW_DATE));
  set('viewDueDate',     formatDate(b.DUE_DATE));
  set('viewReturnDate',  b.RETURN_DATE ? formatDate(b.RETURN_DATE) : '—');
  set('viewPurpose',     b.PURPOSE          || '—');
  set('viewRemarks',     b.REMARKS          || '—');

  var statusEl = document.getElementById('viewStatus');
  if (statusEl) {
    statusEl.textContent = b.STATUS;
    statusEl.className   = 'badge ' + borrowBadgeClass(b.STATUS);
  }

  openModal('viewBorrowModal');
}


// OPEN RETURN MODAL
function openReturnModal(borrow_id) {
  var b = borrows.find(function(x) { return x.BORROW_ID == borrow_id; });
  if (!b) return;

  var set = function(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  set('returnModalAssetId',   b.ASSET_ID          || '—');
  set('returnModalAssetDesc', b.ASSET_DESCRIPTION || '—');
  set('returnModalDueDate',   formatDate(b.DUE_DATE));

  // Set today as default return date
  var retDateEl = document.getElementById('returnDate');
  if (retDateEl) retDateEl.value = new Date().toISOString().split('T')[0];

  // Clear remarks
  var remarksEl = document.getElementById('returnRemarks');
  if (remarksEl) remarksEl.value = '';

  // Store borrow_id on modal
  var modal = document.getElementById('returnModal');
  if (modal) modal.setAttribute('data-borrow-id', borrow_id);

  openModal('returnModal');
}


// SAVE RETURN
function saveReturn() {
  var modal     = document.getElementById('returnModal');
  var borrow_id = modal ? modal.getAttribute('data-borrow-id') : '';
  var get       = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var return_date = get('returnDate');
  var remarks     = get('returnRemarks');

  if (!return_date) {
    showToast('⚠ Please set a return date.');
    return;
  }

  var formData = new FormData();
  formData.append('resource',     'borrows');
  formData.append('action',       'return');
  formData.append('borrow_id',    borrow_id);
  formData.append('return_date',  return_date);
  formData.append('remarks',      remarks);

  fetch(BORROW_API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('returnModal');
        loadBorrows();
        showToast('✓ Asset returned successfully!');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// FETCH ASSET INFO (on Asset ID input)
function fetchAssetInfo(asset_id) {
  var descEl   = document.getElementById('borrowAssetDesc');
  var liableEl = document.getElementById('borrowLiablePerson');

  if (!asset_id) {
    if (descEl)   descEl.value   = '';
    if (liableEl) liableEl.value = '';
    return;
  }

  fetch(BORROW_API + '?resource=borrows&action=getAsset&asset_id=' + encodeURIComponent(asset_id))
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        var a      = data.data;
        var liable = [a.FIRST_NAME, a.MIDDLE_NAME, a.LAST_NAME, a.SUFFIX]
                      .filter(Boolean).join(' ') || '—';
        if (descEl)   descEl.value   = a.DESCRIPTION   || '—';
        if (liableEl) liableEl.value = liable;
      } else {
        if (descEl)   descEl.value   = '⚠ Asset not found';
        if (liableEl) liableEl.value = '—';
      }
    })
    .catch(function() {
      if (descEl)   descEl.value   = '⚠ Error fetching asset';
      if (liableEl) liableEl.value = '—';
    });
}

// CANCEL BORROW
function cancelBorrow(borrow_id) {
  if (!confirm('Cancel this borrow request?')) return;

  var formData = new FormData();
  formData.append('resource',  'borrows');
  formData.append('action',    'cancel');
  formData.append('borrow_id', borrow_id);

  fetch(BORROW_API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        loadBorrows();
        showToast('🗑 Borrow request cancelled.');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}

// APPROVE BORROW
function approveBorrow(borrow_id) {
  if (!confirm('Approve this borrow request?')) return;

  var formData = new FormData();
  formData.append('resource',  'borrows');
  formData.append('action',    'approve');
  formData.append('borrow_id', borrow_id);

  fetch(BORROW_API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        loadBorrows();
        showToast('✓ Borrow request approved.');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}