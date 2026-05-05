var REPORT_API      = '/1QCUPROJECT/backend/routes/reports_route.php';
var REPORT_VIEW_URL = '/1QCUPROJECT/backend/routes/view_report.php';

var reportsList   = [];
var scheduledList = [];
var reportCounter = 1;
var totalDownloads= 0;
var reportDepts   = [];
var pendingTemplate = '';

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

var REPORT_ROUTE_MAP = {
  'Complete Asset Inventory':  'report_complete',
  'Asset Status Report':       'report_status',
  'Certified Assets Report':   'report_certified',
  'Overdue Items Report':      'report_overdue',
  'Maintenance Report':        'report_maintenance',
  'Asset by Department':       'report_by_dept',
  'Borrowing Activity Report': 'report_borrowing',
  'Asset Utilization Report':  'report_utilization',
  'Audit Logs Report':         'report_audit_logs'
};

var DEPT_REPORTS = [
  'Certified Assets Report',
  'Asset by Department',
  'Complete Asset Inventory',
  'Asset Status Report',
  'Maintenance Report',
  'Borrowing Activity Report',
  'Asset Utilization Report',
  'Overdue Items Report'
];

var SCOPE_REPORTS = [
  'Overdue Items Report'
];

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
      var opt         = document.createElement('option');
      opt.value       = d.department_id   || d.DEPARTMENT_ID;
      opt.textContent = d.department_name || d.DEPARTMENT_NAME;
      el.appendChild(opt);
    });
  });
}

function buildExportUrl(templateName, deptId, deptName, scope, month, year) {
  var resource = REPORT_ROUTE_MAP[templateName];
  if (!resource) return null;

  var url = REPORT_VIEW_URL + '?resource=' + resource;

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

function loadRecentReports() {
  fetch(REPORT_API + '?resource=recent_reports&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        reportsList = (data.data.reports || []).map(function(r, i) {
          return {
            _id:         i + 1,
            name:        r.report_name  || r.REPORT_NAME,
            type:        r.report_type  || r.REPORT_TYPE,
            generatedBy: r.generated_by || r.GENERATED_BY,
            date:        r.generated_at || r.GENERATED_AT,
            format:      r.format       || r.FORMAT || 'PDF',
            url:         r.file_url     || r.FILE_URL || ''
          };
        });

        var countEl = document.getElementById('statReportsGenerated');
        if (countEl) countEl.textContent = data.data.monthly_count || 0;

        var totalEl = document.getElementById('statTotalReports');
        if (totalEl) totalEl.textContent = data.data.all_time_count || 0;

        renderReportsTable();
        updateReportStats();
      }
    })
    .catch(function() { showToast('⚠ Failed to load recent reports.'); });
}

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
    .then(function()    { loadRecentReports(); })
    .catch(function() {
      reportsList.unshift({
        _id:         reportCounter++,
        name:        name,
        type:        type,
        generatedBy: 'Admin',
        date:        new Date().toISOString().replace('T', ' ').substring(0, 19),
        format:      'PDF',
        url:         url
      });
      renderReportsTable();
      updateReportStats();
    });
}

function updateReportStats() {
  var el = document.getElementById('statScheduled');
  if (el) el.textContent = scheduledList.length;
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
      + '<td><strong>' + (r.name        || '—') + '</strong></td>'
      + '<td>'         + (r.type        || '—') + '</td>'
      + '<td>'         + (r.generatedBy || '—') + '</td>'
      + '<td>'         + (r.date        || '—') + '</td>'
      + '<td><span class="badge" style="background:#fee2e2;color:#b91c1c;">PDF</span></td>'
      + '<td><button class="view-btn" onclick="viewReport(' + idx + ')">View</button></td>'
      + '</tr>';
  }).join('');
}

function viewReport(idx) {
  var r = reportsList[idx];
  if (!r) return;
  if (r.url) window.open(r.url, '_blank');
  else showToast('⚠ URL not available for this report.');
}