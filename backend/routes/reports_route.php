<?php

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/controllers/ReportController.php';
require_once __DIR__ . '/../../backend/helpers/ResponseHelper.php';

$resource   = $_GET['resource'] ?? '';
$method     = $_SERVER['REQUEST_METHOD'];
$controller = new ReportController($conn);

switch ($resource) {
  case 'save_report':          
    if ($method === 'POST') 
      $controller->saveReport();    
    break;

  case 'recent_reports':       
    if ($method === 'GET')  
      $controller->getRecentReports(); 
    break;

  case 'departments':          
    $controller->getDepartments();           
     break;

  case 'report_status':        
    $controller->exportAssetStatus();         
    break;

    case 'report_complete':        
    $controller->exportAssetComplete();         
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
  ResponseHelper::sendError(404, 'Resource not found.');
}
