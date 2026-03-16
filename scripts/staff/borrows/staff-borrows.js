// API ROUTE
var BORROW_API = '/1QCUPROJECT/backend/routes/borrows_route.php';

// BORROW STORE
var borrows = [];


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

function borrowBadgeClass(status) {
  return {
    'Pending':  'pending',
    'Borrowed': 'in-use',
    'Returned': 'available',
    'Overdue':  'overdue',
    'Cancelled':'maintenance'
  }[status] || 'pending';
}


// RENDER TABLE
function renderBorrowTable(filter, tabFilter) {
  var borrowTableBody = document.getElementById('borrowTableBody');
  if (!borrowTableBody) return;
  filter    = (filter    || '').toLowerCase();
  tabFilter =  tabFilter || 'ALL';

  var filtered = borrows.filter(function(b) {
    var match =
      (b.BORROW_ID       || '').toString().toLowerCase().includes(filter) ||
      (b.ASSET_ID        || '').toLowerCase().includes(filter) ||
      (b.DEPARTMENT_NAME || '').toLowerCase().includes(filter) ||
      (b.FIRST_NAME      || '').toLowerCase().includes(filter) ||
      (b.LAST_NAME       || '').toLowerCase().includes(filter) ||
      (b.PURPOSE         || '').toLowerCase().includes(filter);

      var tab = true;
      if (tabFilter === 'PENDING')   tab = b.STATUS === 'Pending';
      if (tabFilter === 'ACTIVE')    tab = b.STATUS === 'Borrowed';
      if (tabFilter === 'OVERDUE')   tab = b.STATUS === 'Overdue';
      if (tabFilter === 'RETURNED')  tab = b.STATUS === 'Returned';
      if (tabFilter === 'CANCELLED') tab = b.STATUS === 'Cancelled';

    return match && tab;
  });

  if (!filtered.length) {
    borrowTableBody.innerHTML = '<tr class="empty-row"><td colspan="9">No borrow requests to display.</td></tr>';
    return;
  }

  borrowTableBody.innerHTML = filtered.map(function(b) {
    var borrowerName = [b.FIRST_NAME, b.MIDDLE_NAME, b.LAST_NAME, b.SUFFIX]
                        .filter(Boolean).join(' ') || '—';
    var deptColor    = getDeptColor(b.DEPARTMENT_NAME);

    return '<tr>'                                                                                 +
      '<td><strong>' + (b.BORROW_ID       || '—') + '</strong></td>'                            +
      '<td>'         + borrowerName                + '</td>'                                     +
      '<td>'         + (b.ASSET_ID        || '—') + '</td>'                                     +
      '<td><span style="color:' + deptColor + ';font-weight:600;">'
                     + (b.DEPARTMENT_NAME || '—') + '</span></td>'                              +
      '<td>'         + (b.PURPOSE         || '—') + '</td>'                                     + // ← NEW
      '<td>'         + formatDate(b.BORROW_DATE)  + '</td>'                                     +
      '<td>'         + formatDate(b.DUE_DATE)     + '</td>'                                     +
      '<td><span class="badge ' + borrowBadgeClass(b.STATUS) + '">' + b.STATUS + '</span></td>' +
      '<td>'         + borrowActionBtns(b)         + '</td>'                                     +
    '</tr>';
  }).join('');
}

// ACTION BUTTONS
function borrowActionBtns(b) {
  var id   = b.BORROW_ID;
  var btns = '<button class="view-btn" onclick="viewBorrow(' + id + ')">View</button> ';

  if (b.STATUS === 'Pending') {
    btns += '<button class="del-btn"   onclick="cancelBorrow('  + id + ')">Cancel</button>';
  } else if (b.STATUS === 'Borrowed' || b.STATUS === 'Overdue') {
    btns += '<button class="return-btn" onclick="openReturnModal(' + id + ')">Return</button>';
  }

  return btns;
}


// LOAD DROPDOWNS
function loadBorrowDropdowns() {
  fetch(BORROW_API + '?resource=departments&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') return;
      var sel = document.getElementById('borrowDepartment');
      if (!sel) return;
      data.data.forEach(function(d) {
        var opt = '<option value="' + d.DEPARTMENT_ID + '">' + d.DEPARTMENT_NAME + '</option>';
        sel.innerHTML += opt;
      });
    })
    .catch(function() { showToast('⚠ Failed to load departments.'); });
}


// LOAD BORROWS
function loadBorrows() {
  fetch(BORROW_API + '?resource=borrows&action=getAll')
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        borrows = data.data || [];
        renderBorrowTable('', getActiveTab());
        updateBorrowStats();
      } else {
        showToast('⚠ Failed to load borrows.');
      }
    })
    .catch(function() { showToast('⚠ Error connecting to server.'); });
}


// UPDATE STATS
function updateBorrowStats() {
  var now     = new Date();
  var pending  = borrows.filter(function(b) { return b.STATUS === 'Pending';  }).length;
  var active   = borrows.filter(function(b) { return b.STATUS === 'Borrowed'; }).length;

  // Count both active overdue AND returned late
  var overdue  = borrows.filter(function(b) {
    if (b.STATUS === 'Overdue') return true;
    if (b.STATUS === 'Returned' && b.RETURN_DATE && b.DUE_DATE) {
      return b.RETURN_DATE > b.DUE_DATE; // returned after due date
    }
    return false;
  }).length;

  var returned = borrows.filter(function(b) {
    if (b.STATUS !== 'Returned' || !b.RETURN_DATE) return false;
    var d = new Date(b.RETURN_DATE);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  }).length;

  var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
  set('statPendingBorrows', pending);
  set('statActiveBorrows',  active);
  set('statOverdue',        overdue);
  set('statReturnedMonth',  returned);
}


// CLEAR FORM
function clearBorrowForm() {
  ['borrowFirstName', 'borrowMiddleName', 'borrowLastName',
   'borrowSuffix', 'borrowAssetId', 'borrowAssetDesc',
   'borrowLiablePerson', 'borrowDate', 'dueDate', 'borrowPurpose'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  var dept = document.getElementById('borrowDepartment');
  if (dept) dept.selectedIndex = 0;
}