<?php
session_start();
require_once __DIR__ . '/../middleware/requireStaff.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../helpers/audit_helper.php';
require_once __DIR__ . '/../controllers/ReportController.php';

$resource   = $_GET['resource'] ?? '';
$controller = new ReportController($conn);

switch ($resource) {

    case 'departments':
        $controller->getDepartments();
        break;
    case 'save_report':
        $controller->saveReport();
        break;
    case 'recent_reports':
        $controller->getRecentReports();
        break;
    case 'scheduled_reports':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createSchedule();
        } else {
            $controller->getScheduledReports();
        }
        break;
    case 'scheduled_count':
        $controller->getScheduledCount();
        break;
    case 'toggle_schedule':
        $controller->toggleSchedule();
        break;
    case 'delete_schedule':
        $controller->deleteSchedule();
        break;
    case 'bump_schedule':
        $controller->bumpSchedule();
        break;
    case 'due_schedules':
        $controller->getDueSchedules();
        break;
    case 'run_scheduled':
        $controller->runScheduledReport();
        break;

    case 'report_complete':
        $controller->exportAssetComplete();
        break;
    case 'report_status':
        $controller->exportAssetStatus();
        break;
    case 'report_certified':
        $controller->exportCertifiedAssets();
        break;
    case 'report_overdue':
        $controller->exportOverdueItems();
        break;
    case 'report_maintenance':
        $controller->exportMaintenanceReport();
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unknown resource: ' . htmlspecialchars($resource)]);
        exit;
}