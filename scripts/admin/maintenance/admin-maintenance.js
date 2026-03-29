// API ROUTE
var MAINT_API = '/1QCUPROJECT/backend/routes/maintenance_route.php';

// MAINTENANCE STORE
var maintenances = [];


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
  return active ? active.textContent.trim() : 'ALL';
}


// BADGE CLASS
function maintBadgeClass(status) {
  return {
    'Pending':     'pending',
    'In Progress': 'in-use',
    'Completed':   'available',
    'Cancelled':   'maintenance'
  }[status] || 'pending';
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


// RENDER TABLE
function renderMaintTable(filter, tabFilter) {
  var maintTableBody = document.getElementById('maintTableBody');
  if (!maintTableBody) return;
  filter    = (filter    || '').toLowerCase();
  tabFilter =  tabFilter || 'ALL';

  var filtered = maintenances.filter(function(m) {
    var techName = [m.TECH_FIRST_NAME, m.TECH_MIDDLE_NAME, m.TECH_LAST_NAME, m.TECH_SUFFIX]
                    .filter(Boolean).join(' ');
    var match =
      (m.MAINTENANCE_ID   || '').toString().toLowerCase().includes(filter) ||
      (m.ASSET_ID         || '').toLowerCase().includes(filter)            ||
      (m.ASSET_DESCRIPTION|| '').toLowerCase().includes(filter)            ||
      (m.MAINTENANCE_TYPE || '').toLowerCase().includes(filter)            ||
      (m.DEPARTMENT_NAME  || '').toLowerCase().includes(filter)            ||
      (m.ISSUE_DESCRIPTION|| '').toLowerCase().includes(filter)            ||
      techName.toLowerCase().includes(filter);

    var tab = true;
    if (tabFilter === 'PENDING')     tab = m.STATUS === 'Pending';
    if (tabFilter === 'IN PROGRESS') tab = m.STATUS === 'In Progress';
    if (tabFilter === 'COMPLETED')   tab = m.STATUS === 'Completed';
    if (tabFilter === 'CANCELLED')   tab = m.STATUS === 'Cancelled';

    return match && tab;
  });

  if (!filtered.length) {
    maintTableBody.innerHTML = '<tr class="empty-row"><td colspan="9">No maintenance records to display.</td></tr>';
    return;
  }

  maintTableBody.innerHTML = filtered.map(function(m) {
    var techName = [m.TECH_FIRST_NAME, m.TECH_MIDDLE_NAME, m.TECH_LAST_NAME, m.TECH_SUFFIX]
                    .filter(Boolean).join(' ') || '—';
    var deptColor = getDeptColor(m.DEPARTMENT_NAME);

    return '<tr>'                                                                                    +
      '<td><strong>' + (m.MAINTENANCE_ID    || '—') + '</strong></td>'                             +
      '<td>'         + (m.ASSET_ID          || '—') + '</td>'                                      +
      '<td>'         + (m.ASSET_DESCRIPTION || '—') + '</td>'                                      +
      '<td><span style="color:' + deptColor + ';font-weight:600;">'
                     + (m.DEPARTMENT_NAME   || '—') + '</span></td>'                               +
      '<td>'         + (m.MAINTENANCE_TYPE  || '—') + '</td>'                                      +
      '<td>'         + techName                      + '</td>'                                      +
      '<td>'         + formatDate(m.SCHEDULED_DATE)  + '</td>'                                      +
      '<td>'         + (m.NOTES             || '—') + '</td>'                                      +
      '<td><span class="badge ' + maintBadgeClass(m.STATUS) + '">' + m.STATUS + '</span></td>'     +
      '<td>'         + maintActionBtns(m)            + '</td>'                                      +
    '</tr>';
  }).join('');
}


// ACTION BUTTONS
function maintActionBtns(m) {
  var id   = m.MAINTENANCE_ID;
  var btns = '<button class="view-btn" onclick="viewMaint(' + id + ')">View</button> ';

  if (m.STATUS === 'Pending') {
    btns += '<button class="del-btn" onclick="cancelMaint(' + id + ')">Cancel</button>';
  } else if (m.STATUS === 'In Progress') {
    btns += '<button class="return-btn" onclick="completeMaint(' + id + ')">Complete</button>';
  }

  return btns;
}


// LOAD DROPDOWNS
function loadMaintDropdowns() {
  fetch(MAINT_API + '?resource=maintenance_types&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var sel = document.getElementById('maintType');
      if (!sel) return;
      sel.innerHTML = '<option value="">-- Select Type --</option>';
      data.data.forEach(function(t) {
        sel.innerHTML += '<option value="' + t.TYPE_ID + '">' + t.TYPE_NAME + '</option>';
      });
    })
    .catch(function() { showToast('⚠ Failed to load maintenance types.'); });
}


// LOAD MAINTENANCE
function loadMaintenance() {
  fetch(MAINT_API + '?resource=maintenance&action=getAll')
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        maintenances = data.data || [];
        renderMaintTable('', getActiveTab());
        updateMaintStats();
      } else {
        showToast('⚠ Failed to load maintenance records.');
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// UPDATE STATS
function updateMaintStats() {
  var now         = new Date();
  var pending     = maintenances.filter(function(m) { return m.STATUS === 'Pending';     }).length;
  var in_progress = maintenances.filter(function(m) { return m.STATUS === 'In Progress'; }).length;
  var cancelled   = maintenances.filter(function(m) { return m.STATUS === 'Cancelled';   }).length;
  var completed   = maintenances.filter(function(m) {
    if (m.STATUS !== 'Completed' || !m.COMPLETED_DATE) return false;
    var d = new Date(m.COMPLETED_DATE);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  }).length;

  var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
  set('maintStatPending',    pending);
  set('maintStatInProgress', in_progress);
  set('maintStatCompleted',  completed);
  set('maintStatCancelled',  cancelled);
}


// CLEAR FORM
function clearMaintForm() {
  ['maintAssetId', 'maintAssetDesc', 'maintAssetDept',
   'maintTechFirstName', 'maintTechMiddleName',
   'maintTechLastName', 'maintTechSuffix',
   'maintScheduledDate', 'maintNotes'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  var sel = document.getElementById('maintType');
  if (sel) sel.selectedIndex = 0;
}