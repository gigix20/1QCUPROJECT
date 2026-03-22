  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo">
      <div class="logo-icon">
        <img src="logo.png" alt="ONEQCU Logo" style="width:42px; height:42px; object-fit:contain;">
      </div>
      <div class="logo-text">
        <span class="logo-title">ONEQCU</span>
        <span class="logo-sub">QCU ASSET MANAGER</span>
      </div>
    </div>
        <ul class="menu">
        <li class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <span class="menu-icon">&#9635;</span><a href="dashboard.php">Dashboard</a>
        </li>
        <li class="<?= $activePage === 'assets' ? 'active' : '' ?>">
            <span class="menu-icon">&#10792;</span><a href="assets.php">Assets</a>
        </li>
        <li class="<?= $activePage === 'borrow' ? 'active' : '' ?>">
            <span class="menu-icon">&#8644;</span><a href="borrow.php">Borrow/Return</a>
        </li>
        <li class="<?= $activePage === 'maintenance' ? 'active' : '' ?>">
            <span class="menu-icon">&#9881;</span><a href="maintenance.php">Maintenance</a>
        </li>
        <li class="<?= $activePage === 'reports' ? 'active' : '' ?>">
            <span class="menu-icon">&#128202;</span><a href="reports.php">Reports</a>
        </li>

        <li><span class="menu-icon">➜]</span>
        <a href="/1QCUPROJECT/backend/controllers/LogoutController.php" class="logout-btn">
        Logout
        </a>
      </li>
        </ul>
    <div class="admin-user">
      <div class="avatar">SU</div>
      <div>
        <p class="name">Staff User</p>
        <p class="role">Staff Member</p>
      </div>
    </div>
  </div>