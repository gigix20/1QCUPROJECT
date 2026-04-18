<?php
// backend/routes/audit_route.php

require_once __DIR__ . '/../middleware/requireAdmin.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../controllers/AuditController.php';

$controller = new AuditController($conn);
$resource   = $_GET['resource'] ?? '';

switch ($resource) {
    case 'audit_stats':  $controller->getStats();  break;
    case 'audit_logs':   $controller->getLogs();   break;
    case 'clear_audit':  $controller->clearLogs(); break;
    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown resource: ' . htmlspecialchars($resource)]);
        exit;
}