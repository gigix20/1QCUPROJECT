<?php
require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Admin Users Management</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-users.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

  <?php $currentPage = 'users'; ?>
  <?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">USERS MANAGEMENT</h1>
        <p class="page-sub">Manage system users and their roles</p>
      </div>
      <div class="header">
        <div class="topbar-actions">
          <button class="add-btn" onclick="openModal('addUserModal')">+ Add User</button>
        </div>
        <!-- Notification system will be inserted here by JavaScript -->
      </div>
    </div>

    <div class="stats-row" id="statsRow">
      <div class="stat-card">
        <div class="stat-label">TOTAL USERS</div>
        <div class="stat-value" id="statTotal">—</div>
        <div class="stat-sub">Registered accounts</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">ADMINISTRATORS</div>
        <div class="stat-value" id="statAdmin">—</div>
        <div class="stat-sub">Admin role</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">STAFF</div>
        <div class="stat-value" id="statStaff">—</div>
        <div class="stat-sub">Staff role</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">VERIFIED</div>
        <div class="stat-value" id="statVerified">—</div>
        <div class="stat-sub green">Active accounts</div>
      </div>
    </div>

    <div class="search-bar-full">
      <span class="search-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="text" id="searchInput" placeholder="Search by name, email, employee ID, or department...">
    </div>

    <div class="filter-tabs">
      <button class="filter-tab active">ALL USERS</button>
      <button class="filter-tab">ADMINISTRATORS</button>
      <button class="filter-tab">STAFF</button>
      <button class="filter-tab">VERIFIED</button>
      <button class="filter-tab">UNVERIFIED</button>
    </div>

    <div class="table-section">
      <table class="asset-table">
        <thead>
          <tr>
            <th>USER ID</th>
            <th>NAME</th>
            <th>EMAIL</th>
            <th>DEPARTMENT</th>
            <th>EMPLOYEE ID</th>
            <th>ROLE</th>
            <th>STATUS</th>
            <th>CREATED</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody id="usersTableBody">
          <tr class="empty-row">
            <td colspan="9">Loading users...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ADD USER MODAL -->
  <div class="modal-overlay" id="addUserModal">
    <div class="modal dept-modal">
      <div class="dept-modal-header">
        <div>
          <div class="dept-modal-eyebrow">Users Management</div>
          <div class="modal-title">Add New User</div>
        </div>
        <button class="dept-modal-x" onclick="closeModal('addUserModal')">✕</button>
      </div>
      <div class="modal-divider"></div>

      <div class="dept-form-grid">
        <div class="form-group">
          <label>FULL NAME <span class="req">*</span></label>
          <input type="text" id="addFullName" placeholder="e.g. Juan dela Cruz">
          <div class="dept-form-error" id="errAddFullName"></div>
        </div>
        <div class="form-group">
          <label>EMPLOYEE ID <span class="req">*</span></label>
          <input type="text" id="addEmployeeId" placeholder="e.g. EMP-2025-001">
          <div class="dept-form-error" id="errAddEmployeeId"></div>
        </div>
        <div class="form-group">
          <label>EMAIL <span class="req">*</span></label>
          <input type="email" id="addEmail" placeholder="e.g. juan@qcu.edu.ph">
          <div class="dept-form-error" id="errAddEmail"></div>
        </div>
        <div class="form-group">
          <label>DEPARTMENT <span class="req">*</span></label>
          <select id="addDepartment">
            <option value="">Select department...</option>
          </select>
          <div class="dept-form-error" id="errAddDepartment"></div>
        </div>
        <div class="form-group">
          <label>PASSWORD <span class="req">*</span></label>
          <input type="password" id="addPassword" placeholder="Temporary password">
          <div class="dept-form-error" id="errAddPassword"></div>
        </div>
        <div class="form-group">
          <label>ROLE</label>
          <select id="addRole">
            <option value="Staff">Staff (default)</option>
            <option value="Admin">Administrator</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" onclick="closeModal('addUserModal')">Cancel</button>
        <button class="modal-close-btn" onclick="submitAddUser()">Add User</button>
      </div>
    </div>
  </div>

  <!-- EDIT USER MODAL -->
  <div class="modal-overlay" id="editUserModal">
    <div class="modal dept-modal">
      <div class="dept-modal-header">
        <div>
          <div class="dept-modal-eyebrow">Users Management</div>
          <div class="modal-title">Edit User</div>
        </div>
        <button class="dept-modal-x" onclick="closeModal('editUserModal')">✕</button>
      </div>
      <div class="modal-divider"></div>

      <input type="hidden" id="editUserId">

      <div class="dept-form-grid">
        <div class="form-group">
          <label>FULL NAME <span class="req">*</span></label>
          <input type="text" id="editFullName">
          <div class="dept-form-error" id="errEditFullName"></div>
        </div>
        <div class="form-group">
          <label>EMPLOYEE ID <span class="req">*</span></label>
          <input type="text" id="editEmployeeId">
          <div class="dept-form-error" id="errEditEmployeeId"></div>
        </div>
        <div class="form-group">
          <label>EMAIL <span class="req">*</span></label>
          <input type="email" id="editEmail">
          <div class="dept-form-error" id="errEditEmail"></div>
        </div>
        <div class="form-group">
          <label>DEPARTMENT <span class="req">*</span></label>
          <select id="editDepartment"></select>
          <div class="dept-form-error" id="errEditDepartment"></div>
        </div>
        <div class="form-group">
          <label>NEW PASSWORD <span style="color:#888;font-size:10px;">(leave blank to keep)</span></label>
          <input type="password" id="editPassword" placeholder="Leave blank to keep current">
        </div>
        <div class="form-group">
          <label>ROLE</label>
          <select id="editRole">
            <option value="Staff">Staff</option>
            <option value="Admin">Administrator</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" onclick="closeModal('editUserModal')">Cancel</button>
        <button class="modal-close-btn" onclick="submitEditUser()">Save Changes</button>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRM MODAL -->
  <div class="modal-overlay" id="deleteUserModal">
    <div class="modal" style="width:420px;">
      <div class="dept-modal-header">
        <div>
          <div class="dept-modal-eyebrow">Users Management</div>
          <div class="modal-title">Remove User</div>
        </div>
        <button class="dept-modal-x" onclick="closeModal('deleteUserModal')">✕</button>
      </div>
      <div class="modal-divider"></div>
      <div class="modal-info-box">
        <p>You are about to remove <strong id="deleteUserName"></strong>.</p>
        <p>This action cannot be undone.</p>
      </div>
      <input type="hidden" id="deleteUserId">
      <div class="modal-buttons">
        <button class="modal-edit-btn" onclick="closeModal('deleteUserModal')">Cancel</button>
        <button class="delete-confirm-btn" onclick="submitDeleteUser()">Remove User</button>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"></div>

  <script src="../../scripts/admin/user/admin-user.js"></script>
</body>

</html>