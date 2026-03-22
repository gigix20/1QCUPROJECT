document.addEventListener('DOMContentLoaded', function() {

  // Open borrow modal
  var openBtn = document.getElementById('borrowOpenModalBtn');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      openModal('borrowModalOverlay');
      var f = document.getElementById('borrowFirstName');
      if (f) f.focus();
    });
  }

  // Cancel borrow
  var cancelBtn = document.getElementById('borrowCancelBtn');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      closeModal('borrowModalOverlay');
      clearBorrowForm();
    });
  }

  // Submit borrow
  var saveBtn = document.getElementById('borrowSaveBtn');
  if (saveBtn) saveBtn.addEventListener('click', saveBorrow);

  // Close view modal
  var closeViewBtn = document.getElementById('closeViewBorrowBtn');
  if (closeViewBtn) {
    closeViewBtn.addEventListener('click', function() { closeModal('viewBorrowModal'); });
  }

  // Cancel return modal
  var cancelReturnBtn = document.getElementById('cancelReturnBtn');
  if (cancelReturnBtn) {
    cancelReturnBtn.addEventListener('click', function() { closeModal('returnModal'); });
  }

  // Save return
  var saveReturnBtn = document.getElementById('saveReturnBtn');
  if (saveReturnBtn) saveReturnBtn.addEventListener('click', saveReturn);

  // Search
  var searchInput = document.getElementById('borrowSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      renderBorrowTable(this.value, getActiveTab());
    });
  }

  // Filter tabs
  document.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
      var s = document.getElementById('borrowSearchInput');
      renderBorrowTable(s ? s.value : '', this.textContent.trim());
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

  // Asset ID input — auto fetch description + liable person
var assetIdInput = document.getElementById('borrowAssetId');
  if (assetIdInput) {
    var fetchTimer = null;
    assetIdInput.addEventListener('input', function() {
      clearTimeout(fetchTimer);
      var val = this.value.trim();
      
    // Clear fields if empty
      if (!val) {
        fetchAssetInfo('');
        return;
      }

      if (val.length < 10) return; // AST-XX-XXXX-XXXX is at least 10 chars

      fetchTimer = setTimeout(function() {
        fetchAssetInfo(val);
      }, 800); //800ms debounce time
    });
  }

  // EXPORT BUTTON HOOKS
  var borrowExportBtn = document.getElementById('borrowExportBtn');
  if (borrowExportBtn) {
    borrowExportBtn.addEventListener('click', function() {
      openModal('borrowExportModal');
    });
  }

  var borrowExportCancelBtn = document.getElementById('borrowExportCancelBtn');
  if (borrowExportCancelBtn) {
    borrowExportCancelBtn.addEventListener('click', function() {
      closeModal('borrowExportModal');
    });
  }

  var borrowExportConfirmBtn = document.getElementById('borrowExportConfirmBtn');
  if (borrowExportConfirmBtn) {
    borrowExportConfirmBtn.addEventListener('click', doBorrowExport);
  }

  loadBorrowDropdowns();
  loadBorrows();

});