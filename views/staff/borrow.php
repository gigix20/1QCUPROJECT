<?php
require_once __DIR__ . '/../../backend/middleware/requireStaff.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Borrow/Return</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/staff/staff-stats.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/notifications.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

  <?php $currentPage = 'borrow'; ?>
  <?php require __DIR__ . '/../../components/staff/staff_sidebar.php'; ?>

  <!-- MAIN -->
  <div class="main">

    <div class="topbar">
      <div>
        <h1 class="page-title">BORROW &amp; RETURN</h1>
        <p class="page-sub">Track asset borrowing and return transactions</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn" id="borrowExportBtn">EXPORT</button>
        <button class="add-btn" id="borrowOpenModalBtn">+ New Borrow Request</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Pending Requests</div>
        <div class="stat-value" id="statPendingBorrows">0</div>
        <div class="stat-sub yellow">Awaiting approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Borrows</div>
        <div class="stat-value" id="statActiveBorrows">0</div>
        <div class="stat-sub">Currently borrowed</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue Borrows</div>
        <div class="stat-value" id="statOverdue">0</div>
        <div class="stat-sub red">Past due date</div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Overdue Returns</div>
        <div class="stat-value" id="statOverdueReturns">0</div>
        <div class="stat-sub red">Returned late</div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Returned This Month</div>
        <div class="stat-value" id="statReturnedMonth">0</div>
        <div class="stat-sub">Total Returned this month</div>
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar-full">
      <input type="text" id="borrowSearchInput" placeholder="Search by borrower name, Asset ID, or request ID...">
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="filter-tab active">ALL</button>
      <button class="filter-tab">PENDING</button>
      <button class="filter-tab">ACTIVE</button>
      <button class="filter-tab">OVERDUE</button>
      <button class="filter-tab">OVERDUE RETURNS</button>
      <button class="filter-tab">RETURNED</button>
      <button class="filter-tab">CANCELLED</button>
    </div>

    <!-- Table -->
    <div class="table-section">
      <h2 style="font-size:15px;font-weight:600;margin-bottom:16px;">Borrow Requests</h2>
      <table class="asset-table">
        <thead>
          <tr>
            <th>REQUEST ID</th>
            <th>BORROWER NAME</th>
            <th>ASSET ID</th>
            <th>DEPARTMENT</th>
            <th>REASON FOR BORROWING</th>
            <th>BORROW DATE</th>
            <th>DUE DATE</th>
            <th>RETURN DATE</th>
            <th>RETURN STATUS</th>
            <th>STATUS</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody id="borrowTableBody">
          <tr class="empty-row">
            <td colspan="8">No borrow requests to display.</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>


  <!--NEW BORROW REQUEST MODAL -->
  <div class="modal-overlay" id="borrowModalOverlay">
    <div class="modal">
      <div class="modal-title">New Borrow Request</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">BORROWER INFORMATION</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>First Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="borrowFirstName" placeholder="e.g. Juan">
        </div>
        <div class="form-group">
          <label>Middle Name</label>
          <input type="text" id="borrowMiddleName" placeholder="e.g. Santos">
        </div>
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Last Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="borrowLastName" placeholder="e.g. dela Cruz">
        </div>
        <div class="form-group">
          <label>Suffix</label>
          <input type="text" id="borrowSuffix" placeholder="e.g. Jr., III">
        </div>
      </div>

      <div class="form-full">
        <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
        <select id="borrowDepartment">
          <option value="">-- Select Department --</option>
        </select>
      </div>

      <div class="modal-section-label" style="margin-top:4px;">ASSET DETAILS</div>

      <div class="form-full">
        <label>Asset ID <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="borrowAssetId" placeholder="e.g. AST-CICS-0002-0001">
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Item Description
            <span style="font-size:10px;color:#a78bfa;font-weight:400;">(auto-fetched)</span>
          </label>
          <input type="text" id="borrowAssetDesc" readonly placeholder="—">
        </div>
        <div class="form-group">
          <label>Liable Person
            <span style="font-size:10px;color:#a78bfa;font-weight:400;">(auto-fetched)</span>
          </label>
          <input type="text" id="borrowLiablePerson" readonly placeholder="—">
        </div>
      </div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Borrow Date <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="date" id="borrowDate">
        </div>
        <div class="form-group">
          <label>Due Date <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="date" id="dueDate">
        </div>
      </div>

      <div class="form-full">
        <label>Purpose</label>
        <input type="text" id="borrowPurpose" placeholder="e.g. For classroom presentation use">
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="borrowCancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="borrowSaveBtn">SUBMIT REQUEST</button>
      </div>

    </div>
  </div>


  <!--VIEW BORROW DETAILS MODAL-->
  <div class="modal-overlay" id="viewBorrowModal">
    <div class="modal">
      <div class="modal-title">Borrow Request Details</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">REQUEST INFORMATION</div>
      <div class="modal-info-box">
        <p><strong>Request ID:</strong> <span id="viewReqId"></span></p>
        <p><strong>Status:</strong> <span id="viewStatus" class="badge"></span></p>
        <p><strong>Purpose:</strong> <span id="viewPurpose"></span></p>
        <p><strong>Remarks:</strong> <span id="viewRemarks"></span></p>
      </div>

      <div class="modal-section-label">BORROWER &amp; ASSET</div>
      <div class="modal-two-col" style="margin-bottom:14px;">
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Borrower:</strong> <span id="viewBorrower"></span></p>
          <p><strong>Department:</strong> <span id="viewDept"></span></p>
          <p><strong>Liable Person:</strong> <span id="viewLiable"></span></p>
        </div>
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Asset ID:</strong> <span id="viewAsset"></span></p>
          <p><strong>Description:</strong> <span id="viewAssetDesc"></span></p>
          <p><strong>Borrow Date:</strong> <span id="viewBorrowDate"></span></p>
          <p><strong>Due Date:</strong> <span id="viewDueDate"></span></p>
          <p><strong>Return Date:</strong> <span id="viewReturnDate"></span></p>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-close-btn" id="closeViewBorrowBtn">CLOSE</button>
      </div>
    </div>
  </div>


  <!--RETURN MODAL-->
  <div class="modal-overlay" id="returnModal">
    <div class="modal" style="max-width:460px;">
      <div class="modal-title">Return Asset</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">ASSET BEING RETURNED</div>
      <div class="modal-info-box">
        <p><strong>Asset ID:</strong> <span id="returnModalAssetId"></span></p>
        <p><strong>Description:</strong> <span id="returnModalAssetDesc"></span></p>
        <p><strong>Due Date:</strong> <span id="returnModalDueDate"></span></p>
      </div>

      <div class="modal-section-label" style="margin-top:4px;">RETURN DETAILS</div>

      <div class="form-full">
        <label>Return Date <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="date" id="returnDate">
      </div>

      <div class="form-full">
        <label>Remarks</label>
        <input type="text" id="returnRemarks" placeholder="e.g. Returned in good condition">
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelReturnBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveReturnBtn">CONFIRM RETURN</button>
      </div>
    </div>
  </div>

  <!-- EXPORT MODAL -->
  <div class="modal-overlay" id="borrowExportModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-title">Export Borrow Records</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">EXPORT OPTIONS</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Data Scope</label>
          <select id="borrowExportScope">
            <option value="all">All Borrows</option>
            <option value="filtered">Current View Only</option>
          </select>
        </div>
        <div class="form-group" style="justify-content:flex-end;padding-bottom:4px;">
          <label>Options</label>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;
                      font-weight:400;color:#333;cursor:pointer;
                      text-transform:none;letter-spacing:0;">
            <input type="checkbox" id="borrowExportIncludeCancelled" style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
            Include cancelled borrows
          </label>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="borrowExportCancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="borrowExportConfirmBtn">⬇ EXPORT PDF</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>
  <script src="../../scripts/staff/borrows/staff-borrows.js"></script>
  <script src="../../scripts/staff/borrows/staff-borrows-modals.js"></script>
  <script src="../../scripts/staff/borrows/staff-borrows-export.js"></script>
  <script src="../../scripts/staff/borrows/staff-borrows-init.js"></script>

</body>

</html>