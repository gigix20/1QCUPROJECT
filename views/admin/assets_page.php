<?php
    require_once __DIR__ . '/../../backend/auth.php';

    if (!isset($_SESSION['user_id'])) {
      header("Location: /1QCUPROJECT/views/auth/login.php");
      exit;
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>ONEQCU | Assets</title>
      <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-base.css">
      <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-sidebar.css">
      <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-layout.css">
      <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-toast.css">
      <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-table.css">
      <link rel="stylesheet" href="/1QCUPROJECT/styles/admin/admin-modal.css">
      <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <style>
        /* ── Filter Row ─────────────────────────────────────────── */
        .filter-tabs {
          display: flex;
          align-items: center;
          justify-content: space-between;
          flex-wrap: wrap;
          gap: 8px;
          margin-bottom: 20px;
        }

        .filter-tabs-left {
          display: flex;
          align-items: center;
          gap: 6px;
          flex-wrap: wrap;
        }

        .filter-tabs-right {
          display: flex;
          align-items: center;
          gap: 8px;
        }

        /* Dropdown filter buttons */
        .filter-dropdown-wrap {
          position: relative;
          display: inline-flex;
          align-items: center;
        }

        .filter-dropdown-btn {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          padding: 7px 14px;
          background: #fff;
          border: 1.5px solid #d1d5db;
          border-radius: 8px;
          font-family: 'Outfit', sans-serif;
          font-size: 13px;
          font-weight: 500;
          color: #374151;
          cursor: pointer;
          white-space: nowrap;
          transition: border-color 0.18s, background 0.18s, color 0.18s;
          letter-spacing: 0.02em;
        }

        .filter-dropdown-btn:hover {
          border-color: #7c3aed;
          color: #7c3aed;
          background: #f5f3ff;
        }

        .filter-dropdown-btn.active-filter {
          border-color: #7c3aed;
          background: #7c3aed;
          color: #fff;
        }

        .filter-dropdown-btn svg {
          flex-shrink: 0;
          transition: transform 0.18s;
        }

        .filter-dropdown-btn.open svg.chevron {
          transform: rotate(180deg);
        }

        /* Dropdown panel */
        .filter-dropdown-panel {
          display: none;
          position: absolute;
          top: calc(100% + 6px);
          left: 0;
          min-width: 200px;
          background: #fff;
          border: 1.5px solid #e5e7eb;
          border-radius: 10px;
          box-shadow: 0 8px 24px rgba(0,0,0,0.10);
          z-index: 999;
          padding: 6px 0;
          animation: dropFade 0.15s ease;
        }

        .filter-dropdown-panel.show {
          display: block;
        }

        @keyframes dropFade {
          from { opacity: 0; transform: translateY(-4px); }
          to   { opacity: 1; transform: translateY(0); }
        }

        .filter-dropdown-panel .ddp-item {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 9px 16px;
          font-family: 'Outfit', sans-serif;
          font-size: 13px;
          color: #374151;
          cursor: pointer;
          transition: background 0.13s;
          border-radius: 0;
        }

        .filter-dropdown-panel .ddp-item:hover {
          background: #f5f3ff;
          color: #7c3aed;
        }

        .filter-dropdown-panel .ddp-item.selected {
          color: #7c3aed;
          font-weight: 600;
        }

        .filter-dropdown-panel .ddp-item .ddp-check {
          width: 14px;
          height: 14px;
          flex-shrink: 0;
          color: #7c3aed;
          opacity: 0;
        }

        .filter-dropdown-panel .ddp-item.selected .ddp-check {
          opacity: 1;
        }

        .filter-dropdown-panel .ddp-divider {
          height: 1px;
          background: #f3f4f6;
          margin: 4px 0;
        }

        /* Active filter badge on button */
        .filter-badge {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          background: #fff;
          color: #7c3aed;
          border-radius: 50%;
          font-size: 10px;
          font-weight: 700;
          width: 16px;
          height: 16px;
          line-height: 1;
          flex-shrink: 0;
        }

        .filter-dropdown-btn.active-filter .filter-badge {
          background: rgba(255,255,255,0.25);
          color: #fff;
        }

        /* Clear-all filters pill */
        .clear-filters-btn {
          display: none;
          align-items: center;
          gap: 5px;
          padding: 7px 13px;
          background: #fef2f2;
          border: 1.5px solid #fca5a5;
          border-radius: 8px;
          font-family: 'Outfit', sans-serif;
          font-size: 12px;
          font-weight: 600;
          color: #dc2626;
          cursor: pointer;
          transition: background 0.15s;
          white-space: nowrap;
        }

        .clear-filters-btn:hover {
          background: #fee2e2;
        }

        .clear-filters-btn.visible {
          display: inline-flex;
        }
      </style>
    </head>

    <body>

      <?php $currentPage = 'assets'; ?>
      <?php require __DIR__ . '/../../components/staff/staff_sidebar.php'; ?>

      <!--MAIN CONTENT -->
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
          <input type="text" id="assetsSearchInput"
                placeholder="Search by asset ID, description, serial number or department...">
        </div>

        <!-- Filter Tabs + Dropdown Filters (single row) -->
        <div class="filter-tabs">

          <!-- Left: Status tabs -->
          <div class="filter-tabs-left">
            <button class="filter-tab active" data-status="ALL">ALL ASSETS</button>
            <button class="filter-tab" data-status="Available">AVAILABLE</button>
            <button class="filter-tab" data-status="In Use">IN USE</button>
            <button class="filter-tab" data-status="Maintenance">MAINTENANCE</button>
            <button class="filter-tab" data-status="Certified">CERTIFIED</button>
          </div>

          <!-- Right: Dropdown filters -->
          <div class="filter-tabs-right">

            <!-- Category Filter -->
            <div class="filter-dropdown-wrap" id="categoryFilterWrap">
              <button class="filter-dropdown-btn" id="categoryFilterBtn" type="button">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                  <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
                CATEGORY
                <span class="filter-badge" id="categoryBadge" style="display:none;"></span>
                <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </button>
              <div class="filter-dropdown-panel" id="categoryDropdownPanel" style="right:0;left:auto;">
                <div class="ddp-item selected" data-value="">
                  <svg class="ddp-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  All Categories
                </div>
                <div class="ddp-divider"></div>
              </div>
            </div>

            <!-- Item Type Filter -->
            <div class="filter-dropdown-wrap" id="itemTypeFilterWrap">
              <button class="filter-dropdown-btn" id="itemTypeFilterBtn" type="button">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                  <polyline points="2 17 12 22 22 17"/>
                  <polyline points="2 12 12 17 22 12"/>
                </svg>
                ITEM TYPE
                <span class="filter-badge" id="itemTypeBadge" style="display:none;"></span>
                <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </button>
              <div class="filter-dropdown-panel" id="itemTypeDropdownPanel" style="right:0;left:auto;">
                <div class="ddp-item selected" data-value="">
                  <svg class="ddp-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  All Item Types
                </div>
                <div class="ddp-divider"></div>
              </div>
            </div>

            <!-- Clear All -->
            <button class="clear-filters-btn" id="clearFiltersBtn" type="button">
              &#10005; CLEAR
            </button>

          </div>
        </div>

        <!-- Table -->
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
              <tr class="empty-row">
                <td colspan="11">No assets to display.</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

      <!--ADD ASSET MODAL-->
      <div class="modal-overlay" id="assetsModalOverlay">
        <div class="modal">
          <div class="modal-title">Add New Asset</div>
          <div class="modal-divider"></div>

          <div class="modal-section-label">BASIC INFORMATION</div>

          <div class="modal-two-col">
            <div class="form-group">
              <label>Asset ID
                <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">
                  (auto-generated)
                </span>
              </label>
              <input type="text" id="assetsAssetId" readonly
                    placeholder="Will be generated on save">
            </div>
            <div class="form-group">
              <label>QR Code
                <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">
                  (auto-generated)
                </span>
              </label>
              <input type="text" id="assetsQrCode" readonly
                    placeholder="Will be generated on save">
            </div>
          </div>

          <div class="form-full">
            <label>Description <span style="color:#dc2626;font-weight:700;">*</span></label>
            <input type="text" id="assetsDescription"
                  placeholder="e.g. Dell Laptop Latitude 5540">
          </div>

          <div class="modal-two-col">
            <div class="form-group">
              <label>Item Type <span style="color:#dc2626;font-weight:700;">*</span></label>
              <select id="assetsItemType">
                <option value="">-- Select Item Type --</option>
              </select>
            </div>
            <div class="form-group">
              <label>Category</label>
              <select id="assetsCategory">
                <option value="">-- Select Category --</option>
              </select>
            </div>
          </div>

          <div class="modal-two-col">
            <div class="form-group">
              <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
              <select id="assetsDepartment">
                <option value="">-- Select Department --</option>
              </select>
            </div>
            <div class="form-group">
              <label>Liable Person</label>
              <select id="assetsLiablePerson" disabled>
                <option value="">-- Select Department first --</option>
              </select>
            </div>
          </div>

          <div class="modal-two-col">
            <div class="form-group">
              <label>Location</label>
              <input type="text" id="assetsLocation"
                    placeholder="e.g. Room 301, Building A">
            </div>
            <div class="form-group">
              <label>Serial Number</label>
              <input type="text" id="assetsSerialNumber"
                    placeholder="e.g. SN-2024-00123">
            </div>
            <div class="form-group">
              <label>Quantity <span style="color:#dc2626;font-weight:700;">*</span></label>
              <input type="number" id="assetsQuantity"
                    min="1" max="100" value="1"
                    placeholder="e.g. 1">
            </div>
          </div>

          <div class="modal-two-col">
            <div class="form-group">
              <label>Status</label>
              <select id="assetsStatus">
                <option value="Available">Available</option>
                <option value="In Use">In Use</option>
                <option value="Maintenance">Maintenance</option>
              </select>
            </div>
            <div class="form-group" style="justify-content:flex-end;padding-bottom:4px;">
              <label>Certified</label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;
                            font-weight:400;color:#333;cursor:pointer;
                            text-transform:none;letter-spacing:0;">
                <input type="checkbox" id="assetsCertified"
                      style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
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
              <label>Asset ID
                <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">
                  (read-only)
                </span>
              </label>
              <input type="text" id="editAssetId" readonly>
            </div>
            <div class="form-group">
              <label>QR Code
                <span style="font-size:10px;color:#a78bfa;font-weight:400;letter-spacing:0;">
                  (read-only)
                </span>
              </label>
              <input type="text" id="editQrCode" readonly>
            </div>
          </div>

          <div class="form-full">
            <label>Description <span style="color:#dc2626;font-weight:700;">*</span></label>
            <input type="text" id="editDescription"
                  placeholder="e.g. Dell Laptop Latitude 5540">
          </div>

          <div class="modal-two-col">
            <div class="form-group">
              <label>Item Type <span style="color:#dc2626;font-weight:700;">*</span></label>
              <select id="editItemType">
                <option value="">-- Select Item Type --</option>
              </select>
            </div>
            <div class="form-group">
              <label>Category</label>
              <select id="editCategory">
                <option value="">-- Select Category --</option>
              </select>
            </div>
          </div>

        <div class="modal-two-col">
          <div class="form-group">
            <label>Department <span style="color:#dc2626;font-weight:700;">*</span></label>
            <select id="editDepartment">
              <option value="">-- Select Department --</option>
            </select>
          </div>
          <div class="form-group">
            <label>Liable Person</label>
            <select id="editLiablePerson" disabled>
              <option value="">-- Select Department first --</option>
            </select>
          </div>
        </div>

        <div class="modal-two-col">
          <div class="form-group">
            <label>Location</label>
            <input type="text" id="editLocation"
                  placeholder="e.g. Room 301, Building A">
          </div>
          <div class="form-group">
            <label>Serial Number</label>
            <input type="text" id="editSerialNumber"
                  placeholder="e.g. SN-2024-00123">
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
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;
                            font-weight:400;color:#333;cursor:pointer;
                            text-transform:none;letter-spacing:0;">
                <input type="checkbox" id="editCertified"
                      style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
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

      <!--QR CODE VIEW MODAL -->
      <div class="modal-overlay" id="qrViewModal">
        <div class="modal qr-view-modal">
          <div class="modal-title">QR Code</div>
          <div class="modal-divider"></div>

          <div class="qr-asset-info">
            <p class="qr-asset-id"   id="qrModalAssetId"></p>
            <p class="qr-asset-desc" id="qrModalDesc"></p>
            <span id="qrModalDept"
                  style="display:inline-block;font-size:11px;font-weight:600;
                        padding:3px 10px;border-radius:20px;border:1px solid;
                        margin-bottom:12px;letter-spacing:0.5px;">
            </span>
          </div>

          <div class="qr-display-box">
            <div id="qrCanvas"></div>
          </div>

          <div>
            <p class="qr-code-text" id="qrModalCodeText"></p>
          </div>

          <div class="modal-buttons">
            <button class="modal-edit-btn"  id="closeQrViewBtn">CLOSE</button>
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
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;
                          font-weight:400;color:#333;cursor:pointer;
                          text-transform:none;letter-spacing:0;">
              <input type="checkbox" id="exportIncludeDeleted"
                    style="width:16px;height:16px;accent-color:#7c3aed;cursor:pointer;">
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

      <script>
      /* ================================================================
         FILTER DROPDOWNS — Category & Item Type
         ================================================================
         HOW IT WORKS:
         1. On page load, it reads unique Category & Item Type values
            from the already-rendered table rows (populated by your
            existing staff-assets.js).  Call refreshFilterOptions()
            anytime the table re-renders (after add/edit/delete).
         2. Clicking a dropdown item sets the active filter value.
         3. applyDropdownFilters() hides/shows rows that don't match
            BOTH the selected category AND the selected item type.
            It works together with your existing status-tab filter.
         ================================================================ */

      (function () {

        /* ── State ─────────────────────────────────────── */
        let selectedCategory = '';
        let selectedItemType = '';

        /* ── DOM refs ──────────────────────────────────── */
        const categoryBtn      = document.getElementById('categoryFilterBtn');
        const categoryPanel    = document.getElementById('categoryDropdownPanel');
        const categoryBadge    = document.getElementById('categoryBadge');

        const itemTypeBtn      = document.getElementById('itemTypeFilterBtn');
        const itemTypePanel    = document.getElementById('itemTypeDropdownPanel');
        const itemTypeBadge    = document.getElementById('itemTypeBadge');

        const clearBtn         = document.getElementById('clearFiltersBtn');

        /* ── Toggle panels ─────────────────────────────── */
        function togglePanel(btn, panel) {
          const isOpen = panel.classList.contains('show');
          closeAll();
          if (!isOpen) {
            panel.classList.add('show');
            btn.classList.add('open');
          }
        }

        function closeAll() {
          [categoryPanel, itemTypePanel].forEach(p => p.classList.remove('show'));
          [categoryBtn, itemTypeBtn].forEach(b => b.classList.remove('open'));
        }

        categoryBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          togglePanel(categoryBtn, categoryPanel);
        });

        itemTypeBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          togglePanel(itemTypeBtn, itemTypePanel);
        });

        document.addEventListener('click', closeAll);

        /* ── Build dropdown items from unique table values ─ */
        function buildOptions(panel, values, currentValue, onSelect) {
          // Remove old dynamic items (keep "All" + divider = first 2 nodes)
          const all  = panel.children[0];
          const divider = panel.children[1];
          panel.innerHTML = '';
          panel.appendChild(all);
          panel.appendChild(divider);

          values.forEach(val => {
            const item = document.createElement('div');
            item.className = 'ddp-item' + (val === currentValue ? ' selected' : '');
            item.dataset.value = val;
            item.innerHTML = `
              <svg class="ddp-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              ${val}`;
            item.addEventListener('click', (e) => {
              e.stopPropagation();
              onSelect(val);
              closeAll();
            });
            panel.appendChild(item);
          });

          // "All" item click
          all.onclick = (e) => {
            e.stopPropagation();
            onSelect('');
            closeAll();
          };
        }

        /* ── Refresh options from current table rows ───── */
        window.refreshFilterOptions = function () {
          const rows = document.querySelectorAll('#assetsTableBody tr:not(.empty-row)');

          const categories = new Set();
          const itemTypes  = new Set();

          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            // Col indices based on your table: 0=AssetID,1=QR,2=Desc,3=Serial,4=ItemType,5=Category...
            if (cells[4]) itemTypes.add(cells[4].textContent.trim());
            if (cells[5]) categories.add(cells[5].textContent.trim());
          });

          buildOptions(
            categoryPanel,
            [...categories].filter(Boolean).sort(),
            selectedCategory,
            (val) => { selectedCategory = val; updateCategoryUI(); applyDropdownFilters(); }
          );

          buildOptions(
            itemTypePanel,
            [...itemTypes].filter(Boolean).sort(),
            selectedItemType,
            (val) => { selectedItemType = val; updateItemTypeUI(); applyDropdownFilters(); }
          );
        };

        /* ── Update button appearance ───────────────────── */
        function updateCategoryUI() {
          const hasFilter = selectedCategory !== '';
          categoryBtn.classList.toggle('active-filter', hasFilter);
          categoryBadge.style.display = hasFilter ? 'inline-flex' : 'none';
          categoryBadge.textContent = hasFilter ? '1' : '';
          // Update "All Categories" selected state
          categoryPanel.querySelectorAll('.ddp-item').forEach(i => {
            i.classList.toggle('selected', i.dataset.value === selectedCategory);
          });
          updateClearBtn();
        }

        function updateItemTypeUI() {
          const hasFilter = selectedItemType !== '';
          itemTypeBtn.classList.toggle('active-filter', hasFilter);
          itemTypeBadge.style.display = hasFilter ? 'inline-flex' : 'none';
          itemTypeBadge.textContent = hasFilter ? '1' : '';
          itemTypePanel.querySelectorAll('.ddp-item').forEach(i => {
            i.classList.toggle('selected', i.dataset.value === selectedItemType);
          });
          updateClearBtn();
        }

        function updateClearBtn() {
          const hasAny = selectedCategory !== '' || selectedItemType !== '';
          clearBtn.classList.toggle('visible', hasAny);
        }

        /* ── Clear all ──────────────────────────────────── */
        clearBtn.addEventListener('click', () => {
          selectedCategory = '';
          selectedItemType = '';
          updateCategoryUI();
          updateItemTypeUI();
          applyDropdownFilters();
        });

        /* ── Apply filter to table rows ─────────────────── */
        window.applyDropdownFilters = function () {
          const rows = document.querySelectorAll('#assetsTableBody tr:not(.empty-row)');

          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const rowItemType  = cells[4] ? cells[4].textContent.trim() : '';
            const rowCategory  = cells[5] ? cells[5].textContent.trim() : '';

            const matchCategory = selectedCategory === '' || rowCategory === selectedCategory;
            const matchItemType = selectedItemType === '' || rowItemType === selectedItemType;

            // Only touch display if the row is already "visible" by status filter;
            // use a data attribute approach so both filters compose cleanly.
            if (!matchCategory || !matchItemType) {
              row.setAttribute('data-dropdown-hidden', '1');
              row.style.display = 'none';
            } else {
              row.removeAttribute('data-dropdown-hidden');
              // Restore visibility only if status filter hasn't hidden it
              if (!row.getAttribute('data-status-hidden')) {
                row.style.display = '';
              }
            }
          });

          showEmptyIfNeeded();
        };

        /* ── Show empty row if everything is filtered out ─ */
        function showEmptyIfNeeded() {
          const tbody = document.getElementById('assetsTableBody');
          const visible = [...tbody.querySelectorAll('tr:not(.empty-row)')].filter(
            r => r.style.display !== 'none'
          );
          let emptyRow = tbody.querySelector('.empty-row');
          if (visible.length === 0) {
            if (!emptyRow) {
              emptyRow = document.createElement('tr');
              emptyRow.className = 'empty-row';
              emptyRow.innerHTML = '<td colspan="11">No assets match the selected filters.</td>';
              tbody.appendChild(emptyRow);
            }
            emptyRow.style.display = '';
          } else {
            if (emptyRow) emptyRow.style.display = 'none';
          }
        }

        /* ── Hook into table re-renders ─────────────────── */
        // Watch for changes to the table body so options stay fresh
        const observer = new MutationObserver(() => {
          window.refreshFilterOptions();
          window.applyDropdownFilters();
        });
        observer.observe(document.getElementById('assetsTableBody'), { childList: true, subtree: true });

        // Initial build
        window.refreshFilterOptions();

      })();
      </script>
    </body>

    </html>