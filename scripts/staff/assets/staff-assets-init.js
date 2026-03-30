document.addEventListener('DOMContentLoaded', function() {
  // Open add modal
  var openBtn = document.getElementById('assetsOpenModalBtn');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      openModal('assetsModalOverlay');
      var f = document.getElementById('assetsDescription');
      if (f) f.focus();
    });
  }

  // Cancel add
  var cancelBtn = document.getElementById('assetsCancelBtn');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      closeModal('assetsModalOverlay');
      assetsClearForm();
    });
  }

  // Save add
  var saveBtn = document.getElementById('assetsSaveBtn');
  if (saveBtn) saveBtn.addEventListener('click', assetsSave);

  // Cancel edit
  var cancelEditBtn = document.getElementById('cancelEditBtn');
  if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', function() { closeModal('editModal'); });
  }

  // Save edit
  var saveEditBtn = document.getElementById('saveEditBtn');
  if (saveEditBtn) saveEditBtn.addEventListener('click', assetsSaveEdit);

  // Close QR modal
  var closeQrBtn = document.getElementById('closeQrViewBtn');
  if (closeQrBtn) {
    closeQrBtn.addEventListener('click', function() { closeModal('qrViewModal'); });
  }

  // Download QR
  var downloadQrBtn = document.getElementById('downloadQrBtn');
  if (downloadQrBtn) downloadQrBtn.addEventListener('click', downloadQR);

  // ── Request Deletion modal ────────────────────────────────────────────────
  var cancelDelReqBtn = document.getElementById('cancelDelReqBtn');
  if (cancelDelReqBtn) {
    cancelDelReqBtn.addEventListener('click', function() {
      closeModal('requestDeletionModal');
    });
  }

  var submitDelReqBtn = document.getElementById('submitDelReqBtn');
  if (submitDelReqBtn) submitDelReqBtn.addEventListener('click', submitDeletionRequest);

  // Search
  var searchInput = document.getElementById('assetsSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      renderAssetsTable(this.value, getActiveTab());
    });
  }

  // Filter tabs
  document.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
      var s = document.getElementById('assetsSearchInput');
      renderAssetsTable(s ? s.value : '', this.dataset.status || 'ALL');
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

  // Liable person dropdown — Add modal
  var deptSelect = document.getElementById('assetsDepartment');
  if (deptSelect) {
    deptSelect.addEventListener('change', function() {
      updateLiableDropdown('assetsDepartment', 'assetsLiablePerson');
    });
  }

  // Liable person dropdown — Edit modal
  var editDeptSelect = document.getElementById('editDepartment');
  if (editDeptSelect) {
    editDeptSelect.addEventListener('change', function() {
      updateLiableDropdown('editDepartment', 'editLiablePerson');
    });
  }

  // Export open
  var exportBtn = document.getElementById('exportBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', function() { openModal('exportModal'); });
  }

  // Export cancel
  var exportCancelBtn = document.getElementById('exportCancelBtn');
  if (exportCancelBtn) {
    exportCancelBtn.addEventListener('click', function() { closeModal('exportModal'); });
  }

  // Export confirm
  var exportConfirmBtn = document.getElementById('exportConfirmBtn');
  if (exportConfirmBtn) exportConfirmBtn.addEventListener('click', doExport);

  loadDropdowns();
  loadAssets();
  handleQRScan();
});