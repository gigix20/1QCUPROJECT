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
  <title>ONEQCU | Borrow/Return</title>
  <link rel="stylesheet" href="../../styles/staff/staff_style.css">
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
        <button class="outline-btn">EXPORT</button>
        <button class="add-btn" id="borrowOpenModalBtn">+ New Borrow Request</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Active Borrows</div>
        <div class="stat-value" id="statActiveBorrows">0</div>
        <div class="stat-sub">Currently borrowed</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pending Requests</div>
        <div class="stat-value" id="statPendingReqs">0</div>
        <div class="stat-sub yellow">Awaiting approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue Returns</div>
        <div class="stat-value" id="statOverdue">0</div>
        <div class="stat-sub red">Past due date</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Return This Month</div>
        <div class="stat-value" id="statReturnedMonth">0</div>
        <div class="stat-sub green">On time returns</div>
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar-full">
      <input type="text" id="borrowSearchInput" placeholder="Search by borrower name, Asset ID, or request ID...">
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="filter-tab active">ALL</button>
      <button class="filter-tab">ACTIVE</button>
      <button class="filter-tab">PENDING</button>
      <button class="filter-tab">OVERDUE</button>
      <button class="filter-tab">RETURNED</button>
    </div>

    <!-- Table -->
    <div class="table-section">
      <h2 style="font-size:15px; font-weight:600; margin-bottom:16px;">Borrow Requests</h2>
      <table class="asset-table">
        <thead>
          <tr>
            <th>REQUEST ID</th>
            <th>BORROWER NAME</th>
            <th>ASSET ID</th>
            <th>DEPARTMENT</th>
            <th>BORROW DATE</th>
            <th>DUE DATE</th>
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

  <!-- ========================
       NEW BORROW REQUEST MODAL
  ========================= -->
  <div class="modal-overlay" id="borrowModalOverlay">
    <div class="modal">
      <div class="modal-title">New Borrow Request</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">BORROWER INFORMATION</div>

      <div class="modal-two-col">
        <div class="form-group">
          <label>Borrower Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="borrowerName" placeholder="e.g. Juan dela Cruz">
        </div>
        <div class="form-group">
          <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
          <select id="borrowDepartment">
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
      </div>

      <div class="modal-section-label" style="margin-top:4px;">ASSET DETAILS</div>

      <div class="form-full">
        <label>Asset ID <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="borrowAssetId" placeholder="e.g. AST-0001">
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

  <!-- ========================
       VIEW BORROW DETAILS MODAL
  ========================= -->
  <div class="modal-overlay" id="viewBorrowModal">
    <div class="modal">
      <div class="modal-title">Borrow Request Details</div>
      <div class="modal-divider"></div>

      <div class="modal-section-label">REQUEST INFORMATION</div>
      <div class="modal-info-box">
        <p><strong>Request ID:</strong> <span id="viewReqId"></span></p>
        <p><strong>Status:</strong> <span id="viewStatus" class="badge"></span></p>
        <p><strong>Purpose:</strong> <span id="viewPurpose"></span></p>
      </div>

      <div class="modal-section-label">BORROWER &amp; ASSET</div>
      <div class="modal-two-col" style="margin-bottom:14px;">
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Borrower:</strong> <span id="viewBorrower"></span></p>
          <p><strong>Department:</strong> <span id="viewDept"></span></p>
        </div>
        <div class="modal-info-box" style="margin-bottom:0;">
          <p><strong>Asset ID:</strong> <span id="viewAsset"></span></p>
          <p><strong>Borrow Date:</strong> <span id="viewBorrowDate"></span></p>
          <p><strong>Due Date:</strong> <span id="viewDueDate"></span></p>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-close-btn" id="closeViewBorrowBtn">CLOSE</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script src="../../scripts/staff/staff_script.js"></script>
</body>

</html>