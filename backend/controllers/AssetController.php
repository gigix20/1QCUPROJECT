<?php
// controllers/AssetController.php

require_once __DIR__ . '/../models/AssetModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class AssetController {
  private $model;

  public function __construct($conn) {
    $this->model = new AssetModel($conn);
  }

  
  // HANDLE ALL REQUESTS
  public function handleRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
      case 'getAll':       $this->getAll();       break;
      case 'getById':      $this->getById();      break;
      case 'add':          $this->add();          break;
      case 'update':       $this->update();       break;
      case 'delete':       $this->delete();       break;
      case 'search':       $this->search();       break;
      case 'filterStatus': $this->filterStatus(); break;
      default:             ResponseHelper::sendError(400, 'Invalid action.');
    }
  }

  
  // GET ALL ASSETS
  private function getAll() {
    $assets = $this->model->getAllAssets();
    ResponseHelper::sendSuccess($assets);
  }

  
  // GET ASSET BY ID
  private function getById() {
    $asset_id = trim($_GET['asset_id'] ?? '');

    if (empty($asset_id)) {
      ResponseHelper::sendError(400, 'Asset ID is required.');
      return;
    }

    $asset = $this->model->getAssetById($asset_id);

    if (!$asset) {
      ResponseHelper::sendError(404, 'Asset not found.');
      return;
    }

    ResponseHelper::sendSuccess($asset);
  }

  
  // ADD ASSET
  private function add() {
    $asset_id      = trim($_POST['asset_id']      ?? '');
    $qr_code       = trim($_POST['qr_code']       ?? '');
    $description   = trim($_POST['description']   ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $category_id   = trim($_POST['category_id']   ?? '');
    $department_id = trim($_POST['department_id'] ?? '');
    $location      = trim($_POST['location']      ?? '');
    $status        = trim($_POST['status']        ?? 'Available');
    $is_certified  = (int) ($_POST['is_certified'] ?? 0);

    if (empty($asset_id))     { ResponseHelper::sendError(400, 'Asset ID is required.');    return; }
    if (empty($qr_code))      { ResponseHelper::sendError(400, 'QR Code is required.');     return; }
    if (empty($description))  { ResponseHelper::sendError(400, 'Description is required.'); return; }
    if (empty($department_id)){ ResponseHelper::sendError(400, 'Department is required.');  return; }

    if ($this->model->getAssetById($asset_id)) {
      ResponseHelper::sendError(409, 'Asset ID already exists.');
      return;
    }

    $data = [
      'asset_id'      => $asset_id,
      'qr_code'       => $qr_code,
      'description'   => $description,
      'serial_number' => $serial_number ?: null,
      'category_id'   => $category_id   ?: null,
      'department_id' => $department_id,
      'location'      => $location      ?: null,
      'status'        => $status,
      'is_certified'  => $is_certified,
    ];

    if ($this->model->addAsset($data)) {
      ResponseHelper::sendSuccess(null, 'Asset added successfully.');
    } else {
      ResponseHelper::sendError(500, 'Failed to add asset.');
    }
  }

  
  // UPDATE ASSET
  private function update() {
    $asset_id      = trim($_POST['asset_id']      ?? '');
    $description   = trim($_POST['description']   ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $category_id   = trim($_POST['category_id']   ?? '');
    $department_id = trim($_POST['department_id'] ?? '');
    $location      = trim($_POST['location']      ?? '');
    $status        = trim($_POST['status']        ?? 'Available');
    $is_certified  = (int) ($_POST['is_certified'] ?? 0);

    if (empty($asset_id))     { ResponseHelper::sendError(400, 'Asset ID is required.');    return; }
    if (empty($description))  { ResponseHelper::sendError(400, 'Description is required.'); return; }
    if (empty($department_id)){ ResponseHelper::sendError(400, 'Department is required.');  return; }

    if (!$this->model->getAssetById($asset_id)) {
      ResponseHelper::sendError(404, 'Asset not found.');
      return;
    }

    $data = [
      'asset_id'      => $asset_id,
      'description'   => $description,
      'serial_number' => $serial_number ?: null,
      'category_id'   => $category_id   ?: null,
      'department_id' => $department_id,
      'location'      => $location      ?: null,
      'status'        => $status,
      'is_certified'  => $is_certified,
    ];

    if ($this->model->updateAsset($data)) {
      ResponseHelper::sendSuccess(null, 'Asset updated successfully.');
    } else {
      ResponseHelper::sendError(500, 'Failed to update asset.');
    }
  }

  
  // DELETE ASSET
  private function delete() {
    $asset_id = trim($_POST['asset_id'] ?? '');

    if (empty($asset_id)) {
      ResponseHelper::sendError(400, 'Asset ID is required.');
      return;
    }

    if (!$this->model->getAssetById($asset_id)) {
      ResponseHelper::sendError(404, 'Asset not found.');
      return;
    }

    if ($this->model->deleteAsset($asset_id)) {
      ResponseHelper::sendSuccess(null, 'Asset deleted successfully.');
    } else {
      ResponseHelper::sendError(500, 'Failed to delete asset.');
    }
  }

  
  // SEARCH ASSETS
  private function search() {
    $keyword = trim($_GET['keyword'] ?? '');

    if (empty($keyword)) {
      $this->getAll();
      return;
    }

    $assets = $this->model->searchAssets($keyword);
    ResponseHelper::sendSuccess($assets);
  }

  
  // FILTER BY STATUS
  private function filterStatus() {
    $status = trim($_GET['status'] ?? '');

    if (empty($status) || $status === 'ALL') {
      $this->getAll();
      return;
    }

    $assets = $this->model->filterByStatus($status);
    ResponseHelper::sendSuccess($assets);
  }

}
?>