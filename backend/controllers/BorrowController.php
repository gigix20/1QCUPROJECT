<?php
// backend/controllers/BorrowController.php

require_once __DIR__ . '/../models/BorrowModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class BorrowController {
  private $model;

  public function __construct($conn) {
    $this->model = new BorrowModel($conn);
  }


  
  // HANDLE REQUEST
  public function handleRequest() {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    $this->model->markOverdue();

    switch ($action) {
      case 'approve': $this->approveBorrow(); break;
      case 'cancel':  $this->cancelBorrow();  break;
      case 'getAll':   $this->getAll();       break;
      case 'getById':  $this->getById();      break;
      case 'getAsset': $this->getAssetInfo(); break;
      case 'add':      $this->add();          break;
      case 'return':   $this->returnAsset();  break;
      default:         ResponseHelper::sendError(400, 'Invalid action.'); break;
    }
  }


  
  // GET ALL
  private function getAll() {
    $borrows = $this->model->getAllBorrows();
    ResponseHelper::sendSuccess($borrows);
  }


  
  // GET BY ID
  private function getById() {
    $borrow_id = $_GET['borrow_id'] ?? '';
    if (!$borrow_id) {
      ResponseHelper::sendError(400, 'Borrow ID is required.');
      return;
    }
    $borrow = $this->model->getBorrowById($borrow_id);
    if (!$borrow) {
      ResponseHelper::sendError(404, 'Borrow record not found.');
      return;
    }
    ResponseHelper::sendSuccess($borrow);
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


  
  // ADD BORROW
  private function add() {
    $asset_id      = trim($_POST['asset_id']      ?? '');
    $department_id = trim($_POST['department_id'] ?? '');
    $first_name    = trim($_POST['first_name']    ?? '');
    $middle_name   = trim($_POST['middle_name']   ?? '');
    $last_name     = trim($_POST['last_name']     ?? '');
    $suffix        = trim($_POST['suffix']        ?? '');
    $borrow_date   = trim($_POST['borrow_date']   ?? '');
    $due_date      = trim($_POST['due_date']      ?? '');
    $purpose       = trim($_POST['purpose']       ?? '');

    if (!$asset_id || !$department_id || !$first_name || !$last_name ||
        !$borrow_date || !$due_date) {
      ResponseHelper::sendError(400, 'Please fill in all required fields.');
      return;
    }

    $asset = $this->model->getAssetInfo($asset_id);
    if (!$asset) {
      ResponseHelper::sendError(404, 'Asset not found.');
      return;
    }
    if ($asset['STATUS'] !== 'Available') {
      ResponseHelper::sendError(400, 'Asset is not available for borrowing.');
      return;
    }

    $this->model->addBorrow([
      'asset_id'      => $asset_id,
      'department_id' => $department_id,
      'first_name'    => $first_name,
      'middle_name'   => $middle_name,
      'last_name'     => $last_name,
      'suffix'        => $suffix,
      'borrow_date'   => $borrow_date,
      'due_date'      => $due_date,
      'purpose'       => $purpose,
    ]);

    ResponseHelper::sendSuccess(null, 'Borrow request submitted successfully.');
  }

  // APPROVE BORROW
private function approveBorrow() {
  $borrow_id = trim($_POST['borrow_id'] ?? '');
  if (!$borrow_id) {
    ResponseHelper::sendError(400, 'Borrow ID is required.');
    return;
  }
  $borrow = $this->model->getBorrowById($borrow_id);
  if (!$borrow) {
    ResponseHelper::sendError(404, 'Borrow record not found.');
    return;
  }
  if ($borrow['STATUS'] !== 'Pending') {
    ResponseHelper::sendError(400, 'Only pending requests can be approved.');
    return;
  }
  $this->model->approveBorrow($borrow_id);
  ResponseHelper::sendSuccess(null, 'Borrow request approved.');
}

// CANCEL BORROW
private function cancelBorrow() {
  $borrow_id = trim($_POST['borrow_id'] ?? '');
  if (!$borrow_id) {
    ResponseHelper::sendError(400, 'Borrow ID is required.');
    return;
  }
  $borrow = $this->model->getBorrowById($borrow_id);
  if (!$borrow) {
    ResponseHelper::sendError(404, 'Borrow record not found.');
    return;
  }
  if ($borrow['STATUS'] !== 'Pending') {
    ResponseHelper::sendError(400, 'Only pending requests can be cancelled.');
    return;
  }
  $this->model->cancelBorrow($borrow_id);
  ResponseHelper::sendSuccess(null, 'Borrow request cancelled.');
}

  // RETURN ASSET
  private function returnAsset() {
    $borrow_id   = trim($_POST['borrow_id']   ?? '');
    $return_date = trim($_POST['return_date'] ?? date('Y-m-d'));
    $remarks     = trim($_POST['remarks']     ?? '');

    if (!$borrow_id) {
      ResponseHelper::sendError(400, 'Borrow ID is required.');
      return;
    }

    $borrow = $this->model->getBorrowById($borrow_id);
    if (!$borrow) {
      ResponseHelper::sendError(404, 'Borrow record not found.');
      return;
    }
    if ($borrow['STATUS'] === 'Returned') {
      ResponseHelper::sendError(400, 'Asset has already been returned.');
      return;
    }

    $this->model->returnBorrow([
      'borrow_id'   => $borrow_id,
      'return_date' => $return_date,
      'remarks'     => $remarks,
    ]);

    ResponseHelper::sendSuccess(null, 'Asset returned successfully.');
  }

}
?>