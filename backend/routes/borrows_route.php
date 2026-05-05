<?php
// backend/routes/borrows_route.php
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline,
        'type' => 'PHP Error'
    ]);
    exit;
});

set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
        'type' => 'Exception'
    ]);
    exit;
});

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/ResponseHelper.php';
    require_once __DIR__ . '/../models/BorrowModel.php';
    require_once __DIR__ . '/../models/DepartmentModel.php';
    require_once __DIR__ . '/../controllers/BorrowController.php';
    require_once __DIR__ . '/../controllers/BorrowExportController.php';
    require_once __DIR__ . '/../middleware/requireApiAuth.php';

    $resource = $_GET['resource'] ?? $_POST['resource'] ?? 'borrows';

    switch ($resource) {
      case 'borrows':
        $controller = new BorrowController($conn);
        $controller->handleRequest();
        break;
      case 'departments':
        $model = new DepartmentModel($conn);
        ResponseHelper::sendSuccess($model->getAllDepartments());
        break;

      case 'borrow_export':
        $controller = new BorrowExportController($conn);
        $controller->handleRequest();
        break;
      
      default:
        ResponseHelper::sendError(404, 'Resource not found.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>