<<<<<<< Updated upstream <<<<<<<< Updated upstream:views/staff/dashboard.php <!-- STAFF -->
  <?php
  require_once __DIR__ . '/../../backend/middleware/requireStaff.php';

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
  <?php $activePage = 'assets'; ?>
  <?php include '../../components/staff/sidebar.php'; ?>
  >>>>>>> Stashed changes
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
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
          <button class="add-btn" id="openModalBtn">+ Add Asset</button>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Assets</div>
          <div class="stat-value">512</div>
          <div class="stat-sub">All registered assets</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">In Use</div>
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
          <div class="stat-sub red">Needs attention</div>
        </div>
      </div>

      <!-- Recent Assets Table -->
      <div class="table-section">
        <div class="table-header">
          <h2>Recent Assets</h2>
          <button class="view-all-btn">VIEW ALL</button>
        </div>

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
            <!-- Rows will be populated dynamically from the system -->
            <tr class="empty-row">
              <td colspan="6">No assets to display.</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

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
          </div>
        </div>

        <div class="modal-buttons">
          <button class="modal-edit-btn">EDIT</button>
          <button class="modal-close-btn" onclick="closeModal()">CLOSE</button>
        </div>
      </div>
    </div>

    <script src="../../scripts/staff/staff_script.js"></script>
  </body>

  </html>