<?php
// backend/routes/assets_route.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/AssetModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/../controllers/AssetController.php';

$resource = $_GET['resource'] ?? 'assets';

switch ($resource) {

  case 'assets':
    $controller = new AssetController($conn);
    $controller->handleRequest();
    break;

  case 'departments':
    $model = new DepartmentModel($conn);
    ResponseHelper::sendSuccess($model->getAllDepartments());
    break;

  case 'categories':
    $model = new CategoryModel($conn);
    ResponseHelper::sendSuccess($model->getAllCategories());
    break;

  default:
    ResponseHelper::sendError(404, 'Resource not found.');
}
?>