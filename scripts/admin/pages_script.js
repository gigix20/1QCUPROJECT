// FILTER TABS
document.querySelectorAll('.filter-tabs').forEach(function(group) {
  group.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      group.querySelectorAll('.filter-tab').forEach(function(t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
    });
  });
});

// SETTINGS SETTINGS
var settingsNavItems = document.querySelectorAll('.settings-nav-item');
var settingsPanels   = document.querySelectorAll('.settings-panel-content');

settingsNavItems.forEach(function(item) {
  item.addEventListener('click', function() {
    settingsNavItems.forEach(function(n) { n.classList.remove('active'); });
    this.classList.add('active');

    settingsPanels.forEach(function(p) { p.classList.remove('active'); });
    var panel = document.getElementById('panel-' + this.getAttribute('data-panel'));
    if (panel) panel.classList.add('active');
  });
});

// SETTINGS SAVE
var saveBtn = document.getElementById('saveSettingsBtn');
if (saveBtn) {
  saveBtn.addEventListener('click', function() {
    openModal('saveModal');
  });
}


// MODAL FUNCTIONS
function openModal(id) {
  var modal = document.getElementById(id);
  if (modal) modal.classList.add('active');
}

function closeModal(id) {
  var modal = document.getElementById(id);
  if (modal) modal.classList.remove('active');
}

// Close on outside click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
  });
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
      m.classList.remove('active');
    });
  }
});
