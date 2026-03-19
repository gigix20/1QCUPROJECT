
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU - Departments</title>
  <link rel="stylesheet" href="../../styles/admin/admin-department.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'departments'; ?>
<?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">DEPARTMENTS</h1>
        <p class="page-sub">Manage university departments and locations</p>
      </div>
      <div class="topbar-actions">
        <button class="add-btn">+ ADD DEPARTMENT</button>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Departments</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Across campus</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Buildings</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Campus locations</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Property Custodians</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Assigned</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Assets</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Under management</div>
      </div>
    </div>

    <div class="search-bar-full">
      <input type="text" placeholder="Search by name, buildings, or custodian...">
    </div>

    <div class="table-section">
      <h2 class="section-title">DEPARTMENT LISTS</h2>
      <table class="asset-table">
        <thead>
          <tr>
            <th>DEPT NAME</th>
            <th>BUILDING / LOCATION</th>
            <th>DEPARTMENT HEAD</th>
            <th>PROPERTY CUSTODIAN</th>
            <th>TOTAL ASSETS</th>
            <th>STATUS</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <tr class="empty-row">
            <td colspan="7">No departments to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../../scripts/admin/departments/admin-departments.js"></script>
</body>
</html>
