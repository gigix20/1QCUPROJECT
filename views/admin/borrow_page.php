
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU - Borrow/Return</title>
  <link rel="stylesheet" href="../../styles/admin/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'borrow'; ?>
<?php require __DIR__ . '/../../components/admin/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">BORROW & RETURN</h1>
        <p class="page-sub">Track asset borrowing and return transactions</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn">EXPORT</button>
        <button class="add-btn">+ New Borrow Request</button>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Active Borrows</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Currently borrowed</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pending Requests</div>
        <div class="stat-value">0</div>
        <div class="stat-sub yellow">Awaiting approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue Returns</div>
        <div class="stat-value">0</div>
        <div class="stat-sub red">Past due date</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Return This Month</div>
        <div class="stat-value">0</div>
        <div class="stat-sub green">On time returns</div>
      </div>
    </div>

    <div class="search-bar-full">
      <input type="text" placeholder="Search by borrower name, Asset ID, or request ID...">
    </div>

    <div class="filter-tabs">
      <button class="filter-tab active">ALL</button>
      <button class="filter-tab">ACTIVE</button>
      <button class="filter-tab">PENDING</button>
      <button class="filter-tab">OVERDUE</button>
      <button class="filter-tab">RETURNED</button>
    </div>

    <div class="table-section">
      <h2 style="font-size:15px; font-weight:600; margin-bottom:16px;">Borrow Requests</h2>
      <table class="asset-table">
        <thead>
          <tr>
            <th>REQUEST ID</th>
            <th>BORROWER NAME</th>
            <th>ASSET</th>
            <th>DEPARTMENT</th>
            <th>BORROW DATE</th>
            <th>DUE DATE</th>
            <th>STATUS</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <tr class="empty-row">
            <td colspan="8">No borrow requests to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../../scripts/admin/pages_script.js"></script>
</body>
</html>
