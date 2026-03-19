// =============================================
// ONEQCU - Users Page (Staff View)
// View-only: search and filter tabs only
// =============================================

document.addEventListener('DOMContentLoaded', () => {
  renderTable();
  initSearch();
  initFilterTabs();
});

// =============================================
// DATA (replace with actual DB fetch)
// =============================================
let users = [];
let activeFilter = 'ALL USERS';

// =============================================
// RENDER TABLE
// =============================================
function renderTable(data = users) {
  const tbody = document.querySelector('.asset-table tbody');
  tbody.innerHTML = '';

  if (data.length === 0) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="8">No users to display.</td></tr>`;
    return;
  }

  data.forEach(user => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><span class="qr-tag">${user.id}</span></td>
      <td>${user.name}</td>
      <td>${user.email}</td>
      <td>${user.department || '—'}</td>
      <td><span class="badge ${roleBadgeClass(user.role)}">${user.role}</span></td>
      <td><span class="status-badge ${user.status === 'Active' ? 'active' : 'inactive'}">${user.status}</span></td>
      <td>${user.lastLogin || '—'}</td>
      <td>—</td>
    `;
    tbody.appendChild(tr);
  });
}

function roleBadgeClass(role) {
  switch (role) {
    case 'Administrator':      return 'in-use';
    case 'Property Custodian': return 'active';
    case 'Department Staff':   return 'returned';
    default:                   return 'pending';
  }
}

// =============================================
// SEARCH
// =============================================
function initSearch() {
  const searchInput = document.querySelector('.search-bar-full input');
  if (!searchInput) return;

  searchInput.addEventListener('input', () => {
    applyFilters(searchInput.value.toLowerCase().trim());
  });
}

// =============================================
// FILTER TABS
// =============================================
function initFilterTabs() {
  const tabs = document.querySelectorAll('.filter-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      activeFilter = tab.textContent.trim();
      const query = document.querySelector('.search-bar-full input')?.value.toLowerCase().trim() || '';
      applyFilters(query);
    });
  });
}

function applyFilters(query = '') {
  let filtered = [...users];

  switch (activeFilter) {
    case 'ADMINISTRATORS':
      filtered = filtered.filter(u => u.role === 'Administrator'); break;
    case 'PROPERTY CUSTODIANS':
      filtered = filtered.filter(u => u.role === 'Property Custodian'); break;
    case 'DEPARTMENT STAFF':
      filtered = filtered.filter(u => u.role === 'Department Staff'); break;
    case 'ACTIVE':
      filtered = filtered.filter(u => u.status === 'Active'); break;
    case 'INACTIVE':
      filtered = filtered.filter(u => u.status === 'Inactive'); break;
  }

  if (query) {
    filtered = filtered.filter(u =>
      u.name.toLowerCase().includes(query) ||
      u.email.toLowerCase().includes(query) ||
      (u.department || '').toLowerCase().includes(query) ||
      u.role.toLowerCase().includes(query)
    );
  }

  renderTable(filtered);
}