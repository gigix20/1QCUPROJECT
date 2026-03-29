<?php
require_once __DIR__ . '/../auth.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Staff') {
    header("Location: /1QCUPROJECT/views/auth/login.php");
    exit;
}