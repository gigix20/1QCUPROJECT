<?php
require_once __DIR__ . '/../controllers/ForgotPasswordController.php';

$controller = new ForgotPasswordController();
$controller->handleRequest();