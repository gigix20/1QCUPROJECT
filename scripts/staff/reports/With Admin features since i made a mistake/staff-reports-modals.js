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
}

function saveSchedule() {
  var name      = document.getElementById('schedReportName') ? document.getElementById('schedReportName').value.trim() : '';
  var type      = document.getElementById('schedReportType') ? document.getElementById('schedReportType').value        : '';
  var frequency = document.getElementById('schedFrequency')  ? document.getElementById('schedFrequency').value         : '';
  var startDate = document.getElementById('schedStartDate')  ? document.getElementById('schedStartDate').value         : '';

  if (!name || !type || !frequency || !startDate) { showToast('⚠ Please fill in all required fields.'); return; }

  scheduledList.push({ name: name, type: type, frequency: frequency, startDate: formatDate(startDate) });
  closeModal('scheduleReportModal');
  clearScheduleForm();
  updateReportStats();
  showToast('✓ Report scheduled: ' + frequency + ' starting ' + formatDate(startDate));
}
