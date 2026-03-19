// =============================================
// ONEQCU - Departments Page Script
// Uses existing admin_style.css classes
// =============================================

document.addEventListener('DOMContentLoaded', () => {
  renderTable();
  updateStats();
  initSearch();
  initAddDepartment();
});

// =============================================
// DATA (replace with actual DB fetch)
// =============================================
let departments = [];

// =============================================
// RENDER TABLE
// =============================================
function renderTable(data = departments) {
  const tbody = document.querySelector('.asset-table tbody');
  tbody.innerHTML = '';

  if (data.length === 0) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="7">No departments to display.</td></tr>`;
    return;
  }

  data.forEach((dept, index) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${dept.name}</td>
      <td>${dept.building}</td>
      <td>${dept.head || '—'}</td>
      <td>${dept.custodian || '—'}</td>
      <td>${dept.assets}</td>
      <td><span class="status-badge ${dept.status === 'Active' ? 'active' : 'inactive'}">${dept.status}</span></td>
      <td>
        <div class="action-group">
          <button class="edit-btn" data-index="${index}">Edit</button>
          <button class="delete-btn" data-index="${index}">Delete</button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });

  initTableActions();
}

// =============================================
// STATS
// =============================================
function updateStats() {
  const totalDepts = departments.length;
  const buildings = [...new Set(departments.map(d => d.building))].filter(Boolean).length;
  const custodians = [...new Set(departments.map(d => d.custodian))].filter(Boolean).length;
  const totalAssets = departments.reduce((sum, d) => sum + (parseInt(d.assets) || 0), 0);

  const statValues = document.querySelectorAll('.stat-value');
  animateCount(statValues[0], totalDepts);
  animateCount(statValues[1], buildings);
  animateCount(statValues[2], custodians);
  animateCount(statValues[3], totalAssets);
}

function animateCount(el, target) {
  if (!el) return;
  let current = 0;
  const step = Math.max(1, Math.ceil(target / 30));
  const interval = setInterval(() => {
    current += step;
    if (current >= target) {
      el.textContent = target;
      clearInterval(interval);
    } else {
      el.textContent = current;
    }
  }, 30);
}

// =============================================
// SEARCH / FILTER
// =============================================
function initSearch() {
  const searchInput = document.querySelector('.search-bar-full input');
  if (!searchInput) return;

  searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase().trim();
    if (!query) { renderTable(); return; }

    const filtered = departments.filter(d =>
      d.name.toLowerCase().includes(query) ||
      d.building.toLowerCase().includes(query) ||
      (d.custodian || '').toLowerCase().includes(query)
    );
    renderTable(filtered);
  });
}

// =============================================
// ADD DEPARTMENT BUTTON
// =============================================
function initAddDepartment() {
  const addBtn = document.querySelector('.add-btn');
  if (addBtn) addBtn.addEventListener('click', () => openDeptModal());
}

// =============================================
// ADD / EDIT MODAL
// Uses existing .modal-overlay, .modal, .modal-title,
// .dept-modal, .form-group, .modal-buttons classes
// =============================================
function openDeptModal(editIndex = null) {
  const isEdit = editIndex !== null;
  const dept = isEdit ? departments[editIndex] : {};

  // Remove existing modal if any
  document.querySelector('#dept-modal-overlay')?.remove();

  const overlay = document.createElement('div');
  overlay.id = 'dept-modal-overlay';
  overlay.className = 'modal-overlay active';
  overlay.innerHTML = `
    <div class="modal dept-modal">
      <div class="dept-modal-header">
        <div>
          <div class="dept-modal-eyebrow">${isEdit ? 'EDIT RECORD' : 'NEW RECORD'}</div>
          <div class="modal-title">${isEdit ? 'Edit Department' : 'Add Department'}</div>
        </div>
      </div>
      <div class="modal-divider"></div>

      <div class="dept-form-grid">
        <div class="form-group">
          <label>Department Name <span class="req">*</span></label>
          <input type="text" id="dm-name" placeholder="e.g. College of Engineering" value="${dept.name || ''}">
          <div class="dept-form-error" id="err-name"></div>
        </div>
        <div class="form-group">
          <label>Building / Location <span class="req">*</span></label>
          <input type="text" id="dm-building" placeholder="e.g. Engineering Building A" value="${dept.building || ''}">
          <div class="dept-form-error" id="err-building"></div>
        </div>
        <div class="form-group">
          <label>Department Head</label>
          <input type="text" id="dm-head" placeholder="e.g. Dr. Juan dela Cruz" value="${dept.head || ''}">
        </div>
        <div class="form-group">
          <label>Property Custodian</label>
          <input type="text" id="dm-custodian" placeholder="e.g. Maria Santos" value="${dept.custodian || ''}">
        </div>
        <div class="form-group">
          <label>Total Assets</label>
          <input type="number" id="dm-assets" placeholder="0" value="${dept.assets || 0}" min="0">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="dm-status">
            <option value="Active" ${dept.status === 'Active' || !dept.status ? 'selected' : ''}>Active</option>
            <option value="Inactive" ${dept.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
          </select>
        </div>
      </div>

      <div class="modal-buttons">
        <button class="modal-edit-btn" id="dept-modal-cancel">CANCEL</button>
        <button class="modal-close-btn" id="dept-modal-save">${isEdit ? 'SAVE CHANGES' : 'ADD DEPARTMENT'}</button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);

  overlay.querySelector('#dept-modal-cancel').addEventListener('click', () => overlay.remove());
  overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

  overlay.querySelector('#dept-modal-save').addEventListener('click', () => {
    const name = document.getElementById('dm-name').value.trim();
    const building = document.getElementById('dm-building').value.trim();
    const head = document.getElementById('dm-head').value.trim();
    const custodian = document.getElementById('dm-custodian').value.trim();
    const assets = parseInt(document.getElementById('dm-assets').value) || 0;
    const status = document.getElementById('dm-status').value;

    // Validation
    let valid = true;
    document.getElementById('err-name').textContent = '';
    document.getElementById('err-building').textContent = '';

    if (!name) {
      document.getElementById('err-name').textContent = 'Department name is required.';
      valid = false;
    }
    if (!building) {
      document.getElementById('err-building').textContent = 'Building / location is required.';
      valid = false;
    }
    if (!valid) return;

    const newDept = { name, building, head, custodian, assets, status };

    if (isEdit) {
      departments[editIndex] = newDept;
      showToast('Department updated successfully!');
    } else {
      departments.push(newDept);
      showToast('Department added successfully!');
    }

    overlay.remove();
    renderTable();
    updateStats();
  });
}

