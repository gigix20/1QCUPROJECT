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
  <title>ONEQCU - Assets</title>
  <link rel="stylesheet" href="../styles/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'assets'; ?>
<?php require __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">ASSETS</h1>
        <p class="page-sub">Manage and track all of the ONEQCU assets</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn">EXPORT</button>
        <button class="add-btn">+ ADD ASSET</button>
      </div>
    </div>

    <div class="search-bar-full">
      <input type="text" placeholder="Search by asset ID, description, serial number or department...">
    </div>

    <div class="filter-tabs">
      <button class="filter-tab active">ALL ASSETS</button>
      <button class="filter-tab">AVAILABLE</button>
      <button class="filter-tab">IN USE</button>
      <button class="filter-tab">MAINTENANCE</button>
      <button class="filter-tab">CERTIFIED</button>
    </div>

    <div class="table-section">
      <table class="asset-table">
        <thead>
          <tr>
            <th>ASSET ID</th>
            <th>DESCRIPTION</th>
            <th>SERIAL NUMBER</th>
            <th>CATEGORY</th>
            <th>DEPARTMENT</th>
            <th>LOCATION</th>
            <th>STATUS</th>
          </tr>
        </thead>
        <tbody>
          <tr class="empty-row">
            <td colspan="7">No assets to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../scripts/pages_scripts.js"></script>
</body>
</html>
