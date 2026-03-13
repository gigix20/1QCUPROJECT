<?php
// backend/controllers/MaintenanceExportController.php

require_once __DIR__ . '/../models/MaintenanceModel.php';
require_once __DIR__ . '/../services/MaintenanceExportService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class MaintenanceExportController {
  private $model;

  public function __construct($conn) {
    $this->model = new MaintenanceModel($conn);
  }

  public function handleRequest() {
    $scope             = $_GET['scope']             ?? 'all';
    $include_cancelled = $_GET['include_cancelled'] ?? '0';
    $maintenance_ids   = $_GET['maintenance_ids']   ?? '';

    if ($scope === 'filtered' && !empty($maintenance_ids)) {
      $ids         = explode(',', $maintenance_ids);
      $maintenance = $this->model->getMaintenanceByIds($ids);
    } else {
      $maintenance = $this->model->getAllMaintenance();
    }

    if ($include_cancelled !== '1') {
      $maintenance = array_values(array_filter($maintenance, function($m) {
        return $m['STATUS'] !== 'Cancelled';
      }));
    }

    MaintenanceExportService::exportPDF($maintenance);
  }

}
?>