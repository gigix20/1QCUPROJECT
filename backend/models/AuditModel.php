<?php
// backend/models/AuditModel.php

class AuditModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ── INSERT AUDIT LOG ─────────────────────────────────────────────────────────
    // NOTE: log_id is intentionally OMITTED from the INSERT.
    // The Oracle trigger TBL_AUDIT_LOGS_BIR fires BEFORE INSERT and populates
    // log_id via AUDIT_SEQ.NEXTVAL automatically. Passing audit_log_seq.NEXTVAL
    // (wrong name) or any value here would cause an ORA error that was being
    // silently swallowed by the catch block — which is why logs never saved.
    public function log(string $actionType, string $module, string $description, string $referenceId = ''): void {
        try {
            $performedBy = $_SESSION['full_name'] ?? ($_SESSION['email'] ?? 'System');
            $userRole    = $_SESSION['role']       ?? 'Staff';
            $ip          = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $stmt = $this->conn->prepare(
                "INSERT INTO tbl_audit_logs
                   (action_type, module, performed_by, user_role,
                    description, ip_address, reference_id, created_at)
                 VALUES
                   (:action, :module, :by, :role,
                    :desc, :ip, :ref, CURRENT_TIMESTAMP)"
            );
            $stmt->execute([
                ':action' => strtoupper($actionType),
                ':module' => $module,
                ':by'     => $performedBy,
                ':role'   => $userRole,
                ':desc'   => $description,
                ':ip'     => substr($ip, 0, 45),
                ':ref'    => $referenceId,
            ]);
            $this->conn->exec("COMMIT");
        } catch (Throwable $e) {
            // Log to PHP error log so failures are visible during development
            error_log('[AuditModel::log] ' . $e->getMessage());
        }
    }

    // ── STATS ────────────────────────────────────────────────────────────────────
    public function getStats(): array {
        // Oracle returns column aliases in UPPERCASE when fetched as assoc.
        // Using fetchColumn(0) avoids any alias-case issues entirely.

        $today = (int) $this->conn->query(
            "SELECT COUNT(*) FROM tbl_audit_logs WHERE TRUNC(created_at) = TRUNC(SYSDATE)"
        )->fetchColumn(0);

        $month = (int) $this->conn->query(
            "SELECT COUNT(*) FROM tbl_audit_logs
             WHERE EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM SYSDATE)
               AND EXTRACT(YEAR  FROM created_at) = EXTRACT(YEAR  FROM SYSDATE)"
        )->fetchColumn(0);

        $total = (int) $this->conn->query(
            "SELECT COUNT(*) FROM tbl_audit_logs"
        )->fetchColumn(0);

        $critical = (int) $this->conn->query(
            "SELECT COUNT(*) FROM tbl_audit_logs
             WHERE action_type IN ('ASSET_DELETE','LOGIN_FAIL')"
        )->fetchColumn(0);

        return compact('today', 'month', 'total', 'critical');
    }

    // ── GET PAGINATED / FILTERED LOGS ────────────────────────────────────────────
    public function getLogs(array $filters): array {
        $page   = max(1, (int) ($filters['page']  ?? 1));
        $limit  = min(100, max(5, (int) ($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(UPPER(description)  LIKE UPPER(:s1)
                      OR UPPER(performed_by) LIKE UPPER(:s2)
                      OR UPPER(reference_id) LIKE UPPER(:s3))";
            $params[':s1'] = $params[':s2'] = $params[':s3'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['module'])) {
            $where[]         = 'module = :module';
            $params[':module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $where[]         = 'action_type = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[]          = "TRUNC(created_at) >= TO_DATE(:dfrom, 'YYYY-MM-DD')";
            $params[':dfrom'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]         = "TRUNC(created_at) <= TO_DATE(:dto, 'YYYY-MM-DD')";
            $params[':dto']  = $filters['date_to'];
        }

        $clause = implode(' AND ', $where);

        // COUNT — fetchColumn(0) is safe regardless of Oracle alias case
        $cntStmt = $this->conn->prepare("SELECT COUNT(*) FROM tbl_audit_logs WHERE $clause");
        $cntStmt->execute($params);
        $total = (int) $cntStmt->fetchColumn(0);

        // DATA — Oracle pagination using ROWNUM
        $dataStmt = $this->conn->prepare(
            "SELECT * FROM (
               SELECT a.*, ROWNUM AS rn FROM (
                 SELECT log_id, action_type, module, performed_by, user_role,
                        description, ip_address, reference_id,
                        TO_CHAR(created_at,'YYYY-MM-DD HH24:MI:SS') AS created_at
                 FROM tbl_audit_logs
                 WHERE $clause
                 ORDER BY created_at DESC
               ) a WHERE ROWNUM <= :maxrow
             ) WHERE rn > :minrow"
        );
        $dataStmt->execute(array_merge($params, [
            ':maxrow' => $offset + $limit,
            ':minrow' => $offset,
        ]));
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int) ceil($total / max(1, $limit)),
        ];
    }

    // ── CLEAR ALL ────────────────────────────────────────────────────────────────
    public function clearAll(): void {
        $this->conn->exec("DELETE FROM tbl_audit_logs");
        $this->conn->exec("COMMIT");
    }
}