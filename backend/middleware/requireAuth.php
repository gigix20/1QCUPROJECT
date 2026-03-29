<?php
require_once __DIR__ . '/../auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /1QCUPROJECT/views/auth/login.php");
    exit;
}

// This is for any pages that requires a log in before 
// accessing and is outside the admin or staff side.
// to use include this at the top of every page:

// require_once __DIR__ . '/../../backend/middleware/requireAdmin.php';
