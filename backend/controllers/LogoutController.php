
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = $conn;

// Delete remember token if it exists
if (!empty($_COOKIE['remember_me'])) {
    $tokenHash = hash('sha256', $_COOKIE['remember_me']);

    $stmt = $pdo->prepare("DELETE FROM REMEMBER_TOKENS WHERE TOKEN_HASH = ?");
    $stmt->execute([$tokenHash]);

    // Delete cookie
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: /1QCUPROJECT/views/auth/login.php");
exit;