// staff-reports-scheduled.js
// Handles loading, rendering, and display of scheduled reports.
// Depends on: REPORT_API, showToast (defined in staff-reports.js)

// Load the active count for the stat card only
function loadScheduledCount() {
  fetch(REPORT_API + '?resource=scheduled_count&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        var el = document.getElementById('statScheduled');
        if (el) el.textContent = data.data.count;
      }
    })
    .catch(function() {});
}

// Load all scheduled reports and refresh the table + stat card
function loadScheduledReports() {
  fetch(REPORT_API + '?resource=scheduled_reports&_=' + Date.now())
    .then(function(res)  { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        scheduledList = data.data || [];
        renderScheduledTable();
        var el = document.getElementById('statScheduled');
        if (el) el.textContent = scheduledList.filter(function(s) {
          return s.IS_ACTIVE == 1 || s.is_active == 1;
        }).length;
      }
    })
    .catch(function() { showToast('⚠ Failed to load scheduled reports.'); });
}

// Converts '13:00' to '01:00 PM', '08:00' to '08:00 AM'
function convertTo12hr(timeStr) {
  if (!timeStr) return '—';
  var parts  = timeStr.split(':');
  var hours  = parseInt(parts[0], 10);
  var mins   = parts[1];
  var period = hours >= 12 ? 'PM' : 'AM';

  if (hours === 0)      hours = 12; // midnight → 12:xx AM
  else if (hours > 12) hours -= 12; // 13 → 1, 14 → 2, etc.

  return String(hours).padStart(2, '0') + ':' + mins + ' ' + period;
}

// Render the scheduled reports table
function renderScheduledTable() {
  var tbody = document.getElementById('scheduledTableBody');
  if (!tbody) return;

  if (!scheduledList.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No scheduled reports.</td></tr>';
    return;
  }

  tbody.innerHTML = scheduledList.map(function(s) {
    var isActive    = s.IS_ACTIVE == 1 || s.is_active == 1;
    var activeBadge = isActive
      ? '<span class="badge" style="background:#dcfce7;color:#166534;">Active</span>'
      : '<span class="badge" style="background:#fee2e2;color:#b91c1c;">Paused</span>';
    var id = s.SCHEDULE_ID || s.schedule_id;

    return '<tr>'
      + '<td><strong>' + (s.SCHEDULE_NAME || s.schedule_name || '—') + '</strong></td>'
      + '<td>' + (s.REPORT_TYPE   || s.report_type   || '—') + '</td>'
      + '<td>' + (s.FREQUENCY     || s.frequency     || '—') + '</td>'
      + '<td>' + (s.START_DATE    || s.start_date    || '—') + '</td>'
      + '<td>' + (s.NEXT_RUN_DATE || s.next_run_date || '—') + '</td>'
      + '<td>' + convertTo12hr(s.RUN_TIME || s.run_time) + '</td>'
      + '<td>' + activeBadge + '</td>'
      + '<td>'
      +   '<button class="view-btn" onclick="toggleSchedule(' + id + ')">'
      +     (isActive ? 'Pause' : 'Activate')
      +   '</button> '
      +   '<button class="view-btn" style="color:#dc2626;" onclick="deleteSchedule(' + id + ')">Delete</button>'
      + '</td>'
      + '</tr>';
  }).join('');
}