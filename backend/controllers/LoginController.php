<?php
session_start();
require_once __DIR__ . '/../services/LoginService.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'invalid_request']);
        exit;
    }
    header("Location: /1QCUPROJECT/views/auth/login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember_me']);

$loginService = new LoginService();
$result = $loginService->login($email, $password, $remember);

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if (isset($result['redirect'])) {
    header("Location: " . $result['redirect']);
    exit;
}

header("Location: /1QCUPROJECT/views/auth/login.php");
exit;