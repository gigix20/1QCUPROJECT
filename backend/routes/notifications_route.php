<?php

// Set JSON header FIRST before any code
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Suppress errors from being displayed (catch them instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set error handler to catch all errors
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

// Set exception handler for uncaught exceptions
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
    require_once __DIR__ . '/../controllers/NotificationController.php';
    require_once __DIR__ . '/../helpers/ResponseHelper.php';
    require_once __DIR__ . '/../middleware/requireApiAuth.php';

    $controller = new NotificationController($conn);

    $method = $_SERVER['REQUEST_METHOD'];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if ($method === 'POST' && empty($_POST) && stripos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        $jsonBody = json_decode($rawBody, true);
        if (is_array($jsonBody)) {
            $_POST = array_merge($_POST, $jsonBody);
        }
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'unread_count':
                    $controller->getUnreadCount();
                    break;
                case 'cleanup':
                    $controller->cleanupExpired();
                    break;
                default:
                    $controller->getNotifications();
                    break;
            }
            break;

        case 'POST':
            switch ($action) {
                case 'mark_read':
                    $controller->markAsRead();
                    break;
                case 'mark_all_read':
                    $controller->markAllAsRead();
                    break;
                case 'test':
                    $controller->createTestNotification();
                    break;
                default:
                    ResponseHelper::sendError(400, 'Invalid action');
                    break;
            }
            break;

        default:
            ResponseHelper::sendError(405, 'Method not allowed');
            break;
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