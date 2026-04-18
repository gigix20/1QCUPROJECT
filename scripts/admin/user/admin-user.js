const API = '../../backend/routes/users_route.php';

let users = [];
let departments = [];
let activeFilter = 'ALL USERS';

document.addEventListener('DOMContentLoaded', () => {
  loadDepartments();
  loadUsers();
  initSearch();
  initFilterTabs();
});

async function loadUsers() {
  try {
    const res  = await fetch(`${API}?action=list`);
    const json = await res.json();
    if (json.status === 'success') {
      users = json.data;
      updateStats();
      applyFilters();
    } else {
      showToast(json.message || 'Failed to load users.', 'error');
    }
  } catch {
    showToast('Network error loading users.', 'error');
  }
}

async function loadDepartments() {
  try {
    const res  = await fetch(`${API}?action=departments`);
    const json = await res.json();
    if (json.status === 'success') {
      departments = json.data;
      populateDeptSelects();
    }
  } catch { /* silent */ }
}

function populateDeptSelects() {
  const opts = departments.map(d =>
    `<option value="${d.DEPARTMENT_NAME}">${d.DEPARTMENT_NAME}</option>`
  ).join('');

  document.getElementById('addDepartment').innerHTML =
    '<option value="">Select department...</option>' + opts;
  document.getElementById('editDepartment').innerHTML = opts;
}

function updateStats() {
  document.getElementById('statTotal').textContent    = users.length;
  document.getElementById('statAdmin').textContent    = users.filter(u => u.ROLE === 'Admin').length;
  document.getElementById('statStaff').textContent    = users.filter(u => u.ROLE === 'Staff').length;
  document.getElementById('statVerified').textContent = users.filter(u => u.IS_VERIFIED == 1).length;
}

