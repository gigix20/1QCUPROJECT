// SAVE MAINTENANCE
function saveMaintenance() {
  var get = function(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };

  var asset_id          = get('maintAssetId');
  var type_id           = get('maintType');
  var issue_description = get('maintIssueDescription');
  var tech_first_name   = get('maintTechFirstName');
  var tech_middle_name  = get('maintTechMiddleName');
  var tech_last_name    = get('maintTechLastName');
  var tech_suffix       = get('maintTechSuffix');
  var scheduled_date    = get('maintScheduledDate');
  var notes             = get('maintNotes');

  if (!asset_id || !type_id || !issue_description || !scheduled_date ||
      !tech_first_name || !tech_last_name) { 
      showToast('⚠ Please fill in all required fields.');
      return;
  }

  var formData = new FormData();
  formData.append('resource',          'maintenance');
  formData.append('action',            'add');
  formData.append('asset_id',          asset_id);
  formData.append('type_id',           type_id);
  formData.append('issue_description', issue_description);
  formData.append('tech_first_name',   tech_first_name);
  formData.append('tech_middle_name',  tech_middle_name);
  formData.append('tech_last_name',    tech_last_name);
  formData.append('tech_suffix',       tech_suffix);
  formData.append('scheduled_date',    scheduled_date);
  formData.append('notes',             notes);

  fetch(MAINT_API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('maintModalOverlay');
        clearMaintForm();
        loadMaintenance();
        showToast('✓ Maintenance request submitted!');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// VIEW MAINTENANCE
function viewMaint(maintenance_id) {
  var m = maintenances.find(function(x) { return x.MAINTENANCE_ID == maintenance_id; });
  if (!m) return;

  var set = function(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  var techName = [m.TECH_FIRST_NAME, m.TECH_MIDDLE_NAME, m.TECH_LAST_NAME, m.TECH_SUFFIX]
                  .filter(Boolean).join(' ') || '—';

  set('vmMaintId',      m.MAINTENANCE_ID);
  set('vmAssetId',      m.ASSET_ID          || '—');
  set('vmAssetDesc',    m.ASSET_DESCRIPTION || '—');
  set('vmDept',         m.DEPARTMENT_NAME   || '—');
  set('vmType',         m.MAINTENANCE_TYPE  || '—');
  set('vmIssue',        m.ISSUE_DESCRIPTION || '—');
  set('vmTech',         techName);
  set('vmScheduled',    formatDate(m.SCHEDULED_DATE));
  set('vmCompleted',    m.COMPLETED_DATE ? formatDate(m.COMPLETED_DATE) : '—');
  set('vmNotes',        m.NOTES             || '—');

  var statusEl = document.getElementById('vmStatus');
  if (statusEl) {
    statusEl.textContent = m.STATUS;
    statusEl.className   = 'badge ' + maintBadgeClass(m.STATUS);
  }

  openModal('viewMaintModal');
}

// COMPLETE MAINTENANCE
function completeMaint(maintenance_id) {
  var today = new Date().toISOString().split('T')[0];
  updateMaintStatus(maintenance_id, 'Completed', today);
}

// START MAINTENANCE
function startMaint(maintenance_id) {
  if (!confirm('Start this maintenance?')) return;
  updateMaintStatus(maintenance_id, 'In Progress', '');
}

// CANCEL MAINTENANCE
function cancelMaint(maintenance_id) {
  if (!confirm('Cancel this maintenance request?')) return;
  updateMaintStatus(maintenance_id, 'Cancelled', '');
}


// UPDATE MAINTENANCE STATUS
function updateMaintStatus(maintenance_id, status, completed_date) {
  var formData = new FormData();
  formData.append('resource',        'maintenance');
  formData.append('action',          'updateStatus');
  formData.append('maintenance_id',  maintenance_id);
  formData.append('status',          status);
  formData.append('completed_date',  completed_date);

  fetch(MAINT_API, { method: 'POST', body: formData })
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        loadMaintenance();
        showToast('✓ ' + status + '!');
      } else {
        showToast('⚠ ' + data.message);
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// FETCH ASSET INFO
function fetchMaintAssetInfo(asset_id) {
  var descEl = document.getElementById('maintAssetDesc');
  var deptEl = document.getElementById('maintAssetDept');

  if (!asset_id) {
    if (descEl) descEl.value = '';
    if (deptEl) deptEl.value = '';
    return;
  }

  fetch(MAINT_API + '?resource=maintenance&action=getAsset&asset_id=' + encodeURIComponent(asset_id))
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        var a = data.data;
        if (descEl) descEl.value = a.DESCRIPTION   || '—';
        if (deptEl) deptEl.value = a.DEPARTMENT_NAME|| '—';
      } else {
        if (descEl) descEl.value = '⚠ Asset not found';
        if (deptEl) deptEl.value = '—';
      }
    })
    .catch(function() {
      if (descEl) descEl.value = '⚠ Error fetching asset';
      if (deptEl) deptEl.value = '—';
    });
}