<?php
// backend/routes/users_route.php
// Lightweight endpoint — currently serves only the user count for the admin dashboard.
// Extend the switch below as user management features are added.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

$action = $_GET['action'] ?? '';

switch ($action) {

  case 'count':
    // Count all registered users regardless of role or verification status
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users");
    $stmt->execute();
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = (int) ($row['TOTAL'] ?? $row['total'] ?? 0);
    ResponseHelper::sendSuccess(['count' => $count]);
    break;

  default:
    ResponseHelper::sendError(400, 'Invalid action.');
}
?>