function renderTable(data = users) {
  const tbody = document.getElementById('usersTableBody');
  tbody.innerHTML = '';

  if (data.length === 0) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="9">No users to display.</td></tr>`;
    return;
  }

  data.forEach(user => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><span class="qr-tag">${user.USER_ID}</span></td>
      <td>${escHtml(user.FULL_NAME)}</td>
      <td>${escHtml(user.EMAIL)}</td>
      <td>${escHtml(user.DEPARTMENT || '—')}</td>
      <td>${escHtml(user.EMPLOYEE_ID)}</td>
      <td><span class="badge ${user.ROLE === 'Admin' ? 'in-use' : 'returned'}">${user.ROLE}</span></td>
      <td><span class="status-badge ${user.IS_VERIFIED == 1 ? 'active' : 'inactive'}">
        ${user.IS_VERIFIED == 1 ? 'Verified' : 'Unverified'}
      </span></td>
      <td>${user.CREATED_AT || '—'}</td>
      <td>
        <div class="action-group">
          <button class="edit-btn" onclick="openEditModal(${user.USER_ID})">Edit</button>
          <button class="delete-btn" onclick="openDeleteModal(${user.USER_ID}, '${escAttr(user.FULL_NAME)}')">Remove</button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function initSearch() {
  document.getElementById('searchInput').addEventListener('input', e => {
    applyFilters(e.target.value.toLowerCase().trim());
  });
}

function initFilterTabs() {
  document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      activeFilter = tab.textContent.trim();
      applyFilters(document.getElementById('searchInput').value.toLowerCase().trim());
    });
  });
}

function applyFilters(query = '') {
  let filtered = [...users];

  switch (activeFilter) {
    case 'ADMINISTRATORS': filtered = filtered.filter(u => u.ROLE === 'Admin'); break;
    case 'STAFF':          filtered = filtered.filter(u => u.ROLE === 'Staff'); break;
    case 'VERIFIED':       filtered = filtered.filter(u => u.IS_VERIFIED == 1); break;
    case 'UNVERIFIED':     filtered = filtered.filter(u => u.IS_VERIFIED == 0); break;
  }

  if (query) {
    filtered = filtered.filter(u =>
      (u.FULL_NAME   || '').toLowerCase().includes(query) ||
      (u.EMAIL       || '').toLowerCase().includes(query) ||
      (u.EMPLOYEE_ID || '').toLowerCase().includes(query) ||
      (u.DEPARTMENT  || '').toLowerCase().includes(query)
    );
  }

  renderTable(filtered);
}

function openEditModal(userId) {
  const user = users.find(u => u.USER_ID == userId);
  if (!user) return;

  document.getElementById('editUserId').value   = user.USER_ID;
  document.getElementById('editFullName').value  = user.FULL_NAME;
  document.getElementById('editEmail').value     = user.EMAIL;
  document.getElementById('editEmployeeId').value= user.EMPLOYEE_ID;
  document.getElementById('editPassword').value  = '';
  document.getElementById('editRole').value      = user.ROLE;

  const deptSelect = document.getElementById('editDepartment');
  for (const opt of deptSelect.options) {
    opt.selected = opt.value === user.DEPARTMENT;
  }
  if (!deptSelect.value) {
    const custom = document.createElement('option');
    custom.value = user.DEPARTMENT;
    custom.textContent = user.DEPARTMENT;
    custom.selected = true;
    deptSelect.prepend(custom);
  }

  clearErrors(['errEditFullName','errEditEmail','errEditEmployeeId','errEditDepartment']);
  openModal('editUserModal');
}

function openDeleteModal(userId, userName) {
  document.getElementById('deleteUserId').value       = userId;
  document.getElementById('deleteUserName').textContent = userName;
  openModal('deleteUserModal');
}

async function submitAddUser() {
  clearErrors(['errAddFullName','errAddEmail','errAddEmployeeId','errAddDepartment','errAddPassword']);

  const payload = {
    full_name:   document.getElementById('addFullName').value.trim(),
    email:       document.getElementById('addEmail').value.trim(),
    employee_id: document.getElementById('addEmployeeId').value.trim(),
    department:  document.getElementById('addDepartment').value,
    password:    document.getElementById('addPassword').value,
    role:        document.getElementById('addRole').value,
  };

  let valid = true;
  if (!payload.full_name)   { setError('errAddFullName',   'Full name is required.');   valid = false; }
  if (!payload.email)       { setError('errAddEmail',      'Email is required.');       valid = false; }
  if (!payload.employee_id) { setError('errAddEmployeeId', 'Employee ID is required.'); valid = false; }
  if (!payload.department)  { setError('errAddDepartment', 'Department is required.');  valid = false; }
  if (!payload.password)    { setError('errAddPassword',   'Password is required.');    valid = false; }
  if (!valid) return;

  try {
    const res  = await fetch(`${API}?action=create`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.status === 'success') {
      closeModal('addUserModal');
      resetForm(['addFullName','addEmail','addEmployeeId','addPassword']);
      document.getElementById('addRole').value       = 'Staff';
      document.getElementById('addDepartment').value = '';
      showToast('User added successfully.');
      await loadUsers();
    } else {
      showToast(json.message || 'Failed to add user.', 'error');
    }
  } catch {
    showToast('Network error.', 'error');
  }
}

async function submitEditUser() {
  clearErrors(['errEditFullName','errEditEmail','errEditEmployeeId','errEditDepartment']);

  const payload = {
    user_id:     document.getElementById('editUserId').value,
    full_name:   document.getElementById('editFullName').value.trim(),
    email:       document.getElementById('editEmail').value.trim(),
    employee_id: document.getElementById('editEmployeeId').value.trim(),
    department:  document.getElementById('editDepartment').value,
    password:    document.getElementById('editPassword').value,
    role:        document.getElementById('editRole').value,
  };

  let valid = true;
  if (!payload.full_name)   { setError('errEditFullName',   'Full name is required.');   valid = false; }
  if (!payload.email)       { setError('errEditEmail',      'Email is required.');       valid = false; }
  if (!payload.employee_id) { setError('errEditEmployeeId', 'Employee ID is required.'); valid = false; }
  if (!payload.department)  { setError('errEditDepartment', 'Department is required.');  valid = false; }
  if (!valid) return;

  try {
    const res  = await fetch(`${API}?action=update`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.status === 'success') {
      closeModal('editUserModal');
      showToast('User updated successfully.');
      await loadUsers();
    } else {
      showToast(json.message || 'Failed to update user.', 'error');
    }
  } catch {
    showToast('Network error.', 'error');
  }
}

async function submitDeleteUser() {
  const userId = document.getElementById('deleteUserId').value;
  try {
    const res  = await fetch(`${API}?action=delete`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId }),
    });
    const json = await res.json();
    if (json.status === 'success') {
      closeModal('deleteUserModal');
      showToast('User removed successfully.');
      await loadUsers();
    } else {
      showToast(json.message || 'Failed to remove user.', 'error');
    }
  } catch {
    showToast('Network error.', 'error');
  }
}

function openModal(id)  { document.getElementById(id)?.classList.add('active'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('active'); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('active');
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
  }
});

function setError(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg;
}

function clearErrors(ids) {
  ids.forEach(id => setError(id, ''));
}

function resetForm(ids) {
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
}

let toastTimer;
function showToast(msg, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent  = msg;
  toast.style.borderLeftColor = type === 'error' ? '#dc2626' : '#7c3aed';
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function escAttr(str) {
  return String(str ?? '').replace(/'/g, "\\'");
}