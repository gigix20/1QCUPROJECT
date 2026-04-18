<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../controllers/UserController.php';

$action     = $_GET['action'] ?? '';
$controller = new UserController();

switch ($action) {

    case 'count':
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users");
        $stmt->execute();
        $row   = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int) ($row['TOTAL'] ?? $row['total'] ?? 0);
        ResponseHelper::sendSuccess(['count' => $count]);
        break;

    case 'list':
        $controller->getAll();
        break;

    case 'departments':
        $controller->getDepartments();
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $controller->create($data);
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $controller->update($data);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $controller->delete($data);
        break;

    default:
        ResponseHelper::sendError(400, 'Invalid action.');
}