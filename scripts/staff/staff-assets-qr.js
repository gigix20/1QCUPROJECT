// QR SCAN HANDLER
// Reads ?asset_id= from URL on page load
function handleQRScan() {
  var params   = new URLSearchParams(window.location.search);
  var asset_id = params.get('asset_id');
  if (!asset_id) return;

  var attempts = 0;
  var interval = setInterval(function() {
    attempts++;

    var asset = assets.find(function(a) {
      return a.ASSET_ID === asset_id;
    });

    if (asset) {
      clearInterval(interval);

      // Find the row and highlight it
      var rows = document.querySelectorAll('#assetsTableBody tr');
      rows.forEach(function(row) {
        var strong = row.querySelector('strong');
        if (strong && strong.textContent === asset_id) {

          // Scroll to row
          row.scrollIntoView({ behavior: 'smooth', block: 'center' });

          // Highlight row
          row.classList.add('qr-highlight');
          setTimeout(function() {
            row.classList.remove('qr-highlight');
          }, 3000);
        }
      });

      // Removed — showQRModal(asset) no longer called
      showToast('✓ Asset found: ' + asset_id);

    } else if (attempts > 20) {
      clearInterval(interval);
      showToast('⚠ Asset not found: ' + asset_id);
    }

  }, 200);
}