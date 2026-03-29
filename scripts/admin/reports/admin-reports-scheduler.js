// staff-reports-scheduler.js
// Depends on: REPORT_API, addToRecentReports, loadScheduledReports,
//             loadRecentReports, showToast (defined in staff-reports.js)

var executedSchedules = {};

function startSchedulerPolling() {
  runDueSchedules();
  setInterval(runDueSchedules, 60 * 1000);
}

function runDueSchedules() {
  fetch(REPORT_API + '?resource=due_schedules&_=' + Date.now())
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.status === 'success' && data.data && data.data.length > 0) {
        data.data.forEach(function(schedule) {
          var id = schedule.schedule_id || schedule.SCHEDULE_ID;
          if (executedSchedules[id]) return;
          executedSchedules[id] = true;
          executeSchedule(schedule);
        });
      }
    })
    .catch(function() {});
}

function executeSchedule(schedule) {
  var id   = schedule.schedule_id   || schedule.SCHEDULE_ID;
  var type = schedule.report_type   || schedule.REPORT_TYPE;
  var name = schedule.schedule_name || schedule.SCHEDULE_NAME;

  // bump next_run_date first
  fetch(REPORT_API + '?resource=bump_schedule&id=' + id, { method: 'POST' })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.status !== 'success') {
        delete executedSchedules[id];
        return;
      }

      // call run_scheduled which logs + returns the PDF url
      fetch(REPORT_API + '?resource=run_scheduled&type=' + encodeURIComponent(type))
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data.status === 'success') {
            showToast('✓ Scheduled report generated: ' + name);
            loadScheduledReports();
            loadRecentReports(); // shows the new entry
          } else {
            showToast('⚠ Failed to generate: ' + name);
          }
        });
    })
    .catch(function() {
      delete executedSchedules[id];
    });
}