<?php
require_once __DIR__ . '/../auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required'
    ]);
    exit;
}