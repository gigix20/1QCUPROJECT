<?php
require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
?>

<!-- ADMIN SIDE -->

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Admin Dashboard</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-stats.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-dashboard.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/notifications.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="/1QCUPROJECT/scripts/notifications.js"></script>
</head>

<body>
  <?php $currentPage = 'dashboard'; ?>
  <?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <!-- MAIN -->
  <div class="main">

    <div class="topbar">
      <div>
        <h1 class="page-title">DASHBOARD</h1>
        <p class="page-sub">Welcome back! Here's what's happening today.</p>
      </div>
      <div class="header">
        <!-- Notification system will be inserted here by JavaScript -->
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         GOVERNANCE STATS ROW  (Admin-only)
    ══════════════════════════════════════════ -->
    <p class="modal-section-label" style="margin-bottom:10px;">GOVERNANCE OVERVIEW</p>
    <div class="stats-row">

      <div class="stat-card stat-card--gov">
        <div class="stat-gov-icon">👤</div>
        <div class="stat-label">Registered Users</div>
        <div class="stat-value" id="dashStatUsers">0</div>
        <div class="stat-sub">All accounts in system</div>
      </div>

      <div class="stat-card stat-card--gov">
        <div class="stat-gov-icon">🏢</div>
        <div class="stat-label">Total Departments</div>
        <div class="stat-value" id="dashStatDepts">0</div>
        <div class="stat-sub">Active departments</div>
      </div>

      <div class="stat-card stat-card--gov">
        <div class="stat-gov-icon">📦</div>
        <div class="stat-label">System-Wide Assets</div>
        <div class="stat-value" id="dashStatSystemAssets">0</div>
        <div class="stat-sub">All registered assets</div>
      </div>

      <div class="stat-card stat-card--gov stat-card--alert" id="pendingDeleteCard">
        <div class="stat-gov-icon">⚠️</div>
        <div class="stat-label">Pending Delete Requests</div>
        <div class="stat-value" id="dashStatPendingDelete">0</div>
        <div class="stat-sub stat-sub--alert">Awaiting your approval</div>
      </div>

    </div>

    <!-- ══════════════════════════════════════════
         ASSET STATS  (existing)
    ══════════════════════════════════════════ -->
    <p class="modal-section-label" style="margin-bottom:10px;">ASSET OVERVIEW</p>
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Active Assets</div>
        <div class="stat-value" id="dashStatTotal">0</div>
        <div class="stat-sub">All active registered assets</div>
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

    <!-- BORROW + MAINTENANCE STATS (existing) -->
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

    <!-- ══════════════════════════════════════════
         RECENT TABLES (existing)
    ══════════════════════════════════════════ -->
    <div class="modal-two-col" style="gap:20px;align-items:flex-start;">

      <!-- Recent Assets -->
      <div class="table-section" style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h2 style="font-size:15px;font-weight:600;">Recent Assets</h2>
          <a href="/1QCUPROJECT/views/admin/assets_page.php" style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
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
            <tr class="empty-row">
              <td colspan="5">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Borrows -->
      <div class="table-section" style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h2 style="font-size:15px;font-weight:600;">Recent Borrow Requests</h2>
          <a href="/1QCUPROJECT/views/admin/borrow_page.php" style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
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
            <tr class="empty-row">
              <td colspan="5">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Recent Maintenance (existing) -->
    <div class="table-section">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="font-size:15px;font-weight:600;">Recent Maintenance</h2>
        <a href="/1QCUPROJECT/views/admin/maintenance_page.php" style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
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
          <tr class="empty-row">
            <td colspan="5">Loading...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ══════════════════════════════════════════
         RECENT REPORTS SNAPSHOT  (Admin-only)
    ══════════════════════════════════════════ -->
    <div class="table-section" style="margin-top:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
          <h2 style="font-size:15px;font-weight:600;">Recent Reports</h2>
          <p style="font-size:12px;color:#888;margin-top:2px;">Latest generated reports across the system</p>
        </div>
        <a href="/1QCUPROJECT/views/admin/reports_page.php" style="font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">
          MANAGE REPORTS →
        </a>
      </div>
      <table class="asset-table">
        <thead>
          <tr>
            <th>REPORT NAME</th>
            <th>TYPE</th>
            <th>GENERATED BY</th>
            <th>DATE</th>
            <th>FORMAT</th>
          </tr>
        </thead>
        <tbody id="dashReportsTableBody">
          <tr class="empty-row">
            <td colspan="5">Loading...</td>
          </tr>
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