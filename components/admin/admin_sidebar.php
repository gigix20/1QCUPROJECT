<div class="sidebar">

  <div class="logo">
    <div class="logo-icon">
      <img src="/1QCUPROJECT/assets/img/logo.png" alt="ONEQCU Logo" style="width:42px; height:42px; object-fit:contain;">
    </div>
    <div class="logo-text">
      <span class="logo-title">ONEQCU</span>
      <span class="logo-sub">QCU ASSET MANAGER</span>
    </div>
  </div>

  <ul class="menu">
    <li class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
      <span class="menu-icon">▣</span>
      <a href="dashboard.php">Dashboard</a>
    </li>

    <li class="<?= ($currentPage === 'assets') ? 'active' : '' ?>">
      <span class="menu-icon">◈</span>
      <a href="assets_page.php">Assets</a>
    </li>

    <li class="<?= ($currentPage === 'borrow') ? 'active' : '' ?>">
      <span class="menu-icon">⇄</span>
      <a href="borrow_page.php">Borrow/Return</a>
    </li>

    <li class="<?= ($currentPage === 'maintenance') ? 'active' : '' ?>">
      <span class="menu-icon">⚙</span>
      <a href="maintenance_page.php">Maintenance</a>
    </li>

    <li class="<?= ($currentPage === 'reports') ? 'active' : '' ?>">
      <span class="menu-icon">📊</span>
      <a href="reports_page.php">Reports</a>
    </li>

    <li class="<?= ($currentPage === 'audit') ? 'active' : '' ?>">
      <span class="menu-icon">📝</span>
      <a href="admin-audit.php">Audit</a>
    </li>

    <li class="<?= ($currentPage === 'departments') ? 'active' : '' ?>">
      <span class="menu-icon">🏢</span>
      <a href="departments_page.php">Departments</a>
    </li>

    <li class="<?= ($currentPage === 'users') ? 'active' : '' ?>">
      <span class="menu-icon">👤</span>
      <a href="users_page.php">Users</a>
    </li>

    <li><span class="menu-icon">↩</span>
      <a href="#" class="logout-btn" onclick="openLogoutModal(); return false;">Logout</a>
    </li>

  </ul>

<!-- PROFILE -->
<?php
  $fullName = $_SESSION['full_name'] ?? 'Unknown User';
  $role     = $_SESSION['role']      ?? 'Staff';

  $nameParts = explode(' ', trim($fullName));
  $initials  = strtoupper(
    ($nameParts[0][0] ?? '') .
    (count($nameParts) > 1 ? end($nameParts)[0] : '')
  );
?>
<div class="admin-user">
  <div class="avatar"><?= htmlspecialchars($initials) ?></div>
  <div>
    <p class="name"><?= htmlspecialchars($fullName) ?></p>
    <p class="role"><?= htmlspecialchars($role) ?></p>
  </div>
</div>
</div>

<?php require_once __DIR__ . '/../logout-modal.php'; ?>