<?php
require_once __DIR__ . '/../backend/auth.php';

// If user is not logged in, redirect user to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: /1QCUPROJECT/views/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU - Dashboard</title>
  <link rel="stylesheet" href="../styles/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'dashboard'; ?>
<?php require __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <h1 class="page-title">DASHBOARD</h1>
      <div class="topbar-actions">
        <input type="text" class="search-input" placeholder="Search assets...">
        <button class="add-btn">+ Add Asset</button>
      </div>
    </div>

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
        <div class="stat-label">Maintenance</div>
        <div class="stat-value">24</div>
        <div class="stat-sub red">Needs attention</div>
      </div>
    </div>

    <div class="table-section">
      <div class="table-header">
        <h2>Recent Assets</h2>
        <button class="view-all-btn">VIEW ALL</button>
      </div>
      <table class="asset-table">
        <thead>
          <tr>
            <th>ASSET ID</th>
            <th>DESCRIPTION</th>
            <th>DEPARTMENT</th>
            <th>STATUS</th>
            <th>UPDATED</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <tr class="empty-row">
            <td colspan="6">No assets to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../scripts/pages_script.js"></script>
</body>
</html>
