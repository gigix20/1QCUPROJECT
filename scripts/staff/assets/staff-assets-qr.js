
// QR TAG
function qrTagHTML(a) {
  var c  = getDeptColor(a.DEPARTMENT_NAME || a.department);
  var id = a.ASSET_ID || a.assetId;
  return '<span class="qr-tag" title="Click to view QR" ' +
    'onclick="showQRModal(' + JSON.stringify(a).replace(/'/g, "\\'") + ')" ' +
    'style="border-left:3px solid ' + c + ';color:' + c + ';background:' + c + '12;">' +
    id + '</span>';
}




// QR MODAL
function showQRModal(asset) {
  var modal = document.getElementById('qrViewModal');
  if (!modal) return;

  var dept = asset.DEPARTMENT_NAME || asset.department;
  var qr   = asset.QR_CODE         || asset.qrCode;
  var id   = asset.ASSET_ID        || asset.assetId;
  var desc = asset.DESCRIPTION     || asset.description;

  var set = function(elId, val) {
    var el = document.getElementById(elId);
    if (el) el.textContent = val;
  };

  set('qrModalAssetId',  id);
  set('qrModalDesc',     desc);
  set('qrModalCodeText', qr);

  var deptColor = getDeptColor(dept);
  var divider   = document.querySelector('#qrViewModal .modal-divider');
  if (divider) divider.style.background = 'linear-gradient(to right, ' + deptColor + ', transparent)';

  var deptBadge = document.getElementById('qrModalDept');
  if (deptBadge) {
    deptBadge.textContent       = dept;
    deptBadge.style.background  = deptColor + '18';
    deptBadge.style.color       = deptColor;
    deptBadge.style.borderColor = deptColor + '44';
  }

  var container = document.getElementById('qrCanvas');
  if (container && typeof QRCode !== 'undefined') {
    container.innerHTML = '';
    new QRCode(container, {
      text:         qr,
      width:        200,
      height:       200,
      colorDark:    deptColor,
      colorLight:   '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  }

  openModal('qrViewModal');
}




// DOWNLOAD QR
function downloadQR() {
  var container = document.getElementById('qrCanvas');
  if (!container) return;
  var codeLabel = document.getElementById('qrModalCodeText');
  var filename  = (codeLabel ? codeLabel.textContent : 'qrcode') + '.png';
  var canvas    = container.querySelector('canvas');
  var img       = container.querySelector('img');
  var a         = document.createElement('a');
  a.download    = filename;
  if (canvas)   { a.href = canvas.toDataURL('image/png'); a.click(); }
  else if (img) { a.href = img.src; a.click(); }
}




// VIEW QR BY ID
function viewQRById(asset_id) {
  var asset = assets.find(function(a) { return a.ASSET_ID === asset_id; });
  if (asset) showQRModal(asset);
}




// QR SCAN HANDLER
function handleQRScan() {
  var params   = new URLSearchParams(window.location.search);
  var asset_id = params.get('asset_id');
  if (!asset_id) return;

  var attempts = 0;
  var interval = setInterval(function() {
    attempts++;
    var asset = assets.find(function(a) { return a.ASSET_ID === asset_id; });
    if (asset) {
      clearInterval(interval);
      var rows = document.querySelectorAll('#assetsTableBody tr');
      rows.forEach(function(row) {
        var strong = row.querySelector('strong');
        if (strong && strong.textContent === asset_id) {
          row.scrollIntoView({ behavior: 'smooth', block: 'center' });
          row.classList.add('qr-highlight');
          setTimeout(function() { row.classList.remove('qr-highlight'); }, 3000);
        }
      });
      showToast('✓ Asset found: ' + asset_id);
    } else if (attempts > 20) {
      clearInterval(interval);
      showToast('⚠ Asset not found: ' + asset_id);
    }
  }, 200);
}