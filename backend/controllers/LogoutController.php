
<?php
session_start();

require_once __DIR__ . '/../services/TokenService.php';

$tokenService = new TokenService();

// Delete remember token if it exists
if (!empty($_COOKIE['remember_me'])) {
    $tokenService->revokeToken($_COOKIE['remember_me']);
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: /1QCUPROJECT/views/auth/login.php");
exit;