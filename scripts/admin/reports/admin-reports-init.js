document.addEventListener('DOMContentLoaded', function() {

  // Populate year dropdowns (current year going back 4 years)
  populateYearDropdowns();

  // Modal outside click
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('active');
    });
  });

  // Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
        m.classList.remove('active');
      });
    }
  });

  //Report Options Modal
  var cancelReportOptionsBtn  = document.getElementById('cancelReportOptionsBtn');
  var confirmReportOptionsBtn = document.getElementById('confirmReportOptionsBtn');

  if (cancelReportOptionsBtn) {
    cancelReportOptionsBtn.addEventListener('click', function() {
      closeModal('reportOptionsModal');
      pendingTemplate = '';
    });
  }
  if (confirmReportOptionsBtn) {
    confirmReportOptionsBtn.addEventListener('click', confirmReportOptions);
  }

  // Custom Report Modal
  var customReportBtn       = document.getElementById('customReportBtn');
  var cancelCustomReportBtn = document.getElementById('cancelCustomReportBtn');
  var saveCustomReportBtn   = document.getElementById('saveCustomReportBtn');
  var reportTypeSelect      = document.getElementById('reportType');

  if (customReportBtn) {
    customReportBtn.addEventListener('click', function() {
      clearCustomReportForm();
      openModal('customReportModal');
      var f = document.getElementById('reportName'); if (f) f.focus();
    });
  }
  if (cancelCustomReportBtn) {
    cancelCustomReportBtn.addEventListener('click', function() {
      closeModal('customReportModal');
      clearCustomReportForm();
    });
  }
  if (saveCustomReportBtn)  saveCustomReportBtn.addEventListener('click',  saveCustomReport);
  if (reportTypeSelect)     reportTypeSelect.addEventListener('change',    onCustomReportTypeChange);

  //Schedule Modal
  var scheduleReportBtn = document.getElementById('scheduleReportBtn');
  var cancelScheduleBtn = document.getElementById('cancelScheduleBtn');
  var saveScheduleBtn   = document.getElementById('saveScheduleBtn');

  if (scheduleReportBtn) {
    scheduleReportBtn.addEventListener('click', function() {
      clearScheduleForm();
      openModal('scheduleReportModal');
      var f = document.getElementById('schedReportName'); if (f) f.focus();
    });
  }
  if (cancelScheduleBtn) {
    cancelScheduleBtn.addEventListener('click', function() {
      closeModal('scheduleReportModal');
      clearScheduleForm();
    });
  }
  if (saveScheduleBtn) saveScheduleBtn.addEventListener('click', saveSchedule);

  //Preview Modal
  var closePreviewBtn   = document.getElementById('closePreviewBtn');
  var downloadReportBtn = document.getElementById('downloadReportBtn');

  if (closePreviewBtn) {
    closePreviewBtn.addEventListener('click', function() { closeModal('reportPreviewModal'); });
  }
  if (downloadReportBtn) {
    downloadReportBtn.addEventListener('click', function() {
      totalDownloads++;
      updateReportStats();
      showToast('✓ Report downloaded!');
      closeModal('reportPreviewModal');
    });
  }

  //Init
  loadReportDepts();
  loadRecentReports();
  loadScheduledCount();
  loadScheduledReports();
  startSchedulerPolling();  
});