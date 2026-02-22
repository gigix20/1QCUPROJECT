<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();

require_once __DIR__ . '/../services/AuthService.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /1QCUPROJECT/views/auth/signup.php");
    exit;
}

$action = $_POST['form_action'] ?? '';

$authService = new AuthService();

switch ($action) {
    case 'register':
        $result = $authService->register($_POST);
        break;

    case 'verify_otp':
        $otpInput = trim($_POST['otp'] ?? '');
        $result = $authService->verifyOtp($otpInput);
        break;

    case 'resend_otp':
        $result = $authService->resendOtp();
        break;

    default:
        $result = ['success' => false, 'error' => 'invalid_action'];
        break;
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Non-AJAX fallback redirects
if (isset($result['redirect'])) {
    header("Location: " . $result['redirect']);
    exit;
}

header("Location: /1QCUPROJECT/views/auth/signup.php");
exit;