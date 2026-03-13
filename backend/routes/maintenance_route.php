<?php
// backend/routes/maintenance_route.php

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/MaintenanceModel.php';
require_once __DIR__ . '/../controllers/MaintenanceController.php';
require_once __DIR__ . '/../controllers/MaintenanceExportController.php';

$resource = $_GET['resource'] ?? $_POST['resource'] ?? 'maintenance';

switch ($resource) {
  case 'maintenance':
    $controller = new MaintenanceController($conn);
    $controller->handleRequest();
    break;

  case 'maintenance_types':
    $model = new MaintenanceModel($conn);
    ResponseHelper::sendSuccess($model->getMaintenanceTypes());
    break;

  case 'maintenance_export':
    $controller = new MaintenanceExportController($conn);
    $controller->handleRequest();
    break;

  default:
    ResponseHelper::sendError(404, 'Resource not found.');
}
?>