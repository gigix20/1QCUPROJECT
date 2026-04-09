<?php
// backend/controllers/DepartmentController.php

require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class DepartmentController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new DepartmentModel($conn);
    }


    // HANDLE ALL REQUESTS
    public function handleRequest()
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':   $this->getAll();   break;
            case 'getById':  $this->getById();  break;
            case 'add':      $this->add();      break;
            case 'update':   $this->update();   break;
            case 'delete':   $this->delete();   break;
            default:         ResponseHelper::sendError(400, 'Invalid action.');
        }
    }


    // GET ALL
    private function getAll()
    {
        $departments = $this->model->getAllDepartments();
        ResponseHelper::sendSuccess($departments);
    }


    // GET BY ID
    private function getById()
    {
        $id = trim($_GET['department_id'] ?? '');
        if (empty($id)) {
            ResponseHelper::sendError(400, 'Department ID is required.');
            return;
        }
        $dept = $this->model->getDepartmentById($id);
        if (!$dept) {
            ResponseHelper::sendError(404, 'Department not found.');
            return;
        }
        ResponseHelper::sendSuccess($dept);
    }


    // ADD
    private function add()
    {
        $name   = trim($_POST['department_name'] ?? '');
        $build  = trim($_POST['building']        ?? '');
        $head   = trim($_POST['department_head'] ?? '');
        $status = trim($_POST['status']          ?? 'Active');

        if (empty($name)) {
            ResponseHelper::sendError(400, 'Department name is required.');
            return;
        }
        if ($this->model->nameExists($name)) {
            ResponseHelper::sendError(409, 'A department with that name already exists.');
            return;
        }

        $data = [
            'department_name' => $name,
            'building'        => $build ?: null,
            'department_head' => $head  ?: null,
            'status'          => in_array($status, ['Active', 'Inactive']) ? $status : 'Active',
        ];

        if ($this->model->addDepartment($data)) {
            ResponseHelper::sendSuccess(null, 'Department added successfully.');
        } else {
            ResponseHelper::sendError(500, 'Failed to add department.');
        }
    }


    // UPDATE
    private function update()
    {
        $id     = trim($_POST['department_id']   ?? '');
        $name   = trim($_POST['department_name'] ?? '');
        $build  = trim($_POST['building']        ?? '');
        $head   = trim($_POST['department_head'] ?? '');
        $status = trim($_POST['status']          ?? 'Active');

        if (empty($id)) {
            ResponseHelper::sendError(400, 'Department ID is required.');
            return;
        }
        if (empty($name)) {
            ResponseHelper::sendError(400, 'Department name is required.');
            return;
        }
        if (!$this->model->getDepartmentById($id)) {
            ResponseHelper::sendError(404, 'Department not found.');
            return;
        }
        if ($this->model->nameExists($name, $id)) {
            ResponseHelper::sendError(409, 'A department with that name already exists.');
            return;
        }

        $data = [
            'department_id'   => $id,
            'department_name' => $name,
            'building'        => $build ?: null,
            'department_head' => $head  ?: null,
            'status'          => in_array($status, ['Active', 'Inactive']) ? $status : 'Active',
        ];

        if ($this->model->updateDepartment($data)) {
            ResponseHelper::sendSuccess(null, 'Department updated successfully.');
        } else {
            ResponseHelper::sendError(500, 'Failed to update department.');
        }
    }


    // DELETE
    private function delete()
    {
        $id = trim($_POST['department_id'] ?? '');

        if (empty($id)) {
            ResponseHelper::sendError(400, 'Department ID is required.');
            return;
        }
        if (!$this->model->getDepartmentById($id)) {
            ResponseHelper::sendError(404, 'Department not found.');
            return;
        }
        if ($this->model->hasAssets($id)) {
            ResponseHelper::sendError(409, 'Cannot delete — this department still has assets assigned to it.');
            return;
        }
        if ($this->model->deleteDepartment($id)) {
            ResponseHelper::sendSuccess(null, 'Department deleted successfully.');
        } else {
            ResponseHelper::sendError(500, 'Failed to delete department.');
        }
    }
}