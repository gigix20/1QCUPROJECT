<div class="sidebar">

    <!-- Logo -->
    <div class="logo">
        <div class="logo-icon">
            <img src="/1QCUPROJECT/assets/img/logo.png" alt="ONEQCU Logo" style="width:42px; height:42px; object-fit:contain;">
        </div>
        <div class="logo-text">
            <span class="logo-title">ONEQCU</span>
            <span class="logo-sub">QCU ASSET MANAGER</span>
        </div>
    </div>

    <!-- Menu -->
    <ul class="menu">
        <li class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
            <span class="menu-icon">▣</span>
            <a href="dashboard.php">Dashboard</a>
        </li>
        <li class="<?= ($currentPage === 'assets') ? 'active' : '' ?>">
            <span class="menu-icon">◈</span>
            <a href="assets.php">Assets</a>
        </li>
        <li class="<?= ($currentPage === 'borrow') ? 'active' : '' ?>">
            <span class="menu-icon">⇄</span>
            <a href="borrow.php">Borrow/Return</a>
        </li>
        <li class="<?= ($currentPage === 'maintenance') ? 'active' : '' ?>">
            <span class="menu-icon">⚙</span>
            <a href="maintenance.php">Maintenance</a>
        </li>
        <li class="<?= ($currentPage === 'reports') ? 'active' : '' ?>">
            <span class="menu-icon">📊</span>
            <a href="reports.php">Reports</a>
        </li>

        <!-- <li class="<?= ($currentPage === 'departments') ? 'active' : '' ?>">
            <span class="menu-icon">🏢</span>
            <a href="departments.php">Departments</a>
        </li> -->

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