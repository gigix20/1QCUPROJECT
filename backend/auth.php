<?php
session_start();
require_once __DIR__ . '/config/database.php'; 

$pdo = $conn;

if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {

    $tokenHash = hash('sha256', $_COOKIE['remember_me']);

    $stmt = $pdo->prepare("
        SELECT USER_ID 
        FROM REMEMBER_TOKENS
        WHERE TOKEN_HASH = ?
        AND EXPIRES_AT > SYSTIMESTAMP
    ");
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $_SESSION['user_id'] = $row['USER_ID'];
    } else {
        // Token expired or invalid, delete cookie
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    }
}