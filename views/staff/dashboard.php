<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<<< Updated upstream:views/staff/dashboard.php
<!-- STAFF -->
<?php
require_once __DIR__ . '/../../backend/auth.php';

// If user is not logged in, redirect user to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: /1QCUPROJECT/views/auth/login.php");
    exit;
}
?>

========
<?php $activePage = 'assets'; ?>
<?php include '../../components/staff/sidebar.php'; ?>
>>>>>>>> Stashed changes:views/staff/index.php
=======
<?php $activePage = 'dashboard'; ?>
<?php include '../../components/staff/sidebar.php'; ?>
>>>>>>> Stashed changes
=======
<?php $activePage = 'dashboard'; ?>
<?php include '../../components/staff/sidebar.php'; ?>
>>>>>>> Stashed changes
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
<<<<<<< Updated upstream
  <title>ONEQCU | Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../../styles/staff/staff_style.css">
</head>
<body>

  <?php $currentPage = 'dashboard'; ?>
  <?php require __DIR__ . '/../../components/staff/staff_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main">

    <!-- Top Bar -->
    <!-- TODO: Make this a component -->
    <div class="topbar">
      <h1 class="page-title">DASHBOARD</h1>
      <div class="topbar-actions">
        <input type="text" class="search-input" placeholder="Search assets...">
=======
  <title>ONEQCU - Dashboard</title>
  <link rel="stylesheet" href="../../styles/staff/staff-style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <h1 class="page-title">DASHBOARD</h1>
      <div class="topbar-actions">
        <input type="text" class="search-input" id="searchInput" placeholder="Search assets...">
>>>>>>> Stashed changes
        <button class="add-btn" id="openModalBtn">+ Add Asset</button>
      </div>
    </div>

<<<<<<< Updated upstream
    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Assets</div>
        <div class="stat-value">512</div>
=======
    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Assets</div>
        <div class="stat-value" id="totalCount">0</div>
>>>>>>> Stashed changes
        <div class="stat-sub">All registered assets</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">In Use</div>
<<<<<<< Updated upstream
        <div class="stat-value">118</div>
        <div class="stat-sub green">23% total</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Available</div>
        <div class="stat-value">370</div>
        <div class="stat-sub">Ready to assign</div>
      </div>
      <div class="stat-card">
        <div class="stat-label"> Under Maintenance</div>
        <div class="stat-value">24</div>
=======
        <div class="stat-value" id="inUseCount">0</div>
        <div class="stat-sub green" id="inUsePct">0% total</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Available</div>
        <div class="stat-value" id="availableCount">0</div>
        <div class="stat-sub">Ready to assign</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Maintenance</div>
        <div class="stat-value" id="maintenanceCount">0</div>
>>>>>>> Stashed changes
        <div class="stat-sub red">Needs attention</div>
      </div>
    </div>

<<<<<<< Updated upstream
    <!-- Recent Assets Table -->
=======
    <!-- Table -->
>>>>>>> Stashed changes
    <div class="table-section">
      <div class="table-header">
        <h2>Recent Assets</h2>
        <button class="view-all-btn">VIEW ALL</button>
      </div>
<<<<<<< Updated upstream

=======
>>>>>>> Stashed changes
      <table class="asset-table">
        <thead>
          <tr>
            <th>ASSET ID</th>
            <th>QR CODE</th>
            <th>DESCRIPTION</th>
            <th>DEPARTMENT</th>
            <th>STATUS</th>
            <th>UPDATED</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody id="assetTableBody">
<<<<<<< Updated upstream
          <!-- Rows will be populated dynamically from the system -->
          <tr class="empty-row">
            <td colspan="6">No assets to display.</td>
=======
          <tr class="empty-row">
            <td colspan="7">No assets to display.</td>
>>>>>>> Stashed changes
          </tr>
        </tbody>
      </table>
    </div>

  </div>

<<<<<<< Updated upstream
  <!-- Modal Overlay -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <h2 class="modal-title">Asset details</h2>
      <div class="modal-divider"></div>

      <p class="modal-section-label">ASSET INFORMATION</p>
      <div class="modal-info-box">
        <p><strong>ASSET ID:</strong> QCU-LAB-603-2026-002</p>
        <p><strong>DESCRIPTION:</strong> ASUS ROG6072 DESKTOP</p>
        <p><strong>SERIAL NUMBER:</strong> SN7281J8AS7</p>
        <p><strong>CATEGORY:</strong> COMPUTER EQUIPMENT</p>
        <p><strong>CONDITION:</strong> GOOD</p>
      </div>

      <div class="modal-two-col">
        <div>
          <p class="modal-section-label">LOCATION & ASSIGNMENT</p>
          <div class="modal-info-box">
            <p><strong>DEPARTMENT:</strong> COMPUTER SCIENCE</p>
            <p><strong>LOCATION:</strong> LAB 603B</p>
            <p><strong>CURRENT STATUS:</strong> IN USE</p>
            <p><strong>ASSIGNED TO:</strong> DOC. JOMOC</p>
          </div>
        </div>
        <div>
          <p class="modal-section-label">HISTORY</p>
          <div class="modal-info-box history-box">
            <p><strong>FEB, 5 2021:</strong> ASSIGNED TO DOC. JOMOC</p>
            <p><strong>MARCH, 06 2021:</strong> MAINTENANCE COMPLETED</p>
            <p><strong>DEC, 5 2020:</strong> ASSET REGISTERED</p>
          </div>
