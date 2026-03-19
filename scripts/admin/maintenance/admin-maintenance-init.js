document.addEventListener('DOMContentLoaded', function() {

  // Open maintenance modal
  var openBtn = document.getElementById('maintOpenModalBtn');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      openModal('maintModalOverlay');
      var f = document.getElementById('maintAssetId');
      if (f) f.focus();
    });
  }

  // Cancel maintenance modal
  var cancelBtn = document.getElementById('maintCancelBtn');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      closeModal('maintModalOverlay');
      clearMaintForm();
    });
  }

  // Save maintenance
  var saveBtn = document.getElementById('maintSaveBtn');
  if (saveBtn) saveBtn.addEventListener('click', saveMaintenance);

  // Close view modal
  var closeViewBtn = document.getElementById('closeViewMaintBtn');
  if (closeViewBtn) {
    closeViewBtn.addEventListener('click', function() { closeModal('viewMaintModal'); });
  }

  // Export open
  var exportBtn = document.getElementById('maintExportBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', function() { openModal('maintExportModal'); });
  }

  // Export cancel
  var exportCancelBtn = document.getElementById('maintExportCancelBtn');
  if (exportCancelBtn) {
    exportCancelBtn.addEventListener('click', function() { closeModal('maintExportModal'); });
  }

  // Export confirm
  var exportConfirmBtn = document.getElementById('maintExportConfirmBtn');
  if (exportConfirmBtn) {
    exportConfirmBtn.addEventListener('click', doMaintExport);
  }

  // Search
  var searchInput = document.getElementById('maintSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      renderMaintTable(this.value, getActiveTab());
    });
  }

  // Filter tabs
  document.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
      var s = document.getElementById('maintSearchInput');
      renderMaintTable(s ? s.value : '', this.textContent.trim());
    });
  });

  // Close modal on outside click
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('active');
    });
  });

  // Close modal on Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
        m.classList.remove('active');
      });
    }
  });

  // Asset ID input — auto fetch description + department
  var assetIdInput = document.getElementById('maintAssetId');
  if (assetIdInput) {
    var fetchTimer = null;
    assetIdInput.addEventListener('input', function() {
      clearTimeout(fetchTimer);
      var val = this.value.trim();
      if (!val) {
        fetchMaintAssetInfo('');
        return;
      }
      if (val.length < 10) return;
      fetchTimer = setTimeout(function() {
        fetchMaintAssetInfo(val);
      }, 800);
    });
  }

  loadMaintDropdowns();
  loadMaintenance();
});