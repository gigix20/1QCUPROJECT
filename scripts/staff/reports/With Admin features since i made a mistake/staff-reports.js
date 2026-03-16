var REPORT_API = '/1QCUPROJECT/backend/routes/reports_route.php';

var reportsList   = [];
var scheduledList = [];
var reportCounter = 1;
var totalDownloads= 0;

// Departments cache
var reportDepts = [];

// Which template is currently pending in options modal
var pendingTemplate = '';

// UTILITIES
function showToast(msg) {
  var el = document.getElementById('toast');
  if (!el) return;
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(function() { el.classList.remove('show'); }, 3000);
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

// ROUTE MAP
// Maps template name → reports_route.php resource
var REPORT_ROUTE_MAP = {
  'Complete Asset Inventory':  'report_status',
  'Asset by Department':       'report_by_department',
  'Asset Status Report':       'report_status',
  'Certified Assets Report':   'report_certified',
  'Overdue Items Report':      'report_overdue',
  'Borrowing Activity Report': 'report_borrowing',
  'Asset Utilization Report':  'report_utilization',
  'Maintenance Report':        'report_maintenance'
};

// Reports that need a department picker
var DEPT_REPORTS = [
  'Asset by Department',
  'Certified Assets Report'
];

// Reports that need a scope picker
var SCOPE_REPORTS = [
  'Overdue Items Report'
];

// POPULATE YEAR DROPDOWNS
// Generates last 5 years + current year
function populateYearDropdowns() {
  var currentYear = new Date().getFullYear();
  var dropdownIds = ['optsYear', 'customYear'];

  dropdownIds.forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = '<option value="">All Years</option>';
    for (var y = currentYear; y >= currentYear - 4; y--) {
      var opt = document.createElement('option');
      opt.value = String(y);
      opt.textContent = String(y);
      el.appendChild(opt);
    }
  });
}

// LOAD DEPARTMENTS
function loadReportDepts() {
  fetch(REPORT_API + '?resource=departments&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        reportDepts = data.data || [];
        populateDeptDropdowns();
      }
    })
    .catch(function() { showToast('⚠ Failed to load departments.'); });
}

function populateDeptDropdowns() {
  var dropdowns = ['optsDept', 'customDept'];
  dropdowns.forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = '<option value="">All Departments</option>';
    reportDepts.forEach(function(d) {
      var opt    = document.createElement('option');
      opt.value  = d.DEPARTMENT_ID;
      opt.textContent = d.DEPARTMENT_NAME;
      el.appendChild(opt);
    });
  });
}

// BUILD EXPORT URL
function buildExportUrl(templateName, deptId, deptName, scope, month, year) {
  var resource = REPORT_ROUTE_MAP[templateName];
  if (!resource) return null;

  var url = REPORT_API + '?resource=' + resource;

  if (deptId) {
    url += '&dept_id='   + encodeURIComponent(deptId);
    url += '&dept_name=' + encodeURIComponent(deptName || 'Selected Department');
  }

  if (scope && SCOPE_REPORTS.indexOf(templateName) !== -1) {
    url += '&scope=' + encodeURIComponent(scope);
  }

  if (month) url += '&month=' + encodeURIComponent(month);
  if (year)  url += '&year='  + encodeURIComponent(year);

  return url;
}

// LOAD RECENT REPORTS FROM DB
function loadRecentReports() {
  fetch(REPORT_API + '?resource=recent_reports&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        reportsList = (data.data || []).map(function(r, i) {
          return {
            _id:         i + 1,
            name:        r.REPORT_NAME,
            type:        r.REPORT_TYPE,
            generatedBy: r.GENERATED_BY,
            date:        r.GENERATED_AT,
            format:      r.FORMAT || 'PDF',
            url:         r.FILE_URL || ''
          };
        });
        renderReportsTable();
        updateReportStats();
      }
    })
    .catch(function() { showToast('⚠ Failed to load recent reports.'); });
}

// ADD TO RECENT REPORTS (POST to DB then reload)
function addToRecentReports(name, type, url) {
  var formData = new FormData();
  formData.append('report_name', name);
  formData.append('report_type', type);
  formData.append('file_url',    url);

  fetch(REPORT_API + '?resource=save_report', {
    method: 'POST',
    body:   formData
  })
    .then(function(res) { return res.json(); })
    .then(function() { loadRecentReports(); })
    .catch(function() {
      // Fallback: add in-memory only
      reportsList.unshift({
        _id:         reportCounter++,
        name:        name,
        type:        type,
        generatedBy: 'Staff',
        date:        new Date().toISOString().replace('T', ' ').substring(0, 19),
        format:      'PDF',
        url:         url
      });
      renderReportsTable();
      updateReportStats();
    });
}

// STATS + TABLE
function updateReportStats() {
  var now  = new Date();
  var thisMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

  var generated = reportsList.filter(function(r) {
    return r.date && r.date.substring(0, 7) === thisMonth;
  }).length;

  var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
  set('statReportsGenerated', generated);
  set('statScheduled',        scheduledList.length);
  set('statDownloads',        totalDownloads);
}

function renderReportsTable() {
  var tbody = document.getElementById('reportsTableBody');
  if (!tbody) return;

  if (!reportsList.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="6">No reports generated yet.</td></tr>';
    return;
  }

  tbody.innerHTML = reportsList.map(function(r, idx) {
    return '<tr>'
      + '<td><strong>' + (r.name || '—')        + '</strong></td>'
      + '<td>'         + (r.type || '—')        + '</td>'
      + '<td>'         + (r.generatedBy || '—') + '</td>'
      + '<td>'         + (r.date || '—')        + '</td>'
      + '<td><span class="badge" style="background:#fee2e2;color:#b91c1c;">PDF</span></td>'
      + '<td>'
      +   '<button class="view-btn" onclick="viewReport(' + idx + ')">View</button> '
      +   '<button class="del-btn"  onclick="deleteReport(' + idx + ')">Delete</button>'
      + '</td>'
      + '</tr>';
  }).join('');
}

function viewReport(idx) {
  var r = reportsList[idx];
  if (!r) return;
  if (r.url) window.open(r.url, '_blank');
  else showToast('⚠ URL not available for this report.');
}

function deleteReport(idx) {
  if (!confirm('Delete this report record?')) return;
  reportsList.splice(idx, 1);
  renderReportsTable();
  updateReportStats();
  showToast('🗑 Report deleted.');
}
