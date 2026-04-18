<?php
// backend/controllers/AuditController.php

require_once __DIR__ . '/../models/AuditModel.php';

class AuditController {
    private AuditModel $model;

    public function __construct($conn) {
        $this->model = new AuditModel($conn);
    }

    public function getStats(): void {
        $this->json(['status' => 'success', 'data' => $this->model->getStats()]);
    }

    public function getLogs(): void {
        $filters = [
            'page'      => $_GET['page']      ?? 1,
            'limit'     => $_GET['limit']     ?? 25,
            'search'    => trim($_GET['search']    ?? ''),
            'module'    => trim($_GET['module']    ?? ''),
            'action'    => trim($_GET['action']    ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to']   ?? ''),
        ];
        $this->json(['status' => 'success', 'data' => $this->model->getLogs($filters)]);
    }

    public function clearLogs(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'POST required'], 405);
            return;
        }
        if (($_SESSION['role'] ?? '') !== 'Admin') {
            $this->json(['status' => 'error', 'message' => 'Admin only'], 403);
            return;
        }
        $this->model->clearAll();
        $this->json(['status' => 'success', 'message' => 'Audit logs cleared.']);
    }

    private function json(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}