// =============================================
// EDIT & DELETE ACTIONS
// =============================================
function initTableActions() {
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => openDeptModal(parseInt(btn.dataset.index)));
  });

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => openDeleteConfirm(parseInt(btn.dataset.index)));
  });
}

function openDeleteConfirm(index) {
  const dept = departments[index];

  document.querySelector('#delete-modal-overlay')?.remove();

  const overlay = document.createElement('div');
  overlay.id = 'delete-modal-overlay';
  overlay.className = 'modal-overlay active';
  overlay.innerHTML = `
    <div class="modal" style="width:400px; max-width:95vw;">
      <div class="dept-modal-header">
        <div>
          <div class="dept-modal-eyebrow">CONFIRM ACTION</div>
          <div class="modal-title">Delete Department</div>
        </div>
      </div>
      <div class="modal-divider"></div>
      <div class="modal-info-box">
        <p>You are about to delete <strong>${dept.name}</strong>.</p>
        <p>This action cannot be undone.</p>
      </div>
      <div class="modal-buttons">
        <button class="modal-edit-btn" id="del-cancel">CANCEL</button>
        <button class="delete-confirm-btn" id="del-confirm">DELETE</button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);

  overlay.querySelector('#del-cancel').addEventListener('click', () => overlay.remove());
  overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

  overlay.querySelector('#del-confirm').addEventListener('click', () => {
    departments.splice(index, 1);
    overlay.remove();
    renderTable();
    updateStats();
    showToast('Department deleted.');
  });
}

// =============================================
// TOAST - uses existing .toast and .show classes
// =============================================
function showToast(message) {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}