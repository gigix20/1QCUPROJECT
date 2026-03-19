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
  <title>ONEQCU | Dashboard</title>
  <link rel="stylesheet" href="../../styles/staff/staff_style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <?php $currentPage = 'dashboard'; ?>
  <?php require __DIR__ . '/../../components/staff/staff_sidebar.php'; ?>

  <!-- MAIN -->
  <div class="main">

    <div class="topbar">
      <div>
        <h1 class="page-title">DASHBOARD</h1>
        <p class="page-sub">Welcome back! Here's what's happening today.</p>
      </div>
    </div>

    <!-- ASSET STATS-->
    <p class="modal-section-label" style="margin-bottom:10px;">ASSET OVERVIEW</p>
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Assets</div>
        <div class="stat-value" id="dashStatTotal">0</div>
        <div class="stat-sub">All registered assets</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Available</div>
        <div class="stat-value" id="dashStatAvailable">0</div>
        <div class="stat-sub green">Ready to assign</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">In Use</div>
        <div class="stat-value" id="dashStatInUse">0</div>
        <div class="stat-sub">Currently borrowed</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Under Maintenance</div>
        <div class="stat-value" id="dashStatMaintenance">0</div>
        <div class="stat-sub red">Needs attention</div>
      </div>
    </div>

    <!-- BORROW + MAINTENANCE STATS-->
    <div class="stats-row" style="margin-top:0;">
      <div class="stat-card">
        <div class="stat-label">Pending Borrows</div>
        <div class="stat-value" id="dashStatPendingBorrows">0</div>
        <div class="stat-sub yellow">Awaiting approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Borrows</div>
        <div class="stat-value" id="dashStatActiveBorrows">0</div>
        <div class="stat-sub">Currently borrowed</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue Returns</div>
        <div class="stat-value" id="dashStatOverdue">0</div>
        <div class="stat-sub red">Past due date</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pending Maintenance</div>
        <div class="stat-value" id="dashStatPendingMaint">0</div>
        <div class="stat-sub yellow">Awaiting service</div>
      </div>
    </div>

    <!-- RECENT TABLES-->
    <div class="modal-two-col" style="gap:20px;align-items:flex-start;">

      <!-- Recent Assets -->
      <div class="table-section" style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h2 style="font-size:15px;font-weight:600;">Recent Assets</h2>
          <a href="/1QCUPROJECT/views/staff/assets.php"
             style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
            VIEW ALL →
          </a>
        </div>
        <table class="asset-table">
          <thead>
            <tr>
              <th>ASSET ID</th>
              <th>DESCRIPTION</th>
              <th>DEPARTMENT</th>
              <th>STATUS</th>
              <th>ADDED</th>
            </tr>
          </thead>
          <tbody id="dashAssetTableBody">
            <tr class="empty-row"><td colspan="5">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Borrows -->
      <div class="table-section" style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h2 style="font-size:15px;font-weight:600;">Recent Borrow Requests</h2>
          <a href="/1QCUPROJECT/views/staff/borrow.php"
             style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
            VIEW ALL →
          </a>
        </div>
        <table class="asset-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>BORROWER</th>
              <th>ASSET ID</th>
              <th>DATE</th>
              <th>STATUS</th>
            </tr>
          </thead>
          <tbody id="dashBorrowTableBody">
            <tr class="empty-row"><td colspan="5">Loading...</td></tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Recent Maintenance -->
    <div class="table-section">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="font-size:15px;font-weight:600;">Recent Maintenance</h2>
        <a href="/1QCUPROJECT/views/staff/maintenance.php"
           style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
          VIEW ALL →
        </a>
      </div>
      <table class="asset-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>ASSET ID</th>
            <th>TYPE</th>
            <th>SCHEDULED</th>
            <th>STATUS</th>
          </tr>
        </thead>
        <tbody id="dashMaintTableBody">
          <tr class="empty-row"><td colspan="5">Loading...</td></tr>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>
  <script src="../../scripts/admin/dashboard/admin-dashboard.js"></script>
  <script src="../../scripts/admin/dashboard/admin-dashboard-init.js"></script>
</body>
</html>