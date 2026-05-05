<?php

require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../services/ScheduledReportService.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/audit_helper.php';

class ReportController
{
    private $model;
    private $scheduleSvc;
    private $conn;

    public function __construct($conn)
    {
        $this->conn        = $conn;
        $this->model       = new ReportModel($conn);
        $this->scheduleSvc = new ScheduledReportService($this->model);
    }

    private function getMonthYear(): array
    {
        $month = isset($_GET['month']) ? preg_replace('/[^0-9]/', '', $_GET['month']) : '';
        $year  = isset($_GET['year'])  ? preg_replace('/[^0-9]/', '', $_GET['year'])  : '';

        if ($month && ((int)$month < 1 || (int)$month > 12)) $month = '';
        if ($year  && strlen($year) !== 4)                    $year  = '';

        return ['month' => $month, 'year' => $year];
    }

    private function currentUser(): string
    {
        return $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Unknown');
    }

    private function currentRole(): string
    {
        return $_SESSION['role'] ?? 'Staff';
    }

    public function saveReport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ResponseHelper::sendError(405, 'POST required.');
            return;
        }

        $name  = trim($_POST['report_name'] ?? '');
        $type  = trim($_POST['report_type'] ?? '');
        $url   = trim($_POST['file_url']    ?? '');
        $genBy = $this->currentUser();
        $role  = $this->currentRole();

        if (!$name || !$type) {
            ResponseHelper::sendError(400, 'report_name and report_type are required.');
            return;
        }

        $this->model->saveReport($name, $type, $url, $genBy, $role);
        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', "Generated report: $name");
        ResponseHelper::sendSuccess([], 'Report saved successfully.');
    }

    public function getRecentReports(): void
    {
        $role    = $this->currentRole();
        $rows    = $this->model->getRecentReports($role);
        $monthly = $this->model->countReportsThisMonth($role);
        $allTime = $this->model->countAllReports($role);
        ResponseHelper::sendSuccess([
            'reports'        => $rows,
            'monthly_count'  => $monthly,
            'all_time_count' => $allTime,
        ]);
    }

    public function getDepartments(): void
    {
        $rows = $this->model->getDepartments();
        ResponseHelper::sendSuccess($rows, 'Departments retrieved.');
    }

    public function exportAssetComplete(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getCompleteInventory($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Complete Asset Inventory');

        $this->outputReport('Complete Asset Inventory', $month, $year, $rows,
            ['ASSET_ID', 'DESCRIPTION', 'CATEGORY_NAME', 'DEPARTMENT_NAME',
             'ITEM_TYPE_NAME', 'STATUS', 'IS_CERTIFIED', 'SERIAL_NUMBER', 'LOCATION', 'CUSTODIAN', 'CREATED_AT'],
            ['Asset ID', 'Description', 'Category', 'Department',
             'Type', 'Status', 'Certified', 'Serial No.', 'Location', 'Custodian', 'Date Added'],
            ['subtitle' => $deptName]
        );
    }

    public function exportAssetStatus(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getAssetStatusRows($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Asset Status Report');

        $this->outputReport('Asset Status Report', $month, $year, $rows,
            ['ASSET_ID', 'DESCRIPTION', 'DEPARTMENT_NAME', 'STATUS', 'UPDATED_AT'],
            ['Asset ID', 'Description', 'Department', 'Status', 'Last Updated'],
            ['subtitle' => $deptName]
        );
    }

    public function exportCertifiedAssets(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getCertifiedAssetRows($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Certified Assets Report');

        $this->outputReport('Certified Assets Report', $month, $year, $rows,
            ['ASSET_ID', 'DESCRIPTION', 'CATEGORY_NAME', 'DEPARTMENT_NAME', 'CUSTODIAN', 'CERTIFIED_DATE'],
            ['Asset ID', 'Description', 'Category', 'Department', 'Custodian', 'Certified Date'],
            ['subtitle' => $deptName]
        );
    }

    public function exportOverdueItems(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $scope    = trim($_GET['scope']    ?? 'all');
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getOverdueItemRows($scope, $deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Overdue Items Report');

        $this->outputReport('Overdue Items Report', $month, $year, $rows,
            ['REF_ID', 'ASSET_ID', 'TYPE', 'BORROWER', 'DEPARTMENT_NAME', 'BORROW_DATE', 'DUE_DATE', 'STATUS'],
            ['Ref #', 'Asset ID', 'Type', 'Borrower', 'Department', 'Borrow Date', 'Due Date', 'Status/Return Date'],
            ['subtitle' => 'Scope: ' . ucfirst($scope) . ' | ' . $deptName]
        );
    }

    public function exportMaintenanceReport(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getMaintenanceRows($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Maintenance Report');

        $this->outputReport('Maintenance Report', $month, $year, $rows,
            ['MAINTENANCE_ID', 'ASSET_ID', 'TYPE_NAME', 'ISSUE_DESCRIPTION',
             'TECHNICIAN', 'DEPARTMENT_NAME', 'SCHEDULED_DATE', 'COMPLETED_DATE', 'STATUS', 'NOTES'],
            ['ID', 'Asset ID', 'Type', 'Issue', 'Technician', 'Department',
             'Scheduled', 'Completed', 'Status', 'Notes'],
            ['subtitle' => $deptName]
        );
    }

    public function exportAssetByDepartment(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getAssetsByDepartmentRows($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Asset by Department');

        $this->outputReport('Asset by Department', $month, $year, $rows,
            ['DEPARTMENT_NAME', 'ASSET_ID', 'DESCRIPTION', 'CATEGORY_NAME', 'ITEM_TYPE_NAME', 'STATUS', 'CREATED_AT'],
            ['Department', 'Asset ID', 'Description', 'Category', 'Type', 'Status', 'Added'],
            ['subtitle' => $deptName]
        );
    }

    public function exportBorrowingActivity(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getBorrowingActivityRows($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Borrowing Activity Report');

        $this->outputReport('Borrowing Activity Report', $month, $year, $rows,
            ['BORROW_ID', 'ASSET_ID', 'BORROWER', 'DEPARTMENT_NAME',
             'BORROW_DATE', 'DUE_DATE', 'RETURN_DATE', 'STATUS', 'PURPOSE'],
            ['ID', 'Asset ID', 'Borrower', 'Department',
             'Borrow Date', 'Due Date', 'Return Date', 'Status', 'Purpose'],
            ['subtitle' => $deptName]
        );
    }

    public function exportAssetUtilization(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $deptId   = trim($_GET['dept_id']   ?? '');
        $deptName = trim($_GET['dept_name'] ?? 'All Departments');

        $rows = $this->model->getAssetUtilizationRows($deptId, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Asset Utilization Report');

        $this->outputReport('Asset Utilization Report', $month, $year, $rows,
            ['ASSET_ID', 'DESCRIPTION', 'DEPARTMENT_NAME', 'STATUS', 'BORROW_COUNT', 'MAINT_COUNT'],
            ['Asset ID', 'Description', 'Department', 'Status', 'Times Borrowed', 'Maintenance Count'],
            ['subtitle' => $deptName]
        );
    }

    public function exportAuditLogs(): void
    {
        ['month' => $month, 'year' => $year] = $this->getMonthYear();
        $module = trim($_GET['module'] ?? '');
        $action = trim($_GET['action'] ?? '');

        $rows = $this->model->getAuditLogRows($module, $action, $month, $year);

        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', 'Generated: Audit Logs Report');

        $this->outputReport('Audit Logs Report', $month, $year, $rows,
            ['LOG_ID', 'ACTION_TYPE', 'MODULE', 'PERFORMED_BY', 'USER_ROLE', 'DESCRIPTION', 'IP_ADDRESS', 'CREATED_AT'],
            ['Log ID', 'Action', 'Module', 'Performed By', 'Role', 'Description', 'IP Address', 'Timestamp']
        );
    }

    public function getScheduledReports(): void
    {
        $role = $this->currentRole();
        $rows = $this->model->getAllSchedules($role);
        ResponseHelper::sendSuccess($rows, 'Scheduled reports retrieved.');
    }

    public function createSchedule(): void
    {
        $body = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

        $result = $this->scheduleSvc->create([
            'schedule_name'  => $body['schedule_name']  ?? ($body['name'] ?? ''),
            'report_type'    => $body['report_type']    ?? ($body['type'] ?? ''),
            'frequency'      => $body['frequency']      ?? '',
            'start_date'     => $body['start_date']     ?? '',
            'run_time'       => $body['run_time']       ?? '08:00',
            'created_by'     => $this->currentUser(),
            'created_by_role'=> $this->currentRole(),
        ]);

        if ($result['ok']) {
            logAudit($this->conn, 'SCHEDULE_CREATE', 'Reports',
                "Scheduled: " . ($body['schedule_name'] ?? '') . ' (' . ($body['frequency'] ?? '') . ')');
            ResponseHelper::sendSuccess(['schedule_id' => $result['id'] ?? null], 'Schedule created.');
        } else {
            ResponseHelper::sendError(400, $result['message']);
        }
    }

    public function toggleSchedule(): void
    {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) { ResponseHelper::sendError(400, 'Missing schedule id.'); return; }

        $result = $this->scheduleSvc->toggle($id);
        if ($result['ok']) {
            logAudit($this->conn, 'SCHEDULE_TOGGLE', 'Reports', $result['message'], (string)$id);
            ResponseHelper::sendSuccess(['is_active' => $result['is_active']], $result['message']);
        } else {
            ResponseHelper::sendError(404, $result['message']);
        }
    }

    public function deleteSchedule(): void
    {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) { ResponseHelper::sendError(400, 'Missing schedule id.'); return; }

        $result = $this->scheduleSvc->delete($id);
        if ($result['ok']) {
            logAudit($this->conn, 'SCHEDULE_DELETE', 'Reports', "Deleted schedule id=$id", (string)$id);
            ResponseHelper::sendSuccess(null, $result['message']);
        } else {
            ResponseHelper::sendError(404, $result['message']);
        }
    }

    public function bumpSchedule(): void
    {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) { ResponseHelper::sendError(400, 'Missing schedule id.'); return; }

        $result = $this->scheduleSvc->bumpNextRun($id);
        $result['ok']
            ? ResponseHelper::sendSuccess(['next_run_date' => $result['next_run_date']], $result['message'])
            : ResponseHelper::sendError(404, $result['message']);
    }

    public function getDueSchedules(): void
    {
        $role = $this->currentRole();
        $rows = $this->model->getDueSchedules($role);
        ResponseHelper::sendSuccess($rows, 'Due schedules retrieved.');
    }

    public function runScheduledReport(): void
    {
        $type = trim($_GET['type'] ?? '');
        if (!$type) { ResponseHelper::sendError(400, 'Missing report type.'); return; }

        $map = [
            'Complete Asset Inventory'  => 'report_complete',
            'Asset Status Report'       => 'report_status',
            'Certified Assets Report'   => 'report_certified',
            'Overdue Items Report'      => 'report_overdue',
            'Maintenance Report'        => 'report_maintenance',
            'Asset by Department'       => 'report_by_dept',
            'Borrowing Activity Report' => 'report_borrowing',
            'Asset Utilization Report'  => 'report_utilization',
            'Audit Logs Report'         => 'report_audit_logs',
        ];

        if (!isset($map[$type])) {
            ResponseHelper::sendError(400, 'Unknown report type: ' . $type);
            return;
        }

        $url  = '/1QCUPROJECT/backend/routes/view_report.php?resource=' . $map[$type];
        $name = $type . ' (Scheduled)';
        $role = $this->currentRole();

        $this->model->saveReport($name, $type, $url, $this->currentUser(), $role);
        logAudit($this->conn, 'REPORT_GENERATED', 'Reports', "Scheduled report generated: $name");
        ResponseHelper::sendSuccess(['url' => $url], 'Report generated successfully.');
    }

    public function getScheduledCount(): void
    {
        $role  = $this->currentRole();
        $count = $this->model->countActiveSchedules($role);
        ResponseHelper::sendSuccess(['count' => $count]);
    }

    private function outputReport(
        string $title,
        string $month,
        string $year,
        array  $rows,
        array  $cols,
        array  $headers,
        array  $opts = []
    ): void {
        $subtitle = $opts['subtitle'] ?? '';

        $filterLabel = '';
        if ($month || $year) {
            $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $filterLabel = trim(($month ? $months[(int)$month] : '') . ' ' . $year);
        }

        $genBy   = htmlspecialchars($this->currentUser());
        $genDate = date('F d, Y  h:i A');
        $count   = count($rows);

        $statusColor = function (string $val): string {
            $v = strtolower($val);
            if (in_array($v, ['available', 'active', 'completed']))  return '#166534;background:#dcfce7';
            if (in_array($v, ['borrowed', 'in use', 'in progress'])) return '#1d4ed8;background:#dbeafe';
            if (in_array($v, ['overdue', 'maintenance']))            return '#b45309;background:#fef3c7';
            if ($v === 'pending')                                     return '#6b21a8;background:#f3e8ff';
            return '#374151;background:#f3f4f6';
        };

        $tbody = '';
        foreach ($rows as $i => $row) {
            $bg     = $i % 2 === 0 ? '#fff' : '#f9fafb';
            $tbody .= "<tr style=\"background:$bg\">";
            foreach ($cols as $c) {
                $val = $row[$c] ?? $row[strtolower($c)] ?? '—';
                if ($c === 'IS_CERTIFIED') {
                    $val = ($val == 1) ? '✔ Yes' : 'No';
                }
                if ($c === 'STATUS') {
                    $sc     = $statusColor((string)$val);
                    $tbody .= "<td><span style=\"padding:2px 8px;border-radius:9px;font-size:11px;font-weight:600;color:$sc\">"
                            . htmlspecialchars((string)$val) . "</span></td>";
                } else {
                    $tbody .= '<td>' . htmlspecialchars((string)$val) . '</td>';
                }
            }
            $tbody .= '</tr>';
        }

        if (!$rows) {
            $colspan = count($cols);
            $tbody   = "<tr><td colspan=\"$colspan\" style=\"text-align:center;color:#888;padding:24px\">No records found.</td></tr>";
        }

        $thead              = '<tr>' . implode('', array_map(function($h) { return "<th>$h</th>"; }, $headers)) . '</tr>';
        $filterLabelHtml    = $filterLabel ? "<div class=\"rmeta\">Period: <strong>$filterLabel</strong></div>" : '';
        $subtitleHtml       = $subtitle    ? "<div class=\"rmeta\">$subtitle</div>" : '';
        $filterLabelDisplay = $filterLabel ?: 'All Time';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{$title}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; padding: 24px; }
    .header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; border-bottom: 3px solid #1a1a2e; padding-bottom: 12px; }
    .logo-block .school { font-size: 20px; font-weight: 800; color: #1a1a2e; letter-spacing: 1px; }
    .logo-block .sub    { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .report-meta        { text-align: right; }
    .report-meta .rtitle{ font-size: 15px; font-weight: 700; color: #1a1a2e; }
    .report-meta .rmeta { font-size: 11px; color: #6b7280; margin-top: 3px; }
    .summary-bar        { display: flex; gap: 12px; margin: 14px 0; }
    .scard              { background: #f3f4f6; border-radius: 6px; padding: 8px 16px; flex: 1; }
    .scard .sv          { font-size: 22px; font-weight: 700; color: #1a1a2e; }
    .scard .sl          { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
    table               { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead tr            { background: #1a1a2e; color: #fff; }
    thead th            { padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }
    tbody td            { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
    .footer             { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px; display: flex; justify-content: space-between; color: #9ca3af; font-size: 10px; }
    .action-bar         { text-align: right; margin-bottom: 10px; }
    .btn-print          { background: #1a1a2e; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-close          { background: #e5e7eb; color: #374151; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 13px; margin-left: 8px; }
    @media print {
      .action-bar { display: none !important; }
      body { padding: 10px; }
      @page { margin: 15mm; }
    }
  </style>
</head>
<body>

  <div class="action-bar">
    <button class="btn-print" onclick="window.print()">&#128449; Print / Save PDF</button>
    <button class="btn-close" onclick="window.close()">&#x2715; Close</button>
  </div>

  <div class="header">
    <div class="logo-block">
      <div class="school">ONEQCU</div>
      <div class="sub">Asset Management System — Quezon City University</div>
    </div>
    <div class="report-meta">
      <div class="rtitle">{$title}</div>
      {$subtitleHtml}
      <div class="rmeta">Generated by: <strong>{$genBy}</strong></div>
      <div class="rmeta">{$genDate}</div>
      {$filterLabelHtml}
    </div>
  </div>

  <div class="summary-bar">
    <div class="scard"><div class="sv">{$count}</div><div class="sl">Total Records</div></div>
    <div class="scard"><div class="sv">{$filterLabelDisplay}</div><div class="sl">Period</div></div>
    <div class="scard"><div class="sv">{$genBy}</div><div class="sl">Generated By</div></div>
  </div>

  <table>
    <thead>{$thead}</thead>
    <tbody>{$tbody}</tbody>
  </table>

  <div class="footer">
    <span>ONEQCU Asset Management System</span>
    <span>Report: {$title} • {$genDate}</span>
    <span>Total: {$count} record(s)</span>
  </div>

</body>
</html>
HTML;

        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }
}