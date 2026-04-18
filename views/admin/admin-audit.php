<?php
require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Admin Audit Logs</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-stats.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-audit.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <?php $currentPage = 'audit'; ?>
  <?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">

    <!-- Top bar -->
    <div class="topbar">
      <div>
        <h1 class="page-title">AUDIT LOGS</h1>
        <p class="page-sub">Track all system activity and events</p>
      </div>
      <div class="topbar-actions">
        <button class="add-btn" id="exportAuditBtn">&#11015; EXPORT CSV</button>
        <button class="add-btn" id="clearAuditBtn" style="background:#dc2626;margin-left:8px;">&#128465; CLEAR LOGS</button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Events Today</div>
        <div class="stat-value" id="statToday">0</div>
        <div class="stat-sub">Since midnight</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">This Month</div>
        <div class="stat-value" id="statMonth">0</div>
        <div class="stat-sub">Current month</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Events</div>
        <div class="stat-value" id="statTotal">0</div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Critical Events</div>
        <div class="stat-value" id="statCritical">0</div>
        <div class="stat-sub red">Deletions &amp; failed logins</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="table-section">
      <div class="audit-filters">
        <input  type="text"   id="auditSearch"   class="search-input" placeholder="&#128269;  Search by user, description, reference…">
        <select id="auditModule"  class="filter-select">
          <option value="">All Modules</option>
          <option value="Assets">Assets</option>
          <option value="Borrows">Borrows</option>
          <option value="Maintenance">Maintenance</option>
          <option value="Reports">Reports</option>
          <option value="Auth">Auth</option>
        </select>
        <select id="auditAction"  class="filter-select">
          <option value="">All Actions</option>
          <option value="LOGIN">LOGIN</option>
          <option value="LOGOUT">LOGOUT</option>
          <option value="LOGIN_FAIL">LOGIN_FAIL</option>
          <option value="ASSET_ADD">ASSET_ADD</option>
          <option value="ASSET_EDIT">ASSET_EDIT</option>
          <option value="ASSET_DELETE">ASSET_DELETE</option>
          <option value="BORROW">BORROW</option>
          <option value="RETURN">RETURN</option>
          <option value="MAINTENANCE_ADD">MAINTENANCE_ADD</option>
          <option value="MAINTENANCE_UPDATE">MAINTENANCE_UPDATE</option>
          <option value="REPORT_GENERATED">REPORT_GENERATED</option>
          <option value="SCHEDULE_CREATE">SCHEDULE_CREATE</option>
          <option value="SCHEDULE_TOGGLE">SCHEDULE_TOGGLE</option>
          <option value="SCHEDULE_DELETE">SCHEDULE_DELETE</option>
        </select>
        <input  type="date"   id="auditDateFrom" class="filter-select" title="Date from">
        <input  type="date"   id="auditDateTo"   class="filter-select" title="Date to">
        <button id="applyAuditFilter" class="add-btn" style="padding:8px 18px;">APPLY</button>
        <button id="resetAuditFilter" class="modal-edit-btn" style="padding:8px 14px;">RESET</button>
      </div>

      <!-- Table -->
      <table class="asset-table" style="margin-top:14px;">
        <thead>
          <tr>
            <th>#</th>
            <th>TIMESTAMP</th>
            <th>MODULE</th>
            <th>ACTION</th>
            <th>PERFORMED BY</th>
            <th>ROLE</th>
            <th>DESCRIPTION</th>
            <th>REFERENCE</th>
            <th>IP ADDRESS</th>
          </tr>
        </thead>
        <tbody id="auditTableBody">
          <tr class="empty-row">
            <td colspan="9">Loading audit logs…</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="audit-pagination" id="auditPagination"></div>
    </div>

  </div>

  <!-- Confirm Clear Modal -->
  <div class="modal-overlay" id="clearAuditModal">
    <div class="modal">
      <div class="modal-title">Clear All Audit Logs</div>
      <div class="modal-divider"></div>
      <div class="modal-info-box" style="background:#fef2f2;border-color:#fca5a5;">
        <p style="color:#b91c1c;font-weight:600;font-size:13px;">⚠ This will permanently delete ALL audit log entries.</p>
        <p style="color:#6b7280;font-size:12px;margin-top:6px;">This action cannot be undone. Are you sure?</p>
      </div>
      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelClearBtn">CANCEL</button>
        <button class="modal-close-btn" id="confirmClearBtn" style="background:#dc2626;">DELETE ALL LOGS</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script src="/1QCUPROJECT/scripts/admin/audit/admin-audit.js"></script>
</body>
</html>
