<?php
// backend/helpers/audit_helper.php
// Thin wrapper — all models that call logAudit() continue to work unchanged.

require_once __DIR__ . '/../models/AuditModel.php';

function logAudit(PDO $conn, string $actionType, string $module, string $description, string $referenceId = ''): void {
    // Ensure a session is active so $_SESSION vars are available
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    (new AuditModel($conn))->log($actionType, $module, $description, $referenceId);
}