<?php

require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../services/ScheduledReportService.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class ReportController {

  private $model;
  private $scheduleSvc;

  public function __construct($conn) {
    $this->model = new ReportModel($conn);
    $this->scheduleSvc = new ScheduledReportService($this->model);
  }

  // Helper: read + sanitize month/year from GET
  private function getMonthYear() {
    $month = isset($_GET['month']) ? preg_replace('/[^0-9]/', '', $_GET['month']) : '';
    $year  = isset($_GET['year'])  ? preg_replace('/[^0-9]/', '', $_GET['year'])  : '';

    if ($month && ((int)$month < 1 || (int)$month > 12)) $month = '';
    if ($year  && strlen($year) !== 4)                    $year  = '';

    return ['month' => $month, 'year' => $year];
  }

  // Helper: build period label for PDF subtitle
  private function periodLabel($month, $year) {
    if ($year && $month) {
      $dt = DateTime::createFromFormat('Y-m', $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT));
      return $dt ? $dt->format('F Y') : '';
    }
    if ($year) return $year;
    return '';
  }

  // SAVE REPORT
  public function saveReport() {
    $name = isset($_POST['report_name']) ? trim($_POST['report_name']) : '';
    $type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
    $url  = isset($_POST['file_url'])    ? trim($_POST['file_url'])    : '';

    if (!$name || !$type) {
      ResponseHelper::sendError(400, 'report_name and report_type are required.');
      return;
    }

    $this->model->saveReport($name, $type, $url);
    ResponseHelper::sendSuccess([], 'Report saved successfully.');
  }

  // GET RECENT REPORTS
  public function getRecentReports() {
    $rows     = $this->model->getRecentReports();
    $monthly  = $this->model->countReportsThisMonth();
    $allTime  = $this->model->countAllReports();
    ResponseHelper::sendSuccess([
        'reports'        => $rows,
        'monthly_count'  => $monthly,
        'all_time_count' => $allTime,
    ]);
}

  // GET DEPARTMENTS
  public function getDepartments() {
    $rows = $this->model->getDepartments();
    ResponseHelper::sendSuccess($rows, 'Departments retrieved.');
  }

    // EXPORT: Asset Complete (Complete Asset Inventory)
  public function exportAssetComplete() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $breakdown  = $this->model->getAssetBreakdown($month, $year);

    $rows = $this->model->getAssetStatusSummary($month, $year);
    ReportService::exportAssetComplete([
        'rows'        => $rows,
    'by_category' => $breakdown['by_category'],
    'by_item_type'=> $breakdown['by_item_type'],
  ]);
  }

  // EXPORT: ASSET STATUS (Complete Asset Inventory + Asset Status Report)
  public function exportAssetStatus() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period = $this->periodLabel($month, $year);

    $rows = $this->model->getAssetStatusSummary($month, $year);
    ReportService::exportAssetStatusReport([
      'rows'   => $rows,
      'period' => $period,
    ]);
  }

  // EXPORT: CERTIFIED ASSETS
  public function exportCertifiedAssets() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period   = $this->periodLabel($month, $year);
    $deptId   = isset($_GET['dept_id'])   ? trim($_GET['dept_id'])   : '';
    $deptName = isset($_GET['dept_name']) ? trim($_GET['dept_name']) : 'All Departments';

    $rows = $this->model->getCertifiedAssets($deptId, $month, $year);
    ReportService::exportCertifiedAssetsReport([
      'rows'      => $rows,
      'dept_name' => $deptName,
      'period'    => $period,
    ]);
  }

  // EXPORT: OVERDUE ITEMS
  public function exportOverdueItems() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period = $this->periodLabel($month, $year);
    $scope  = isset($_GET['scope']) ? trim($_GET['scope']) : 'all';

    $items   = $this->model->getOverdueItems($scope, $month, $year);
    $summary = $this->model->getOverdueSummary($scope, $month, $year);

    ReportService::exportOverdueItemsReport([
      'overdue_borrows' => $items['overdue_borrows'],
      'late_returns'    => $items['late_returns'],
      'overdue_active'  => $items['overdue_active'],
      'summary'         => $summary,
      'scope'           => $scope,
      'period'          => $period,
    ]);
  }

  // EXPORT: MAINTENANCE REPORT
  public function exportMaintenanceReport() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period = $this->periodLabel($month, $year);

    $data = $this->model->getMaintenanceSummary($month, $year);
    ReportService::exportMaintenanceReport([
      'summary' => $data['summary'],
      'by_type' => $data['by_type'],
      'period'  => $period,
    ]);
  }

  // SCHEDULED REPORTS
  
  public function getScheduledReports() {
      $rows = $this->scheduleSvc->getAll();
      ResponseHelper::sendSuccess($rows, 'Scheduled reports retrieved.');
  }

  public function createSchedule() {
    $body = $_POST;
    if (empty($body)) {
      $body = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $result = $this->scheduleSvc->create([
    'schedule_name' => $body['schedule_name'] ?? ($body['name'] ?? ''),
    'report_type'   => $body['report_type']   ?? ($body['type'] ?? ''),
    'frequency'     => $body['frequency']     ?? '',
    'start_date'    => $body['start_date']    ?? '',
    'run_time'      => $body['run_time']      ?? '08:00',
    'created_by'    => $_SESSION['username']  ?? 'Staff',
  ]);

    if ($result['ok']) {
      ResponseHelper::sendSuccess(['schedule_id' => $result['id'] ?? null], 'Schedule created.');
    } else {
      ResponseHelper::sendError(400, $result['message']);
    }
  }

  public function toggleSchedule() {
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) { ResponseHelper::sendError(400, 'Missing schedule id.'); return; }

    $result = $this->scheduleSvc->toggle($id);
    if ($result['ok']) {
      ResponseHelper::sendSuccess(['is_active' => $result['is_active']], $result['message']);
    } else {
      ResponseHelper::sendError(404, $result['message']);
    }
  }

  public function deleteSchedule() {
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) { ResponseHelper::sendError(400, 'Missing schedule id.'); return; }

    $result = $this->scheduleSvc->delete($id);
    if ($result['ok']) {
      ResponseHelper::sendSuccess(null, $result['message']);
    } else {
      ResponseHelper::sendError(404, $result['message']);
    }
  }

  public function bumpSchedule() {
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) { ResponseHelper::sendError(400, 'Missing schedule id.'); return; }

    $result = $this->scheduleSvc->bumpNextRun($id);
    if ($result['ok']) {
      ResponseHelper::sendSuccess(['next_run_date' => $result['next_run_date']], $result['message']);
    } else {
      ResponseHelper::sendError(404, $result['message']);
    }
  }

  public function getDueSchedules() {
    $rows = $this->scheduleSvc->getDue();
    ResponseHelper::sendSuccess($rows, 'Due schedules retrieved.');
  }

  public function runScheduledReport() {
    $type   = isset($_GET['type']) ? trim($_GET['type']) : '';
    ['month' => $month, 'year' => $year] = $this->getMonthYear();

    if (!$type) {
      ResponseHelper::sendError(400, 'Missing report type.');
      return;
    }

    $routeBase = '/1QCUPROJECT/backend/routes/reports_route.php';

    switch ($type) {
      case 'Complete Asset Inventory':
        $url = $routeBase . '?resource=report_complete';
        break;
      case 'Asset Status Report':
        $url = $routeBase . '?resource=report_status';
        break;
      case 'Certified Assets Report':
        $url = $routeBase . '?resource=report_certified';
        break;
      case 'Overdue Items Report':
        $url = $routeBase . '?resource=report_overdue';
        break;
      case 'Maintenance Report':
        $url = $routeBase . '?resource=report_maintenance';
        break;
      default:
        ResponseHelper::sendError(400, 'Unknown report type: ' . $type);
        return;
    }

    // Append month/year to URL if provided
    if ($month) $url .= '&month=' . $month;
    if ($year)  $url .= '&year='  . $year;

    // Save entry to recent reports so staff can view it later
    $this->model->saveReport($type . ' (Scheduled)', $type, $url);

    ResponseHelper::sendSuccess(['url' => $url], 'Report generated successfully.');
  }
}
