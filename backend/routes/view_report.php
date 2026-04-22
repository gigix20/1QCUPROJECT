<?php
session_start();
require_once __DIR__ . '/../middleware/requireAuth.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../helpers/audit_helper.php';
require_once __DIR__ . '/../controllers/ReportController.php';

const ADMIN_ONLY_RESOURCES = [
    'report_by_dept',
    'report_borrowing',
    'report_utilization',
    'report_audit_logs',
];

$resource = $_GET['resource'] ?? '';
$role     = $_SESSION['role'] ?? '';

if (!$resource) {
    http_response_code(400);
    echo pageMsg('Bad Request', 'No report resource specified.');
    exit;
}

if ($role === 'Staff' && in_array($resource, ADMIN_ONLY_RESOURCES)) {
    http_response_code(403);
    echo pageMsg('Access Denied', 'This report type is restricted to administrators.');
    exit;
}

$controller = new ReportController($conn);

switch ($resource) {
    case 'report_complete':    $controller->exportAssetComplete();     break;
    case 'report_status':      $controller->exportAssetStatus();       break;
    case 'report_certified':   $controller->exportCertifiedAssets();   break;
    case 'report_overdue':     $controller->exportOverdueItems();      break;
    case 'report_maintenance': $controller->exportMaintenanceReport(); break;
    case 'report_by_dept':     $controller->exportAssetByDepartment(); break;
    case 'report_borrowing':   $controller->exportBorrowingActivity(); break;
    case 'report_utilization': $controller->exportAssetUtilization();  break;
    case 'report_audit_logs':  $controller->exportAuditLogs();         break;
    default:
        http_response_code(404);
        echo pageMsg('Not Found', 'Unknown report type: ' . htmlspecialchars($resource));
        exit;
}

function pageMsg(string $title, string $body): string {
    return '<!DOCTYPE html><html><head><title>' . $title . '</title>'
         . '<style>body{font-family:sans-serif;padding:40px;color:#1f2937;}'
         . 'h2{margin-bottom:8px;}button{margin-top:16px;padding:8px 18px;'
         . 'background:#1a1a2e;color:#fff;border:none;border-radius:6px;cursor:pointer;}</style>'
         . '</head><body><h2>' . $title . '</h2><p>' . $body . '</p>'
         . '<button onclick="window.close()">Close</button></body></html>';
}