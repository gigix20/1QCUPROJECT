<?php
require_once __DIR__ . '/../../backend/auth.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: /1QCUPROJECT/views/auth/login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Maintenance</title>
  <link rel="stylesheet" href="../../styles/staff/staff_style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

  <?php $currentPage = 'maintenance'; ?>
  <?php require __DIR__ . '/../../components/staff/staff_sidebar.php'; ?>

  <!-- ========================
       MAIN CONTENT
  ========================= -->
  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div>
        <h1 class="page-title">MAINTENANCE</h1>
        <p class="page-sub">Track and manage asset maintenance records</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn">EXPORT</button>
        <button class="add-btn" id="maintOpenModalBtn">+ New Request</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value" id="maintStatPending">0</div>
        <div class="stat-sub yellow">Awaiting service</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">In Progress</div>
        <div class="stat-value" id="maintStatInProgress">0</div>
        <div class="stat-sub">Currently serviced</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value" id="maintStatCompleted">0</div>
        <div class="stat-sub green">This month</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue</div>
        <div class="stat-value" id="maintStatOverdue">0</div>
        <div class="stat-sub red">Past schedule</div>
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar-full">
      <input type="text" id="maintSearchInput" placeholder="Search by asset ID, description, or technician...">
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="filter-tab active">ALL</button>
      <button class="filter-tab">PENDING</button>
      <button class="filter-tab">IN PROGRESS</button>
      <button class="filter-tab">COMPLETED</button>
      <button class="filter-tab">OVERDUE</button>
    </div>

    <!-- Table -->
    <div class="table-section">
      <table class="asset-table">
        <thead>
          <tr>
            <th>REQUEST ID</th>
            <th>ASSET ID</th>
            <th>DESCRIPTION</th>
            <th>TYPE</th>
            <th>TECHNICIAN</th>
            <th>SCHEDULED DATE</th>
            <th>STATUS</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody id="maintTableBody">
          <tr class="empty-row">
            <td colspan="8">No maintenance records to display.</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>

  <!-- ========================
       NEW MAINTENANCE REQUEST MODAL
  ========================= -->
  <div class="modal-overlay" id="maintModalOverlay">
    <div class="modal">
      <div class="modal-title">New Maintenance Request</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">ASSET DETAILS</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Asset ID <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="maintAssetId" placeholder="e.g. AST-0001">
        </div>
        <div class="form-group">
          <label>Maintenance Type <span style="color:#dc2626;font-weight:700;">*</span></label>
          <select id="maintType">
            <option value="">-- Select Type --</option>
            <option>Preventive</option>
            <option>Corrective</option>
            <option>Predictive</option>
            <option>Emergency</option>
            <option>Inspection</option>
            <option>Calibration</option>
            <option>Cleaning</option>
            <option>Upgrade</option>
          </select>
        </div>
      </div>

      <div class="form-full">
        <label>Description / Issue <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="maintDescription" placeholder="e.g. Screen flickering, needs display replacement">
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Assigned Technician</label>
          <input type="text" id="maintTechnician" placeholder="e.g. Pedro Santos">
        </div>
        <div class="form-group">
          <label>Scheduled Date <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="date" id="maintScheduledDate">
        </div>
      </div>

      <div class="form-full">
        <label>Notes</label>
        <input type="text" id="maintNotes" placeholder="Additional notes or instructions...">
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="maintCancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="maintSaveBtn">SUBMIT REQUEST</button>
      </div>
    </div>
  </div>

  <!-- ========================
       VIEW MAINTENANCE DETAILS MODAL
  ========================= -->
  <div class="modal-overlay" id="viewMaintModal">
    <div class="modal">
      <div class="modal-title">Maintenance Details</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">REQUEST INFO</div>
      <div class="modal-info-box">
        <p><strong>Request ID:</strong> <span id="vmReqId"></span></p>
        <p><strong>Status:</strong> <span id="vmStatus" class="badge"></span></p>
        <p><strong>Completed Date:</strong> <span id="vmCompleted"></span></p>
      </div>

      <div class="modal-section-label">ASSET &amp; TASK</div>
      <div class="modal-two-col" style="margin-bottom:14px;">
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Asset ID:</strong> <span id="vmAssetId"></span></p>
          <p><strong>Description:</strong> <span id="vmDesc"></span></p>
          <p><strong>Type:</strong> <span id="vmType"></span></p>
        </div>
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Technician:</strong> <span id="vmTech"></span></p>
          <p><strong>Scheduled:</strong> <span id="vmSched"></span></p>
          <p><strong>Notes:</strong> <span id="vmNotes"></span></p>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-close-btn" id="closeViewMaintBtn">CLOSE</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script src="../../scripts/staff/staff_script.js"></script>
</body>

</html>