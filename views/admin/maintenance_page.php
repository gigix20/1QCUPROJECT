<?php
require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Admin Maintenance</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-stats.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <?php $currentPage = 'maintenance'; ?>
  <?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <!-- MAIN -->
  <div class="main">

    <div class="topbar">
      <div>
        <h1 class="page-title">MAINTENANCE</h1>
        <p class="page-sub">Track and manage asset maintenance records</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn" id="maintExportBtn">EXPORT</button>
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
        <div class="stat-label">Cancelled</div>
        <div class="stat-value" id="maintStatCancelled">0</div>
        <div class="stat-sub red">Cancelled requests</div>
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar-full">
      <span class="search-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="text" id="maintSearchInput" placeholder="Search by asset ID, description, type, or technician...">
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="filter-tab active">ALL</button>
      <button class="filter-tab">PENDING</button>
      <button class="filter-tab">IN PROGRESS</button>
      <button class="filter-tab">COMPLETED</button>
      <button class="filter-tab">CANCELLED</button>
    </div>

    <!-- Table -->
    <div class="table-section">
      <h2 style="font-size:15px;font-weight:600;margin-bottom:16px;">Maintenance Records</h2>
      <table class="asset-table">
        <thead>
          <tr>
            <th>REQUEST ID</th>
            <th>ASSET ID</th>
            <th>DESCRIPTION</th>
            <th>DEPARTMENT</th>
            <th>TYPE</th>
            <th>TECHNICIAN</th>
            <th>SCHEDULED DATE</th>
            <th>NOTES</th>
            <th>STATUS</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody id="maintTableBody">
          <tr class="empty-row">
            <td colspan="10">No maintenance records to display.</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>


  <!-- NEW MAINTENANCE REQUEST MODAL -->
  <div class="modal-overlay" id="maintModalOverlay">
    <div class="modal">
      <div class="modal-title">New Maintenance Request</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">ASSET DETAILS</div>

      <div class="form-full">
        <label>Asset ID <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="maintAssetId" placeholder="e.g. AST-CICS-0002-0001">
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Description
            <span style="font-size:10px;color:#a78bfa;font-weight:400;">(auto-fetched)</span>
          </label>
          <input type="text" id="maintAssetDesc" readonly placeholder="—">
        </div>
        <div class="form-group">
          <label>Department
            <span style="font-size:10px;color:#a78bfa;font-weight:400;">(auto-fetched)</span>
          </label>
          <input type="text" id="maintAssetDept" readonly placeholder="—">
        </div>
      </div>

      <div class="form-full">
        <label>Maintenance Type <span style="color:#dc2626;font-weight:700;">*</span></label>
        <select id="maintType">
          <option value="">-- Select Type --</option>
        </select>
      </div>

      <div class="form-full">
        <label>Issue Description <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="maintIssueDescription" placeholder="e.g. Screen flickering, needs display replacement">
      </div>

      <div class="modal-section-label" style="margin-top:4px;">TECHNICIAN</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>First Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="maintTechFirstName" placeholder="e.g. Pedro">
        </div>
        <div class="form-group">
          <label>Middle Name</label>
          <input type="text" id="maintTechMiddleName" placeholder="e.g. Santos">
        </div>
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Last Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="maintTechLastName" placeholder="e.g. Reyes">
        </div>
        <div class="form-group">
          <label>Suffix</label>
          <input type="text" id="maintTechSuffix" placeholder="e.g. Jr.">
        </div>
      </div>

      <div class="form-full">
        <label>Scheduled Date <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="date" id="maintScheduledDate">
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


  <!-- VIEW MAINTENANCE DETAILS MODAL -->
  <div class="modal-overlay" id="viewMaintModal">
    <div class="modal">
      <div class="modal-title">Maintenance Details</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">REQUEST INFORMATION</div>
      <div class="modal-info-box">
        <p><strong>Request ID:</strong> <span id="vmMaintId"></span></p>
        <p><strong>Status:</strong> <span id="vmStatus" class="badge"></span></p>
        <p><strong>Scheduled Date:</strong> <span id="vmScheduled"></span></p>
        <p><strong>Completed Date:</strong> <span id="vmCompleted"></span></p>
      </div>

      <div class="modal-section-label">ASSET &amp; TASK</div>
      <div class="modal-two-col" style="margin-bottom:14px;">
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Asset ID:</strong> <span id="vmAssetId"></span></p>
          <p><strong>Description:</strong> <span id="vmAssetDesc"></span></p>
          <p><strong>Department:</strong> <span id="vmDept"></span></p>
          <p><strong>Type:</strong> <span id="vmType"></span></p>
          <p><strong>Issue:</strong> <span id="vmIssue"></span></p>
        </div>
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Technician:</strong> <span id="vmTech"></span></p>
          <p><strong>Notes:</strong> <span id="vmNotes"></span></p>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-close-btn" id="closeViewMaintBtn">CLOSE</button>
      </div>
    </div>
  </div>


  <!-- EXPORT MODAL -->
  <div class="modal-overlay" id="maintExportModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-title">Export Maintenance Records</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">EXPORT OPTIONS</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Data Scope</label>
          <select id="maintExportScope">
            <option value="all">All Records</option>
            <option value="filtered">Current View Only</option>
          </select>
        </div>
        <div class="form-group" style="justify-content:flex-end;padding-bottom:4px;">
          <label>Options</label>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;
                        font-weight:400;color:#333;cursor:pointer;
                        text-transform:none;letter-spacing:0;">
            <input type="checkbox" id="maintExportIncludeCancelled" style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
            Include cancelled records
          </label>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="maintExportCancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="maintExportConfirmBtn">⬇ EXPORT PDF</button>
      </div>
    </div>
  </div>


  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script src="../../scripts/admin/maintenance/admin-maintenance.js"></script>
  <script src="../../scripts/admin/maintenance/admin-maintenance-modals.js"></script>
  <script src="../../scripts/admin/maintenance/admin-maintenance-export.js"></script>
  <script src="../../scripts/admin/maintenance/admin-maintenance-init.js"></script>

</body>

</html>