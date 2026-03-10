
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU - Settings</title>
  <link rel="stylesheet" href="../../styles/admin/admin_style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php $currentPage = 'settings'; ?>
<?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">SYSTEM SETTINGS</h1>
        <p class="page-sub">Configure system preferences and settings</p>
      </div>
      <div class="topbar-actions">
        <button class="add-btn" id="saveSettingsBtn">SAVE CHANGES</button>
      </div>
    </div>

    <div class="settings-layout">
      <!-- Settings Left Nav -->
      <div class="settings-nav">
        <ul>
          <li class="settings-nav-item active" data-panel="system-config">System Configuration</li>
          <li class="settings-nav-item" data-panel="notifications">Notifications</li>
          <li class="settings-nav-item" data-panel="categories">Categories</li>
          <li class="settings-nav-item" data-panel="security">Security</li>
          <li class="settings-nav-item" data-panel="data">Data</li>
          <li class="settings-nav-item" data-panel="system-info">System Info</li>
        </ul>
      </div>

      <!-- Settings Panel -->
      <div class="settings-panel">

        <div class="settings-panel-content active" id="panel-system-config">
          <h3 class="settings-panel-title">System Configuration</h3>
          <div class="settings-field">
            <label>System Name</label>
            <input type="text" placeholder="ONEQCU Asset Manager">
          </div>
          <div class="settings-field">
            <label>Institution</label>
            <input type="text" placeholder="Quezon City University">
          </div>
          <div class="settings-field">
            <label>Academic Year</label>
            <input type="text" placeholder="2025 - 2026">
          </div>
          <div class="settings-field">
            <label>Default Currency</label>
            <input type="text" placeholder="PHP">
          </div>
        </div>

        <div class="settings-panel-content" id="panel-notifications">
          <h3 class="settings-panel-title">Notifications</h3>
          <div class="settings-field">
            <label>Email Notifications</label>
            <input type="text" placeholder="admin@qcu.edu.ph">
          </div>
          <div class="settings-field">
            <label>Overdue Alert (days)</label>
            <input type="text" placeholder="3">
          </div>
        </div>

        <div class="settings-panel-content" id="panel-categories">
          <h3 class="settings-panel-title">Categories</h3>
          <div class="settings-field">
            <label>Asset Categories</label>
            <input type="text" placeholder="e.g. Computer Equipment, Furniture...">
          </div>
        </div>

        <div class="settings-panel-content" id="panel-security">
          <h3 class="settings-panel-title">Security</h3>
          <div class="settings-field">
            <label>Session Timeout (minutes)</label>
            <input type="text" placeholder="30">
          </div>
          <div class="settings-field">
            <label>Password Policy</label>
            <input type="text" placeholder="Minimum 8 characters">
          </div>
        </div>

        <div class="settings-panel-content" id="panel-data">
          <h3 class="settings-panel-title">Data</h3>
          <div class="settings-field">
            <label>Backup Frequency</label>
            <input type="text" placeholder="Daily">
          </div>
          <div class="settings-field">
            <label>Retention Period (days)</label>
            <input type="text" placeholder="365">
          </div>
        </div>

        <div class="settings-panel-content" id="panel-system-info">
          <h3 class="settings-panel-title">System Info</h3>
          <div class="settings-field">
            <label>Version</label>
            <input type="text" placeholder="v1.0.0" disabled>
          </div>
          <div class="settings-field">
            <label>Last Updated</label>
            <input type="text" placeholder="February 2026" disabled>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Save Confirmation Modal -->
  <div class="modal-overlay" id="saveModal">
    <div class="modal" style="max-width:360px; text-align:center;">
      <h2 class="modal-title" style="font-style:normal;">Settings Saved</h2>
      <div class="modal-divider"></div>
      <p style="font-size:13px; color:#555; margin:16px 0 24px;">Your system settings have been saved successfully.</p>
      <button class="modal-close-btn" onclick="closeModal('saveModal')" style="width:100%;">OK</button>
    </div>
  </div>

  <script src="../../scripts/admin/admin_script.js"></script>
</body>
</html>
