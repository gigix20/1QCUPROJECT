<?php
// backend/routes/custodians_route.php

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/CustodianModel.php';
require_once __DIR__ . '/../controllers/CustodianController.php';

$controller = new CustodianController($conn);
$controller->handleRequest();