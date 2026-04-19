<?php
require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Admin Departments</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-stats.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sections.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

  <?php $currentPage = 'departments'; ?>
  <?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">

    <!-- ══════════════════════════════════════════════════════════
         TOPBAR
    ══════════════════════════════════════════════════════════ -->
    <div class="topbar">
      <div>
        <h1 class="page-title">DEPARTMENTS & CUSTODIANS</h1>
        <p class="page-sub">Manage university departments and their property custodians</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         STATS ROW
    ══════════════════════════════════════════════════════════ -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Departments</div>
        <div class="stat-value" id="statTotalDepts">0</div>
        <div class="stat-sub">Registered departments</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Buildings</div>
        <div class="stat-value" id="statBuildings">0</div>
        <div class="stat-sub">Distinct locations</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Departments</div>
        <div class="stat-value" id="statActiveDepts">0</div>
        <div class="stat-sub green">Currently active</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Assets</div>
        <div class="stat-value" id="statTotalAssets">0</div>
        <div class="stat-sub">Under management</div>
      </div>
    </div>


    <!-- ══════════════════════════════════════════════════════════
         SECTION 1 — DEPARTMENTS
    ══════════════════════════════════════════════════════════ -->
    <div class="page-section">

      <div class="section-topbar">
        <div>
          <h2 class="section-heading">DEPARTMENTS</h2>
          <p class="section-desc">University departments and their asset totals</p>
        </div>
        <button class="add-btn" id="openAddDeptBtn">+ ADD DEPARTMENT</button>
      </div>

      <div class="search-bar-full">
        <span class="search-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input type="text" id="deptSearchInput" placeholder="Search by department name, building, or head...">
      </div>

      <div class="table-section">
        <table class="asset-table">
          <thead>
            <tr>
              <th>DEPT NAME</th>
              <th>BUILDING / LOCATION</th>
              <th>DEPARTMENT HEAD</th>
              <th style="text-align:center;">TOTAL ASSETS</th>
              <th>STATUS</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="deptTableBody">
            <tr class="empty-row">
              <td colspan="6">No departments to display.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div><!-- /.page-section -->


    <!-- ══════════════════════════════════════════════════════════
         SECTION 2 — PROPERTY CUSTODIANS
    ══════════════════════════════════════════════════════════ -->
    <div class="page-section">

      <div class="section-topbar">
        <div>
          <h2 class="section-heading">ASSET CUSTODIANS</h2>
          <p class="section-desc">Faculty members assigned as liable persons for department assets</p>
        </div>
        <button class="add-btn" id="openAddCustBtn">+ ADD CUSTODIAN</button>
      </div>

      <div class="search-bar-full">
        <span class="search-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input type="text" id="custSearchInput" placeholder="Search by name, employee ID, department, or email...">
      </div>

      <div class="table-section">
        <table class="asset-table">
          <thead>
            <tr>
              <th>NAME</th>
              <th>EMPLOYEE ID</th>
              <th>DEPARTMENT</th>
              <th>EMAIL</th>
              <th>PHONE</th>
              <th style="text-align:center;">ASSETS</th>
              <th>STATUS</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="custTableBody">
            <tr class="empty-row">
              <td colspan="8">No custodians to display.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div><!-- /.page-section -->

  </div><!-- /.main -->


  <!-- ══════════════════════════════════════════════════════════
       MODAL: ADD DEPARTMENT
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="addDeptModal">
    <div class="modal" style="width:520px;">
      <div class="modal-title">Add Department</div>
      <div class="modal-divider"></div>

      <p class="modal-section-label">DEPARTMENT INFORMATION</p>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Department Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="addDeptName" placeholder="e.g. CICS">
          <div class="dept-form-error" id="errAddName"></div>
        </div>
        <div class="form-group">
          <label>Building / Location</label>
          <input type="text" id="addBuilding" placeholder="e.g. Engineering Building A">
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Department Head</label>
          <input type="text" id="addDeptHead" placeholder="e.g. Dr. Juan dela Cruz">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="addStatus">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelAddDeptBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveAddDeptBtn">ADD DEPARTMENT</button>
      </div>
    </div>
  </div>


  <!-- ══════════════════════════════════════════════════════════
       MODAL: EDIT DEPARTMENT
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="editDeptModal">
    <div class="modal" style="width:520px;">
      <div class="modal-title">Edit Department</div>
      <div class="modal-divider"></div>

      <input type="hidden" id="editDeptId">

      <p class="modal-section-label">DEPARTMENT INFORMATION</p>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Department Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="editDeptName" placeholder="e.g. CICS">
          <div class="dept-form-error" id="errEditName"></div>
        </div>
        <div class="form-group">
          <label>Building / Location</label>
          <input type="text" id="editBuilding" placeholder="e.g. Engineering Building A">
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Department Head</label>
          <input type="text" id="editDeptHead" placeholder="e.g. Dr. Juan dela Cruz">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="editStatus">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelEditDeptBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveEditDeptBtn">SAVE CHANGES</button>
      </div>
    </div>
  </div>


  <!-- ══════════════════════════════════════════════════════════
       MODAL: DELETE DEPARTMENT
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="deleteDeptModal">
    <div class="modal" style="max-width:440px;">
      <div class="modal-title" style="color:#dc2626;">Delete Department</div>
      <div class="modal-divider" style="background:linear-gradient(to right,#dc2626,transparent);"></div>

      <div class="modal-info-box">
        <p style="font-style:normal;font-size:13px;color:#333;font-weight:600;">
          You are about to delete:
          <strong id="deleteDeptName" style="color:#1a1a2e;"></strong>
        </p>
        <p style="font-style:normal;font-size:12px;color:#666;margin-top:4px;">
          This action cannot be undone.
        </p>
      </div>

      <div id="deleteAssetWarning" style="display:none;background:#fff8f8;border-left:3px solid #dc2626;
               border-radius:6px;padding:12px 14px;margin-bottom:16px;
               font-size:12px;color:#b91c1c;font-weight:600;"></div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelDeleteDeptBtn">CANCEL</button>
        <button id="confirmDeleteDeptBtn" style="padding:10px 36px;background:#dc2626;color:white;border:none;
                 border-radius:8px;font-family:'Outfit',sans-serif;font-size:13px;
                 font-weight:700;letter-spacing:1px;cursor:pointer;transition:background 0.2s;" onmouseover="if(!this.disabled)this.style.background='#b91c1c'" onmouseout="if(!this.disabled)this.style.background='#dc2626'">
          DELETE
        </button>
      </div>
    </div>
  </div>


  <!-- ══════════════════════════════════════════════════════════
       MODAL: ADD CUSTODIAN
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="addCustModal">
    <div class="modal" style="width:620px;">
      <div class="modal-title">Add Property Custodian</div>
      <div class="modal-divider"></div>

      <p class="modal-section-label">ASSIGNMENT</p>
      <div class="form-full" style="margin-bottom:14px;">
        <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
        <select id="addCustDept">
          <option value="">-- Select Department --</option>
        </select>
        <div class="dept-form-error" id="errAddCustDept"></div>
      </div>

      <p class="modal-section-label">PERSONAL INFORMATION</p>
      <div class="modal-two-col">
        <div class="form-group">
          <label>First Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="addCustFirst" placeholder="e.g. Maria">
          <div class="dept-form-error" id="errAddCustFirst"></div>
        </div>
        <div class="form-group">
          <label>Middle Name</label>
          <input type="text" id="addCustMiddle" placeholder="e.g. Santos">
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Last Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="addCustLast" placeholder="e.g. Reyes">
          <div class="dept-form-error" id="errAddCustLast"></div>
        </div>
        <div class="form-group">
          <label>Suffix</label>
          <input type="text" id="addCustSuffix" placeholder="e.g. Jr., Sr., III">
        </div>
      </div>

      <p class="modal-section-label">CONTACT & IDENTIFICATION</p>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Employee ID</label>
          <input type="text" id="addCustEmpId" placeholder="e.g. EMP-2024-001">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="addCustEmail" placeholder="e.g. maria.reyes@qcu.edu.ph">
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" id="addCustPhone" placeholder="e.g. 09171234567">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="addCustStatus">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelAddCustBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveAddCustBtn">ADD CUSTODIAN</button>
      </div>
    </div>
  </div>


  <!-- ══════════════════════════════════════════════════════════
       MODAL: EDIT CUSTODIAN
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="editCustModal">
    <div class="modal" style="width:620px;">
      <div class="modal-title">Edit Property Custodian</div>
      <div class="modal-divider"></div>

      <input type="hidden" id="editCustId">

      <p class="modal-section-label">ASSIGNMENT</p>
      <div class="form-full" style="margin-bottom:14px;">
        <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
        <select id="editCustDept">
          <option value="">-- Select Department --</option>
        </select>
        <div class="dept-form-error" id="errEditCustDept"></div>
      </div>

      <p class="modal-section-label">PERSONAL INFORMATION</p>
      <div class="modal-two-col">
        <div class="form-group">
          <label>First Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="editCustFirst" placeholder="e.g. Maria">
          <div class="dept-form-error" id="errEditCustFirst"></div>
        </div>
        <div class="form-group">
          <label>Middle Name</label>
          <input type="text" id="editCustMiddle" placeholder="e.g. Santos">
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Last Name <span style="color:#dc2626;font-weight:700;">*</span></label>
          <input type="text" id="editCustLast" placeholder="e.g. Reyes">
          <div class="dept-form-error" id="errEditCustLast"></div>
        </div>
        <div class="form-group">
          <label>Suffix</label>
          <input type="text" id="editCustSuffix" placeholder="e.g. Jr., Sr., III">
        </div>
      </div>

      <p class="modal-section-label">CONTACT & IDENTIFICATION</p>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Employee ID</label>
          <input type="text" id="editCustEmpId" placeholder="e.g. EMP-2024-001">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="editCustEmail" placeholder="e.g. maria.reyes@qcu.edu.ph">
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" id="editCustPhone" placeholder="e.g. 09171234567">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="editCustStatus">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelEditCustBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveEditCustBtn">SAVE CHANGES</button>
      </div>
    </div>
  </div>


  <!-- ══════════════════════════════════════════════════════════
       MODAL: DELETE CUSTODIAN
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="deleteCustModal">
    <div class="modal" style="max-width:460px;">
      <div class="modal-title" style="color:#dc2626;">Delete Custodian</div>
      <div class="modal-divider" style="background:linear-gradient(to right,#dc2626,transparent);"></div>

      <div class="modal-info-box">
        <p style="font-style:normal;font-size:13px;color:#333;font-weight:600;">
          You are about to delete:
          <strong id="deleteCustName" style="color:#1a1a2e;"></strong>
        </p>
        <p style="font-style:normal;font-size:12px;color:#666;margin-top:4px;">
          This action cannot be undone.
        </p>
      </div>

      <!-- Shown when custodian has assets — informational only, delete is still allowed -->
      <div id="deleteCustAssetWarning" style="display:none;background:#fffbeb;border-left:3px solid #d97706;
               border-radius:6px;padding:12px 14px;margin-bottom:16px;
               font-size:12px;color:#92400e;font-weight:600;"></div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelDeleteCustBtn">CANCEL</button>
        <button id="confirmDeleteCustBtn" style="padding:10px 36px;background:#dc2626;color:white;border:none;
                 border-radius:8px;font-family:'Outfit',sans-serif;font-size:13px;
                 font-weight:700;letter-spacing:1px;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
          DELETE
        </button>
      </div>
    </div>
  </div>


  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script src="/1QCUPROJECT/scripts/admin/departments/admin-departments.js"></script>
  <script src="/1QCUPROJECT/scripts/admin/departments/admin-departments-modals.js"></script>
  <script src="/1QCUPROJECT/scripts/admin/departments/admin-departments-init.js"></script>

</body>

</html>