// MAINTENANCE EXPORT
function doMaintExport() {
  var scope            = document.getElementById('maintExportScope').value;
  var includeCancelled = document.getElementById('maintExportIncludeCancelled').checked ? '1' : '0';

  var url = MAINT_API + '?resource=maintenance_export' +
            '&scope='             + scope +
            '&include_cancelled=' + includeCancelled;

  if (scope === 'filtered') {
    var visibleIds = [];
    document.querySelectorAll('#maintTableBody tr').forEach(function(row) {
      var strong = row.querySelector('strong');
      if (strong) visibleIds.push(strong.textContent.trim());
    });
    url += '&maintenance_ids=' + encodeURIComponent(visibleIds.join(','));
  }

  window.location.href = url;
  closeModal('maintExportModal');
}