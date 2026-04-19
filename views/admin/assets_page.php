<?php
require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Admin Assets</title>
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
  <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-filter.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

  <?php $currentPage = 'assets'; ?>
  <?php require __DIR__ . '/../../components/admin/admin_sidebar.php'; ?>

  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div>
        <h1 class="page-title">ASSETS</h1>
        <p class="page-sub">Manage and track all of the ONEQCU assets</p>
      </div>
      <div class="topbar-actions">
        <button class="outline-btn" id="exportBtn">EXPORT</button>
        <button class="add-btn" id="assetsOpenModalBtn">+ ADD ASSET</button>
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar-full">
      <span class="search-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="text" id="assetsSearchInput" placeholder="Search by asset ID, description, serial number or department...">
    </div>

    <!-- Filter Tabs + Dropdown Filters -->
    <div class="filter-tabs">
      <div class="filter-tabs-left">
        <button class="filter-tab active" data-status="ALL">ALL ACTIVE ASSETS</button>
        <button class="filter-tab" data-status="Available">AVAILABLE</button>
        <button class="filter-tab" data-status="In Use">IN USE</button>
        <button class="filter-tab" data-status="Maintenance">MAINTENANCE</button>
        <button class="filter-tab" data-status="Certified">CERTIFIED</button>
        <!-- Pending Deletions tab — highlighted in red -->
        <button class="filter-tab" data-status="PendingDeletion"
          style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;">
          ⚠ PENDING DELETIONS
          <span id="pendingDeletionsBadge"
            style="display:none;background:#dc2626;color:white;font-size:10px;
                   font-weight:700;padding:1px 6px;border-radius:20px;margin-left:4px;">
          </span>
        </button>
      </div>

      <!-- Right: Dropdown filters (only relevant for normal table view) -->
      <div class="filter-tabs-right">
        <div class="filter-dropdown-wrap" id="categoryFilterWrap">
          <button class="filter-dropdown-btn" id="categoryFilterBtn" type="button">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
              <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            CATEGORY
            <span class="filter-badge" id="categoryBadge" style="display:none;"></span>
            <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>
          <div class="filter-dropdown-panel" id="categoryDropdownPanel" style="right:0;left:auto;">
            <div class="ddp-item selected" data-value="">
              <svg class="ddp-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              All Categories
            </div>
            <div class="ddp-divider"></div>
          </div>
        </div>

        <div class="filter-dropdown-wrap" id="itemTypeFilterWrap">
          <button class="filter-dropdown-btn" id="itemTypeFilterBtn" type="button">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 2 7 12 12 22 7 12 2"/>
              <polyline points="2 17 12 22 22 17"/>
              <polyline points="2 12 12 17 22 12"/>
            </svg>
            ITEM TYPE
            <span class="filter-badge" id="itemTypeBadge" style="display:none;"></span>
            <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>
          <div class="filter-dropdown-panel" id="itemTypeDropdownPanel" style="right:0;left:auto;">
            <div class="ddp-item selected" data-value="">
              <svg class="ddp-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              All Item Types
            </div>
            <div class="ddp-divider"></div>
          </div>
        </div>

        <button class="clear-filters-btn" id="clearFiltersBtn" type="button">&#10005; CLEAR</button>
      </div>
    </div>

    <!-- ── NORMAL ASSETS TABLE ────────────────────────────────────────────────── -->
    <div id="normalAssetsSection">
      <div class="table-section">
        <table class="asset-table">
          <thead>
            <tr>
              <th>ASSET ID</th>
              <th>QR CODE</th>
              <th>DESCRIPTION</th>
              <th>SERIAL NUMBER</th>
              <th>ITEM TYPE</th>
              <th>CATEGORY</th>
              <th>DEPARTMENT</th>
              <th>LIABLE PERSON</th>
              <th>LOCATION</th>
              <th>STATUS</th>
              <th>CERTIFIED</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="assetsTableBody">
            <tr class="empty-row"><td colspan="12">No assets to display.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── PENDING DELETIONS SECTION (hidden by default) ─────────────────────── -->
    <div id="pendingDeletionsSection" style="display:none;">
      <div class="table-section"
        style="border-top:3px solid #dc2626;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <div>
            <h2 style="font-size:15px;font-weight:600;color:#dc2626;">⚠ Pending Deletion Requests</h2>
            <p style="font-size:12px;color:#888;margin-top:2px;">
              Review staff-submitted deletion requests. Approve to permanently remove, or reject to restore the asset.
            </p>
          </div>
        </div>

        <table class="asset-table">
          <thead>
            <tr>
              <th>ASSET ID</th>
              <th>DESCRIPTION</th>
              <th>DEPARTMENT</th>
              <th>REQUESTED BY</th>
              <th>REASON</th>
              <th>DATE SUBMITTED</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="pendingDeletionsBody">
            <tr class="empty-row"><td colspan="7">No pending deletion requests.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /.main -->


  <!-- ══════════════════════════════════════════════════════════
       MODALS
  ══════════════════════════════════════════════════════════ -->

  <!-- ADD ASSET MODAL -->
  <div class="modal-overlay" id="assetsModalOverlay">
    <div class="modal">
      <div class="modal-title">Add New Asset</div>
      <div class="modal-divider"></div>
      <div class="modal-section-label">BASIC INFORMATION</div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Asset ID <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">(auto-generated)</span></label>
          <input type="text" id="assetsAssetId" readonly placeholder="Will be generated on save">
        </div>
        <div class="form-group">
          <label>QR Code <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">(auto-generated)</span></label>
          <input type="text" id="assetsQrCode" readonly placeholder="Will be generated on save">
        </div>
      </div>
      <div class="form-full">
        <label>Description <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="assetsDescription" placeholder="e.g. Dell Laptop Latitude 5540">
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Item Type <span style="color:#dc2626;font-weight:700;">*</span></label>
          <select id="assetsItemType"><option value="">-- Select Item Type --</option></select>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select id="assetsCategory"><option value="">-- Select Category --</option></select>
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
          <select id="assetsDepartment"><option value="">-- Select Department --</option></select>
        </div>
        <div class="form-group">
          <label>Liable Person</label>
          <select id="assetsLiablePerson" disabled><option value="">-- Select Department first --</option></select>
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Location</label>
          <input type="text" id="assetsLocation" placeholder="e.g. Room 301, Building A">
        </div>
        <div class="form-group">
          <label>Serial Number</label>
          <input type="text" id="assetsSerialNumber" placeholder="e.g. SN-2024-00123">
        </div>
        <div class="form-group">
          <label>Quantity</label>
          <input type="number" id="assetsQuantity" min="1" value="1">
        </div>
      </div>
      <div class="modal-two-col">

      <!-- asset status options -->
        <div class="form-group">
          <label>Status</label>
          <select id="assetsStatus">
            <option value="Available">Available</option>
            <option value="In Use">In Use</option>
            <!-- <option value="Maintenance">Maintenance</option> -->
          </select>
        </div>

        <div class="form-group" style="justify-content:flex-end;padding-bottom:4px;">
          <label>Certified</label>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;color:#333;cursor:pointer;text-transform:none;letter-spacing:0;">
            <input type="checkbox" id="assetsCertified" style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
            Mark as Certified
          </label>
        </div>
      </div>
      <div class="modal-buttons">
        <button class="modal-edit-btn" id="assetsCancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="assetsSaveBtn">SAVE &amp; GENERATE QR</button>
      </div>
    </div>
  </div>

  <!-- EDIT ASSET MODAL -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <div class="modal-title">Edit Asset</div>
      <div class="modal-divider"></div>
      <div class="modal-section-label">EDIT INFORMATION</div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Asset ID <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">(read-only)</span></label>
          <input type="text" id="editAssetId" readonly>
        </div>
        <div class="form-group">
          <label>QR Code <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">(read-only)</span></label>
          <input type="text" id="editQrCode" readonly>
        </div>
      </div>
      <div class="form-full">
        <label>Description <span style="color:#dc2626;font-weight:700;">*</span></label>
        <input type="text" id="editDescription" placeholder="e.g. Dell Laptop Latitude 5540">
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Item Type <span style="color:#dc2626;font-weight:700;">*</span></label>
          <select id="editItemType"><option value="">-- Select Item Type --</option></select>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select id="editCategory"><option value="">-- Select Category --</option></select>
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
          <select id="editDepartment"><option value="">-- Select Department --</option></select>
        </div>
        <div class="form-group">
          <label>Liable Person</label>
          <select id="editLiablePerson" disabled><option value="">-- Select Department first --</option></select>
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Location</label>
          <input type="text" id="editLocation" placeholder="e.g. Room 301, Building A">
        </div>
        <div class="form-group">
          <label>Serial Number</label>
          <input type="text" id="editSerialNumber" placeholder="e.g. SN-2024-00123">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="editStatus">
            <option value="Available">Available</option>
            <option value="In Use">In Use</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </div>
      </div>
      <div class="modal-two-col">
        <div class="form-group" style="justify-content:flex-end;padding-bottom:4px;">
          <label>Certified</label>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;color:#333;cursor:pointer;text-transform:none;letter-spacing:0;">
            <input type="checkbox" id="editCertified" style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
            Mark as Certified
          </label>
        </div>
      </div>
      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelEditBtn">CANCEL</button>
        <button class="modal-close-btn" id="saveEditBtn">SAVE CHANGES</button>
      </div>
    </div>
  </div>

  <!-- ── ADMIN DIRECT DELETE CONFIRMATION MODAL ─────────────────────────────── -->
  <div class="modal-overlay" id="adminDeleteModal">
    <div class="modal" style="max-width:440px;">
      <div class="modal-title" style="color:#dc2626;">Confirm Deletion</div>
      <div class="modal-divider" style="background:linear-gradient(to right,#dc2626,transparent);"></div>

      <div class="modal-info-box" style="background:#fff8f8;border-left:3px solid #dc2626;">
        <p style="font-style:normal;font-size:13px;color:#dc2626;font-weight:600;margin-bottom:4px;">
          ⚠ This action is permanent and cannot be undone.
        </p>
        <p style="font-style:normal;font-size:12px;color:#666;">
          Asset <strong id="adminDeleteAssetId" style="color:#1a1a2e;font-family:monospace;"></strong>
          will be permanently removed from the system.
        </p>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="cancelAdminDeleteBtn">CANCEL</button>
        <button id="confirmAdminDeleteBtn"
          style="padding:10px 36px;background:#dc2626;color:white;border:none;
                 border-radius:8px;font-family:'Outfit',sans-serif;font-size:13px;
                 font-weight:700;letter-spacing:1px;cursor:pointer;transition:background 0.2s;"
          onmouseover="this.style.background='#b91c1c'"
          onmouseout="this.style.background='#dc2626'">
          DELETE PERMANENTLY
        </button>
      </div>
    </div>
  </div>

  <!-- QR CODE VIEW MODAL -->
  <div class="modal-overlay" id="qrViewModal">
    <div class="modal qr-view-modal">
      <div class="modal-title">QR Code</div>
      <div class="modal-divider"></div>
      <div class="qr-asset-info">
        <p class="qr-asset-id" id="qrModalAssetId"></p>
        <p class="qr-asset-desc" id="qrModalDesc"></p>
        <span id="qrModalDept" style="display:inline-block;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid;margin-bottom:12px;letter-spacing:0.5px;"></span>
      </div>
      <div class="qr-display-box">
        <div id="qrCanvas"></div>
      </div>
      <div>
        <p class="qr-code-text" id="qrModalCodeText"></p>
      </div>
      <div class="modal-buttons">
        <button class="modal-edit-btn" id="closeQrViewBtn">CLOSE</button>
        <button class="qr-download-btn" id="downloadQrBtn">&#11015; DOWNLOAD</button>
      </div>
    </div>
  </div>

  <!-- EXPORT MODAL -->
  <div class="modal-overlay" id="exportModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-title">Export Assets</div>
      <div class="modal-divider"></div>
      <div class="modal-section-label">EXPORT OPTIONS</div>
      <div class="modal-two-col">
        <div class="form-group">
          <label>Data Scope</label>
          <select id="exportScope">
            <option value="all">All Assets</option>
            <option value="filtered">Current View Only</option>
          </select>
        </div>
        <div class="form-group" style="justify-content:flex-end;padding-bottom:4px;">
          <label>Options</label>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;color:#333;cursor:pointer;text-transform:none;letter-spacing:0;">
            <input type="checkbox" id="exportIncludeDeleted" style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
            Include pending deletion assets
          </label>
        </div>
      </div>
      <div class="modal-buttons">
        <button class="modal-edit-btn" id="exportCancelBtn">CANCEL</button>
        <button class="modal-close-btn" id="exportConfirmBtn">⬇ EXPORT PDF</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="../../scripts/admin/assets/admin-assets.js"></script>
  <script src="../../scripts/admin/assets/admin-assets-modals.js"></script>
  <script src="../../scripts/admin/assets/admin-assets-qr.js"></script>
  <script src="../../scripts/admin/assets/admin-assets-export.js"></script>
  <script src="../../scripts/admin/assets/admin-assets-init.js"></script>
  <script src="../../scripts/admin/assets/admin-assets-filter.js"></script>
</body>
</html>