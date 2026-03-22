<?php

require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class ReportController {

  private $model;

  public function __construct($conn) {
    $this->model = new ReportModel($conn);
  }

  // Helper: read + sanitize month/year from GET
  // Returns ['month'=>'03','year'=>'2025'] or empty strings
  private function getMonthYear() {
    $month = isset($_GET['month']) ? preg_replace('/[^0-9]/', '', $_GET['month']) : '';
    $year  = isset($_GET['year'])  ? preg_replace('/[^0-9]/', '', $_GET['year'])  : '';

    // Validate ranges
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
    $rows = $this->model->getRecentReports();
    ResponseHelper::sendSuccess($rows, 'Recent reports retrieved.');
  }

  // GET DEPARTMENTS
  public function getDepartments() {
    $rows = $this->model->getDepartments();
    ResponseHelper::sendSuccess($rows, 'Departments retrieved.');
  }

  // EXPORT: ASSET STATUS (& COMPLETE INVENTORY)
  public function exportAssetStatus() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period = $this->periodLabel($month, $year);

    $rows = $this->model->getAssetStatusSummary($month, $year);
    ReportService::exportAssetStatusReport([
      'rows'   => $rows,
      'period' => $period,
    ]);
  }

  // EXPORT: ASSET BY DEPARTMENT
  public function exportAssetByDepartment() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period   = $this->periodLabel($month, $year);
    $deptId   = isset($_GET['dept_id'])   ? trim($_GET['dept_id'])   : '';
    $deptName = isset($_GET['dept_name']) ? trim($_GET['dept_name']) : 'All Departments';

    $rows = $this->model->getAssetsByDepartment($deptId, $month, $year);
    ReportService::exportAssetByDepartmentReport([
      'rows'      => $rows,
      'dept_name' => $deptName,
      'period'    => $period,
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
      'overdue_maint'   => $items['overdue_maint'],
      'summary'         => $summary,
      'scope'           => $scope,
      'period'          => $period,
    ]);
  }

  // EXPORT: BORROWING ACTIVITY
  public function exportBorrowingActivity() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period = $this->periodLabel($month, $year);

    $data = $this->model->getBorrowingActivity($month, $year);
    ReportService::exportBorrowingActivityReport([
      'summary' => $data['summary'],
      'records' => $data['records'],
      'period'  => $period,
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

  // EXPORT: ASSET UTILIZATION
  public function exportAssetUtilization() {
    ['month' => $month, 'year' => $year] = $this->getMonthYear();
    $period = $this->periodLabel($month, $year);

    $data = $this->model->getAssetUtilization($month, $year);
    ReportService::exportAssetUtilizationReport([
      'rows'    => $data['rows'],
      'overall' => $data['overall'],
      'period'  => $period,
    ]);
  }

}
