<?php
// backend/routes/departments_route.php

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/DepartmentModel.php';
require_once __DIR__ . '/../controllers/DepartmentController.php';

$controller = new DepartmentController($conn);
$controller->handleRequest();
?>