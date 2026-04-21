// TEMPLATE QUICK GENERATE
// Opens Report Options Modal first

function generateReport(templateName) {
  pendingTemplate = templateName;

  // Update modal title
  var title = document.getElementById('reportOptionsTitle');
  if (title) title.textContent = 'Generate: ' + templateName;

  // Show/hide dept picker
  var deptWrapper  = document.getElementById('optsDeptWrapper');
  var scopeWrapper = document.getElementById('optsScopeWrapper');

  if (deptWrapper)  deptWrapper.style.display  = DEPT_REPORTS.indexOf(templateName)  !== -1 ? 'block' : 'none';
  if (scopeWrapper) scopeWrapper.style.display = SCOPE_REPORTS.indexOf(templateName) !== -1 ? 'block' : 'none';

  // Reset selects
  var selects = ['optsDept', 'optsScope', 'optsMonth', 'optsYear'];
  selects.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.selectedIndex = 0;
  });

  openModal('reportOptionsModal');
}

// CONFIRM REPORT OPTIONS → GENERATE PDF
function confirmReportOptions() {
  var deptEl   = document.getElementById('optsDept');
  var scopeEl  = document.getElementById('optsScope');
  var monthEl  = document.getElementById('optsMonth');
  var yearEl   = document.getElementById('optsYear');

  var deptId   = deptEl  ? deptEl.value  : '';
  var deptName = deptEl  ? deptEl.options[deptEl.selectedIndex].text : 'All Departments';
  var scope    = scopeEl ? scopeEl.value : 'all';
  var month    = monthEl ? monthEl.value : '';
  var year     = yearEl  ? yearEl.value  : '';

  var url = buildExportUrl(pendingTemplate, deptId, deptName, scope, month, year);
  if (!url) { showToast('⚠ Report type not supported yet.'); return; }

  closeModal('reportOptionsModal');
  addToRecentReports(pendingTemplate, pendingTemplate, url);
  showToast('✓ Generating PDF...');
  window.open(url, '_blank');
  pendingTemplate = '';
}

// CUSTOM REPORT MODAL
function clearCustomReportForm() {
  var el = document.getElementById('reportName'); if (el) el.value = '';
  ['reportType', 'customDept', 'customScope', 'customMonth', 'customYear'].forEach(function(id) {
    var e = document.getElementById(id); if (e) e.selectedIndex = 0;
  });
  var dw  = document.getElementById('customDeptWrapper');  if (dw)  dw.style.display  = 'none';
  var sw  = document.getElementById('customScopeWrapper'); if (sw)  sw.style.display  = 'none';
}

function saveCustomReport() {
  var name    = document.getElementById('reportName')  ? document.getElementById('reportName').value.trim() : '';
  var type    = document.getElementById('reportType')  ? document.getElementById('reportType').value        : '';
  var deptEl  = document.getElementById('customDept');
  var scopeEl = document.getElementById('customScope');
  var monthEl = document.getElementById('customMonth');
  var yearEl  = document.getElementById('customYear');

  if (!name || !type) { showToast('⚠ Please fill in all required fields.'); return; }

  var deptId   = deptEl  ? deptEl.value  : '';
  var deptName = deptEl  ? deptEl.options[deptEl.selectedIndex].text : 'All Departments';
  var scope    = scopeEl ? scopeEl.value : 'all';
  var month    = monthEl ? monthEl.value : '';
  var year     = yearEl  ? yearEl.value  : '';

  var url = buildExportUrl(type, deptId, deptName, scope, month, year);
  if (!url) { showToast('⚠ Report type not supported.'); return; }

  closeModal('customReportModal');
  clearCustomReportForm();
  addToRecentReports(name, type, url);
  showToast('✓ Report generated!');
  window.open(url, '_blank');
}

// Show/hide dept+scope on custom report type change
function onCustomReportTypeChange() {
  var type         = document.getElementById('reportType') ? document.getElementById('reportType').value : '';
  var deptWrapper  = document.getElementById('customDeptWrapper');
  var scopeWrapper = document.getElementById('customScopeWrapper');
  if (deptWrapper)  deptWrapper.style.display  = DEPT_REPORTS.indexOf(type)  !== -1 ? 'block' : 'none';
  if (scopeWrapper) scopeWrapper.style.display = SCOPE_REPORTS.indexOf(type) !== -1 ? 'block' : 'none';
}

// SCHEDULE REPORT MODAL
function clearScheduleForm() {
  var el = document.getElementById('schedReportName'); if (el) el.value = '';
  ['schedReportType', 'schedFrequency', 'schedStartDate'].forEach(function(id) {
    var e = document.getElementById(id); if (e) e.value = '';
  });
  var rt = document.getElementById('schedRunTime'); if (rt) rt.value = '08:00';
}

function saveSchedule() {
  var name      = (document.getElementById('schedReportName') || {}).value || '';
  var type      = (document.getElementById('schedReportType') || {}).value || '';
  var frequency = (document.getElementById('schedFrequency')  || {}).value || '';
  var startDate = (document.getElementById('schedStartDate')  || {}).value || '';
  var runTime   = (document.getElementById('schedRunTime')    || {}).value || '08:00'; // ← add

  name = name.trim();

  if (!name || !type || !frequency || !startDate) {
    showToast('⚠ Please fill in all required fields.');
    return;
  }

  var btn = document.getElementById('saveScheduleBtn');
  if (btn) { btn.disabled = true; btn.textContent = 'SAVING…'; }

  var formData = new FormData();
  formData.append('schedule_name', name);
  formData.append('report_type',   type);
  formData.append('frequency',     frequency);
  formData.append('start_date',    startDate);
  formData.append('run_time',      runTime); // ← add

  fetch(REPORT_API + '?resource=scheduled_reports', {
    method: 'POST',
    body:   formData
  })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        closeModal('scheduleReportModal');
        clearScheduleForm();
        loadScheduledReports(); // replace loadScheduledCount() so table also refreshes
        showToast('✓ Report scheduled: ' + frequency + ' starting ' + formatDate(startDate));
      } else {
        showToast('⚠ ' + (data.message || 'Failed to save schedule.'));
      }
    })
    .catch(function() {
      showToast('⚠ Network error — schedule not saved.');
    })
    .finally(function() {
      if (btn) { btn.disabled = false; btn.textContent = 'SAVE SCHEDULE'; }
    });
  }
//PAUSE SCHEDULED
  function toggleSchedule(id) {
  var formData = new FormData();
  formData.append('id', id);

  fetch(REPORT_API + '?resource=toggle_schedule&id=' + id, {
    method: 'POST',
    body:   formData
  })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        showToast('✓ ' + data.message);
        loadScheduledReports(); // refresh table stat card
      } else {
        showToast('⚠ ' + (data.message || 'Toggle failed.'));
      }
    })
    .catch(function() { showToast('⚠ Network error.'); });
}
//DELETE SCHEDULED 
function deleteSchedule(id) {
  if (!confirm('Delete this scheduled report? This cannot be undone.')) return;

  var formData = new FormData();
  formData.append('id', id);

  fetch(REPORT_API + '?resource=delete_schedule&id=' + id, {
    method: 'POST',
    body:   formData
  })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        showToast('✓ Schedule deleted.');
        loadScheduledReports(); // refresh table + stat card
      } else {
        showToast('⚠ ' + (data.message || 'Delete failed.'));
      }
    })
    .catch(function() { showToast('⚠ Network error.'); });
}