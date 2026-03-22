<?php
// backend/controllers/BorrowExportController.php

require_once __DIR__ . '/../models/BorrowModel.php';
require_once __DIR__ . '/../services/BorrowExportService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class BorrowExportController {
  private $model;

  public function __construct($conn) {
    $this->model = new BorrowModel($conn);
  }

  // HANDLE REQUEST
  public function handleRequest() {
    $scope             = $_GET['scope']             ?? 'all';
    $include_cancelled = $_GET['include_cancelled'] ?? '0';
    $borrow_ids        = $_GET['borrow_ids']        ?? '';

    // Get borrows
    if ($scope === 'filtered' && !empty($borrow_ids)) {
      $ids     = explode(',', $borrow_ids);
      $borrows = $this->model->getBorrowsByIds($ids);
    } else {
      $borrows = $this->model->getAllBorrows();
    }

    // Filter cancelled if not included
    if ($include_cancelled !== '1') {
      $borrows = array_values(array_filter($borrows, function($b) {
        return $b['STATUS'] !== 'Cancelled';
      }));
    }

    BorrowExportService::exportPDF($borrows);
  }

}
?>