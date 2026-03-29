<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$pdo = $conn;

// Restore session from remember_me cookie if not already logged in
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {

    $tokenHash = hash('sha256', $_COOKIE['remember_me']);

    $stmt = $pdo->prepare("
        SELECT rt.USER_ID, u.ROLE
        FROM REMEMBER_TOKENS rt
        JOIN users u ON u.USER_ID = rt.USER_ID
        WHERE rt.TOKEN_HASH = ?
        AND rt.EXPIRES_AT > SYSTIMESTAMP
    ");
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $_SESSION['user_id'] = $row['USER_ID'];
        $_SESSION['role']    = $row['ROLE'];
    } else {
        // Token expired or invalid — clear cookie
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    }
}
