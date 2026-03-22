<?php
// backend/controllers/MaintenanceController.php

require_once __DIR__ . '/../models/MaintenanceModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class MaintenanceController {
  private $model;

  public function __construct($conn) {
    $this->model = new MaintenanceModel($conn);
  }

  
  // HANDLE REQUEST
  public function handleRequest() {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
      case 'getAll':      $this->getAll();       break;
      case 'getById':     $this->getById();      break;
      case 'getAsset':    $this->getAssetInfo(); break;
      case 'add':         $this->add();          break;
      case 'updateStatus':$this->updateStatus(); break;
      default:            ResponseHelper::sendError(400, 'Invalid action.'); break;
    }
  }

  // GET ALL
  private function getAll() {
    $maintenance = $this->model->getAllMaintenance();
    ResponseHelper::sendSuccess($maintenance);
  }

  // GET BY ID 
  private function getById() {
    $maintenance_id = $_GET['maintenance_id'] ?? '';
    if (!$maintenance_id) {
      ResponseHelper::sendError(400, 'Maintenance ID is required.');
      return;
    }
    $maintenance = $this->model->getMaintenanceById($maintenance_id);
    if (!$maintenance) {
      ResponseHelper::sendError(404, 'Maintenance record not found.');
      return;
    }
    ResponseHelper::sendSuccess($maintenance);
  }
 
  // GET ASSET INFO
  private function getAssetInfo() {
    $asset_id = $_GET['asset_id'] ?? '';
    if (!$asset_id) {
      ResponseHelper::sendError(400, 'Asset ID is required.');
      return;
    }
    $asset = $this->model->getAssetInfo($asset_id);
    if (!$asset) {
      ResponseHelper::sendError(404, 'Asset not found or unavailable.');
      return;
    }
    ResponseHelper::sendSuccess($asset);
  }
 
  // ADD MAINTENANCE
  private function add() {
    $asset_id         = trim($_POST['asset_id']          ?? '');
    $type_id          = trim($_POST['type_id']           ?? '');
    $issue_description= trim($_POST['issue_description'] ?? '');
    $tech_first_name  = trim($_POST['tech_first_name']   ?? '');
    $tech_middle_name = trim($_POST['tech_middle_name']  ?? '');
    $tech_last_name   = trim($_POST['tech_last_name']    ?? '');
    $tech_suffix      = trim($_POST['tech_suffix']       ?? '');
    $scheduled_date   = trim($_POST['scheduled_date']    ?? '');
    $notes            = trim($_POST['notes']             ?? '');

    if (!$asset_id || !$type_id || !$issue_description || !$scheduled_date) {
      ResponseHelper::sendError(400, 'Please fill in all required fields.');
      return;
    }

    $asset = $this->model->getAssetInfo($asset_id);
    if (!$asset) {
      ResponseHelper::sendError(404, 'Asset not found.');
      return;
    }
    if ($asset['STATUS'] === 'In Use') {
      ResponseHelper::sendError(400, 'Asset is currently borrowed and cannot be set for maintenance.');
      return;
    }

    $this->model->addMaintenance([
      'asset_id'          => $asset_id,
      'type_id'           => $type_id,
      'issue_description' => $issue_description,
      'tech_first_name'   => $tech_first_name,
      'tech_middle_name'  => $tech_middle_name,
      'tech_last_name'    => $tech_last_name,
      'tech_suffix'       => $tech_suffix,
      'scheduled_date'    => $scheduled_date,
      'notes'             => $notes,
    ]);

    ResponseHelper::sendSuccess(null, 'Maintenance request submitted successfully.');
  }

  // UPDATE STATUS
  private function updateStatus() {
    $maintenance_id = trim($_POST['maintenance_id'] ?? '');
    $status         = trim($_POST['status']         ?? '');
    $completed_date = trim($_POST['completed_date'] ?? '');

    if (!$maintenance_id || !$status) {
      ResponseHelper::sendError(400, 'Maintenance ID and status are required.');
      return;
    }

    $valid = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
    if (!in_array($status, $valid)) {
      ResponseHelper::sendError(400, 'Invalid status.');
      return;
    }

    $maintenance = $this->model->getMaintenanceById($maintenance_id);
    if (!$maintenance) {
      ResponseHelper::sendError(404, 'Maintenance record not found.');
      return;
    }

    $this->model->updateStatus([
      'maintenance_id' => $maintenance_id,
      'status'         => $status,
      'completed_date' => $completed_date,
    ]);

    ResponseHelper::sendSuccess(null, 'Maintenance status updated successfully.');
  }

}
?>