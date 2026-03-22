
// SAVE ASSET
function assetsSave() {
  var get = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var description   = get('assetsDescription');
  var serial_number = get('assetsSerialNumber');
  var category_id   = get('assetsCategory');
  var department_id = get('assetsDepartment');
  var item_type_id  = get('assetsItemType');
  var location      = get('assetsLocation');
  var status        = get('assetsStatus') || 'Available';
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
        showToast('✓ ' + generated.length + ' asset(s) added: ' + generated.join(', '));
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
      set('editItemType',     a.ITEM_TYPE_ID  || '');
      set('editLocation',     a.LOCATION      || '');
      set('editStatus',       a.STATUS);

      var editDeptEl = document.getElementById('editDepartment');
      if (editDeptEl) {
        editDeptEl.value = a.DEPARTMENT_ID || '';
        updateLiableDropdown('editDepartment', 'editLiablePerson');
      }

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
      if (contentType && contentType.includes('application/json')) return res.json();
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