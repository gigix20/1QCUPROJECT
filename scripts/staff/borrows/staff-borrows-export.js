// scripts/staff/borrows/staff-borrows-export.js

function doBorrowExport() {
  var scope            = document.getElementById('borrowExportScope').value;
  var includeCancelled = document.getElementById('borrowExportIncludeCancelled').checked ? '1' : '0';

  var url = BORROW_API + '?resource=borrow_export' +
            '&scope='             + scope +
            '&include_cancelled=' + includeCancelled;

  if (scope === 'filtered') {
    var visibleIds = [];
    document.querySelectorAll('#borrowTableBody tr').forEach(function(row) {
      var strong = row.querySelector('strong');
      if (strong) visibleIds.push(strong.textContent.trim());
    });
    url += '&borrow_ids=' + encodeURIComponent(visibleIds.join(','));
  }

  window.location.href = url;
  closeModal('borrowExportModal');
}