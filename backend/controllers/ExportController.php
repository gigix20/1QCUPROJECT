<?php
// backend/controllers/ExportController.php
require_once __DIR__ . '/../models/AssetModel.php';
require_once __DIR__ . '/../services/ExportService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class ExportController {
  private $model;

  public function __construct($conn) {
    $this->model = new AssetModel($conn);
  }

  public function handleRequest() {
    $scope           = $_GET['scope']           ?? 'all';
    $include_deleted = $_GET['include_deleted'] ?? '0';
    $asset_ids       = $_GET['asset_ids']       ?? '';

    // Get assets
    if ($scope === 'filtered' && !empty($asset_ids)) {
      $ids    = explode(',', $asset_ids);
      $assets = $this->model->getAssetsByIds($ids);
    } else {
      $assets = $this->model->getAllAssets();
    }

    // Filter pending deletion if not included
    if ($include_deleted !== '1') {
      $assets = array_filter($assets, function($a) {
        return $a['IS_DELETED'] != 1;
      });
      $assets = array_values($assets);
    }

    ExportService::exportPDF($assets);
  }
}
?>