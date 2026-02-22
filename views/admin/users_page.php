
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU - Users</title>
  <link rel="stylesheet" href="../../styles/admin/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'users'; ?>
<?php require __DIR__ . '/../../components/admin/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">USER MANAGEMENT</h1>
        <p class="page-sub">Manage system users and their roles</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn">EXPORT USERS</button>
        <button class="add-btn">+ ADD USER</button>
      </div>
    </div>

    <div class="search-bar-full">
      <input type="text" placeholder="Search users by name, email, department, or role...">
    </div>

    <div class="filter-tabs">
      <button class="filter-tab active">ALL USERS</button>
      <button class="filter-tab">ADMINISTRATORS</button>
      <button class="filter-tab">PROPERTY CUSTODIANS</button>
      <button class="filter-tab">DEPARTMENT STAFF</button>
      <button class="filter-tab">ACTIVE</button>
      <button class="filter-tab">INACTIVE</button>
    </div>

    <div class="table-section">
      <table class="asset-table">
        <thead>
          <tr>
            <th>USER ID</th>
            <th>NAME</th>
            <th>EMAIL</th>
            <th>DEPARTMENT</th>
            <th>ROLE</th>
            <th>STATUS</th>
            <th>LAST LOGIN</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <tr class="empty-row">
            <td colspan="8">No users to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../../scripts/admin/pages_script.js"></script>
</body>
</html>
