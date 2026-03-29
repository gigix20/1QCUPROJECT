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

    <li class="<?= ($currentPage === 'departments') ? 'active' : '' ?>">
      <span class="menu-icon">🏢</span>
      <a href="departments_page.php">Departments</a>
    </li>

    <li class="<?= ($currentPage === 'users') ? 'active' : '' ?>">
      <span class="menu-icon">👤</span>
      <a href="users_page.php">Users</a>
    </li>

    <!-- TEMPORARY PLACEMENT -->
    <li><span class="menu-icon">➜]</span>
      <a href="/1QCUPROJECT/backend/controllers/LogoutController.php" class="logout-btn">
        Logout
      </a>
    </li>

  </ul>

  <!-- PROFILE -->
  <div class="admin-user">
    <div class="avatar">AU</div>
    <div>
      <p class="name">Admin User</p>
      <p class="role">Administrator</p>
    </div>
  </div>
</div>