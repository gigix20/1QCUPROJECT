<?php
// backend/routes/assets_route.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/AssetModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/../models/ItemTypeModel.php';
require_once __DIR__ . '/../models/CustodianModel.php';
require_once __DIR__ . '/../services/AssetIdService.php';
require_once __DIR__ . '/../controllers/AssetController.php';
require_once __DIR__ . '/../controllers/ExportController.php';
require_once __DIR__ . '/../controllers/CustodianController.php';

$resource = $_GET['resource'] ?? $_POST['resource'] ?? 'assets';

switch ($resource) {

    case 'assets':
        $controller = new AssetController($conn);
        $controller->handleRequest();
        break;

    case 'deletion_requests':
        $controller = new AssetController($conn);
        $controller->handleRequest();
        break;

    case 'departments':
        $model = new DepartmentModel($conn);
        ResponseHelper::sendSuccess($model->getAllDepartments());
        break;

    case 'custodians':
        // Used by the asset form to populate the custodian dropdown by department.
        $model      = new CustodianModel($conn);
        $dept_id    = trim($_GET['department_id'] ?? '');
        if (!empty($dept_id)) {
            ResponseHelper::sendSuccess($model->getCustodiansByDept($dept_id));
        } else {
            ResponseHelper::sendSuccess($model->getAllCustodians());
        }
        break;

    case 'categories':
        $model = new CategoryModel($conn);
        ResponseHelper::sendSuccess($model->getAllCategories());
        break;

    case 'item_types':
        $model = new ItemTypeModel($conn);
        ResponseHelper::sendSuccess($model->getAllItemTypes());
        break;

    case 'export':
        $controller = new ExportController($conn);
        $controller->handleRequest();
        break;

    default:
        ResponseHelper::sendError(404, 'Resource not found.');
}