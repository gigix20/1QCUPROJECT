// EXPORT
function doExport() {
  var scope          = document.getElementById('exportScope').value;
  var includeDeleted = document.getElementById('exportIncludeDeleted').checked ? '1' : '0';

  var url = API + '?resource=export' +
            '&scope='           + scope +
            '&include_deleted=' + includeDeleted;

  if (scope === 'filtered') {
    var visibleIds = [];
    document.querySelectorAll('#assetsTableBody tr').forEach(function(row) {
      var strong = row.querySelector('strong');
      if (strong) visibleIds.push(strong.textContent);
    });
    url += '&asset_ids=' + encodeURIComponent(visibleIds.join(','));
  }

  window.location.href = url;
  closeModal('exportModal');
}