=======
  <!-- ========================
       ADD ASSET MODAL
  ========================= -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <div class="modal-title">Add New Asset</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">ASSET INFORMATION</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Asset ID</label>
          <input type="text" id="assetId" placeholder="e.g. AST-0001">
        </div>
        <!-- QR code is auto-generated — shown as read-only -->
        <div class="form-group">
          <label>QR Code <span style="font-size:10px; color:#a78bfa; font-weight:400; letter-spacing:0;">(auto-generated)</span></label>
          <input type="text" id="qrCode" readonly>
        </div>
      </div>

      <div class="form-full">
        <label>Description</label>
        <input type="text" id="description" placeholder="e.g. Dell Laptop Latitude 5540">
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Department</label>
          <select id="department">
            <option value="">-- Select Department --</option>
            <option>CICS</option>
            <option>COENG</option>
            <option>COED</option>
            <option>CBA</option>
            <option>CAS</option>
            <option>CAUP</option>
            <option>OSAS</option>
            <option>Admin Office</option>
            <option>Library</option>
            <option>IT Department</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="status">
            <option value="Available">Available</option>
            <option value="In Use">In Use</option>
            <option value="Maintenance">Maintenance</option>
          </select>
>>>>>>> Stashed changes
        </div>
      </div>

      <div class="modal-buttons">
<<<<<<< Updated upstream
        <button class="modal-edit-btn">EDIT</button>
        <button class="modal-close-btn" onclick="closeModal()">CLOSE</button>
=======
        <button class="modal-edit-btn" id="cancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveAssetBtn">SAVE & GENERATE QR</button>
>>>>>>> Stashed changes
      </div>
    </div>
  </div>

<<<<<<< Updated upstream
  <script src="../../scripts/staff/staff_script.js"></script>
</body>
</html>

=======
  <!-- ========================
       QR CODE VIEW MODAL
  ========================= -->
  <div class="modal-overlay" id="qrViewModal">
    <div class="modal qr-view-modal">
      <div class="modal-title">QR Code</div>
      <div class="modal-divider"></div>

      <div class="qr-asset-info">
        <p class="qr-asset-id" id="qrModalAssetId"></p>
        <p class="qr-asset-desc" id="qrModalDesc"></p>
        <span id="qrModalDept" style="display:inline-block; font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; border:1px solid; margin-bottom:12px; letter-spacing:0.5px;"></span>
      </div>

      <!-- QR code renders here -->
      <div class="qr-display-box">
        <div id="qrCanvas"></div>
      </div>

      <div>
        <p class="qr-code-text" id="qrModalCodeText"></p>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="closeQrViewBtn">CLOSE</button>
        <button class="qr-download-btn" id="downloadQrBtn">⬇ DOWNLOAD</button>
      </div>
    </div>
  </div>

  <!-- ========================
       EDIT ASSET MODAL
  ========================= -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <div class="modal-title">Edit Asset</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">EDIT INFORMATION</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Asset ID</label>
          <input type="text" id="editAssetId" placeholder="e.g. AST-0001">
        </div>
        <div class="form-group">
          <label>QR Code <span style="font-size:10px; color:#a78bfa; font-weight:400; letter-spacing:0;">(read-only)</span></label>
          <input type="text" id="editQrCode" readonly>
        </div>
      </div>

      <div class="form-full">
        <label>Description</label>
        <input type="text" id="editDescription" placeholder="e.g. Dell Laptop Latitude 5540">
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Department</label>
          <select id="editDepartment">
            <option value="">-- Select Department --</option>
            <option>CICS</option>
            <option>COENG</option>
            <option>COED</option>
            <option>CBA</option>
            <option>CAS</option>
            <option>CAUP</option>
            <option>OSAS</option>
            <option>Admin Office</option>
            <option>Library</option>
            <option>IT Department</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="editStatus">
            <option value="Available">Available</option>
            <option value="In Use">In Use</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelEditBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveEditBtn">SAVE CHANGES</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <!-- QRCode.js library for generating QR code images -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="../../scripts/staff/staff-script.js"></script>
</body>
</html>
>>>>>>> Stashed changes
