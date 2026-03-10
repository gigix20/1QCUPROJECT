
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU - Maintenance</title>
  <link rel="stylesheet" href="../../styles/admin/admin_style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'maintenance'; ?>
<?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">MAINTENANCE</h1>
        <p class="page-sub">Track and manage asset maintenance records</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn">EXPORT</button>
        <button class="add-btn">+ New Request</button>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value">0</div>
        <div class="stat-sub yellow">Awaiting service</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">In Progress</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Currently serviced</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value">0</div>
        <div class="stat-sub green">This month</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue</div>
        <div class="stat-value">0</div>
        <div class="stat-sub red">Past schedule</div>
      </div>
    </div>

    <div class="search-bar-full">
      <input type="text" placeholder="Search by asset ID, description, or technician...">
    </div>

    <div class="filter-tabs">
      <button class="filter-tab active">ALL</button>
      <button class="filter-tab">PENDING</button>
      <button class="filter-tab">IN PROGRESS</button>
      <button class="filter-tab">COMPLETED</button>
      <button class="filter-tab">OVERDUE</button>
    </div>

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
        <tbody>
          <tr class="empty-row">
            <td colspan="8">No maintenance records to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../../scripts/admin/admin_script.js"></script>
</body>
</html>
