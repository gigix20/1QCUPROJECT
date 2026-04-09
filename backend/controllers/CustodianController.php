<?php
// backend/controllers/CustodianController.php

require_once __DIR__ . '/../models/CustodianModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class CustodianController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new CustodianModel($conn);
    }


    // HANDLE ALL REQUESTS
    public function handleRequest()
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':       $this->getAll();       break;
            case 'getByDept':    $this->getByDept();    break;
            case 'getById':      $this->getById();      break;
            case 'add':          $this->add();          break;
            case 'update':       $this->update();       break;
            case 'delete':       $this->delete();       break;
            default:             ResponseHelper::sendError(400, 'Invalid action.');
        }
    }


    // GET ALL
    private function getAll()
    {
        $custodians = $this->model->getAllCustodians();
        ResponseHelper::sendSuccess($custodians);
    }


    // GET BY DEPARTMENT
    private function getByDept()
    {
        $dept_id = trim($_GET['department_id'] ?? '');
        if (empty($dept_id)) {
            ResponseHelper::sendError(400, 'Department ID is required.');
            return;
        }
        $custodians = $this->model->getCustodiansByDept($dept_id);
        ResponseHelper::sendSuccess($custodians);
    }


    // GET BY ID
    private function getById()
    {
        $id = trim($_GET['custodian_id'] ?? '');
        if (empty($id)) {
            ResponseHelper::sendError(400, 'Custodian ID is required.');
            return;
        }
        $custodian = $this->model->getCustodianById($id);
        if (!$custodian) {
            ResponseHelper::sendError(404, 'Custodian not found.');
            return;
        }
        ResponseHelper::sendSuccess($custodian);
    }


    // ADD
    private function add()
    {
        $dept_id     = trim($_POST['department_id'] ?? '');
        $first       = trim($_POST['first_name']    ?? '');
        $middle      = trim($_POST['middle_name']   ?? '');
        $last        = trim($_POST['last_name']     ?? '');
        $suffix      = trim($_POST['suffix']        ?? '');
        $employee_id = trim($_POST['employee_id']   ?? '');
        $email       = trim($_POST['email']         ?? '');
        $phone       = trim($_POST['phone']         ?? '');
        $status      = trim($_POST['status']        ?? 'Active');

        if (empty($dept_id)) {
            ResponseHelper::sendError(400, 'Department is required.');
            return;
        }
        if (empty($first)) {
            ResponseHelper::sendError(400, 'First name is required.');
            return;
        }
        if (empty($last)) {
            ResponseHelper::sendError(400, 'Last name is required.');
            return;
        }
        if (!empty($employee_id) && $this->model->employeeIdExists($employee_id)) {
            ResponseHelper::sendError(409, 'A custodian with that Employee ID already exists.');
            return;
        }

        $data = [
            'department_id' => $dept_id,
            'first_name'    => $first,
            'middle_name'   => $middle  ?: null,
            'last_name'     => $last,
            'suffix'        => $suffix  ?: null,
            'employee_id'   => $employee_id ?: null,
            'email'         => $email   ?: null,
            'phone'         => $phone   ?: null,
            'status'        => in_array($status, ['Active', 'Inactive']) ? $status : 'Active',
        ];

        if ($this->model->addCustodian($data)) {
            ResponseHelper::sendSuccess(null, 'Custodian added successfully.');
        } else {
            ResponseHelper::sendError(500, 'Failed to add custodian.');
        }
    }


    // UPDATE
    private function update()
    {
        $id          = trim($_POST['custodian_id']  ?? '');
        $dept_id     = trim($_POST['department_id'] ?? '');
        $first       = trim($_POST['first_name']    ?? '');
        $middle      = trim($_POST['middle_name']   ?? '');
        $last        = trim($_POST['last_name']     ?? '');
        $suffix      = trim($_POST['suffix']        ?? '');
        $employee_id = trim($_POST['employee_id']   ?? '');
        $email       = trim($_POST['email']         ?? '');
        $phone       = trim($_POST['phone']         ?? '');
        $status      = trim($_POST['status']        ?? 'Active');

        if (empty($id)) {
            ResponseHelper::sendError(400, 'Custodian ID is required.');
            return;
        }
        if (empty($dept_id)) {
            ResponseHelper::sendError(400, 'Department is required.');
            return;
        }
        if (empty($first)) {
            ResponseHelper::sendError(400, 'First name is required.');
            return;
        }
        if (empty($last)) {
            ResponseHelper::sendError(400, 'Last name is required.');
            return;
        }
        if (!$this->model->getCustodianById($id)) {
            ResponseHelper::sendError(404, 'Custodian not found.');
            return;
        }
        if (!empty($employee_id) && $this->model->employeeIdExists($employee_id, $id)) {
            ResponseHelper::sendError(409, 'A custodian with that Employee ID already exists.');
            return;
        }

        $data = [
            'custodian_id'  => $id,
            'department_id' => $dept_id,
            'first_name'    => $first,
            'middle_name'   => $middle  ?: null,
            'last_name'     => $last,
            'suffix'        => $suffix  ?: null,
            'employee_id'   => $employee_id ?: null,
            'email'         => $email   ?: null,
            'phone'         => $phone   ?: null,
            'status'        => in_array($status, ['Active', 'Inactive']) ? $status : 'Active',
        ];

        if ($this->model->updateCustodian($data)) {
            ResponseHelper::sendSuccess(null, 'Custodian updated successfully.');
        } else {
            ResponseHelper::sendError(500, 'Failed to update custodian.');
        }
    }


    // DELETE
    // Assets assigned to this custodian will have custodian_id set to NULL (ON DELETE SET NULL).
    private function delete()
    {
        $id = trim($_POST['custodian_id'] ?? '');

        if (empty($id)) {
            ResponseHelper::sendError(400, 'Custodian ID is required.');
            return;
        }
        if (!$this->model->getCustodianById($id)) {
            ResponseHelper::sendError(404, 'Custodian not found.');
            return;
        }

        $assetCount = $this->model->getAssetCount($id);

        if ($this->model->deleteCustodian($id)) {
            $msg = 'Custodian deleted.';
            if ($assetCount > 0) {
                $msg .= ' ' . $assetCount . ' asset(s) have been unassigned.';
            }
            ResponseHelper::sendSuccess(['unassigned' => $assetCount], $msg);
        } else {
            ResponseHelper::sendError(500, 'Failed to delete custodian.');
        }
    }
}