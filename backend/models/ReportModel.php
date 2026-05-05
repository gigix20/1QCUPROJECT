<?php

class ReportModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // ── REPORT LOG ────────────────────────────────────────────────────────────

    public function saveReport(string $name, string $type, string $url, string $generatedBy = 'Staff', string $role = 'Staff'): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO tbl_reports
                 (report_id, report_name, report_type, generated_by, generated_by_role, generated_at, format, file_url)
             VALUES
                 (report_seq.NEXTVAL, :name, :type, :by, :role, CURRENT_TIMESTAMP, 'PDF', :url)"
        );
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':by',   $generatedBy);
        $stmt->bindValue(':role', $role);
        $stmt->bindValue(':url',  $url);
        $stmt->execute();
        $this->conn->exec('COMMIT');
    }

    public function getRecentReports(string $role = 'Admin'): array
    {
        $roleFilter = ($role === 'Staff') ? " AND generated_by_role = 'Staff'" : '';

        $stmt = $this->conn->prepare(
            "SELECT * FROM (
               SELECT report_id, report_name, report_type, generated_by, generated_by_role,
                      TO_CHAR(generated_at, 'YYYY-MM-DD HH24:MI:SS') AS generated_at,
                      format, file_url
               FROM tbl_reports
               WHERE 1=1{$roleFilter}
               ORDER BY generated_at DESC
             ) WHERE ROWNUM <= 50"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countReportsThisMonth(string $role = 'Admin'): int
    {
        $roleFilter = ($role === 'Staff') ? " AND generated_by_role = 'Staff'" : '';

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS CNT FROM tbl_reports
             WHERE SUBSTR(TO_CHAR(generated_at, 'YYYY-MM-DD'), 1, 7) = :month{$roleFilter}"
        );
        $stmt->bindValue(':month', date('Y-m'));
        $stmt->execute();
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
    }

    public function countAllReports(string $role = 'Admin'): int
    {
        $roleFilter = ($role === 'Staff') ? " AND generated_by_role = 'Staff'" : '';

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS CNT FROM tbl_reports WHERE 1=1{$roleFilter}"
        );
        $stmt->execute();
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
    }

    public function getDepartments(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT department_id, department_name FROM tbl_departments ORDER BY department_name"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private function monthYearClause(string $col, string $month, string $year): string
    {
        if ($year && $month) {
            $ym = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            return " AND SUBSTR({$col},1,7) = '{$ym}'";
        }
        if ($year) return " AND SUBSTR({$col},1,4) = '{$year}'";
        return '';
    }

    // ── FLAT-ROW QUERIES (used by controller outputReport) ────────────────────

    public function getCompleteInventory(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('TO_CHAR(a.created_at,\'YYYY-MM-DD\')', $month, $year);
        $df = $deptId ? ' AND a.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT a.asset_id, a.description, a.serial_number, a.location,
                    a.status, a.is_certified,
                    c.category_name, d.department_name,
                    it.item_type_name,
                    cu.first_name||' '||cu.last_name AS custodian,
                    TO_CHAR(a.created_at,'YYYY-MM-DD') AS created_at
             FROM tbl_assets a
             LEFT JOIN tbl_categories   c  ON c.category_id   = a.category_id
             LEFT JOIN tbl_departments  d  ON d.department_id = a.department_id
             LEFT JOIN tbl_item_types   it ON it.item_type_id = a.item_type_id
             LEFT JOIN tbl_custodians   cu ON cu.custodian_id = a.custodian_id
             WHERE a.is_deleted = 0{$f}{$df}
             ORDER BY d.department_name, a.asset_id"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssetStatusRows(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('TO_CHAR(a.updated_at,\'YYYY-MM-DD\')', $month, $year);
        $df = $deptId ? ' AND a.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT a.asset_id, a.description, d.department_name,
                    a.status,
                    TO_CHAR(a.updated_at,'YYYY-MM-DD HH24:MI') AS updated_at
             FROM tbl_assets a
             LEFT JOIN tbl_departments d ON d.department_id = a.department_id
             WHERE a.is_deleted = 0{$f}{$df}
             ORDER BY d.department_name, a.status"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCertifiedAssetRows(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('TO_CHAR(a.updated_at,\'YYYY-MM-DD\')', $month, $year);
        $df = $deptId ? ' AND a.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT a.asset_id, a.description, d.department_name,
                    c.category_name,
                    cu.first_name||' '||cu.last_name AS custodian,
                    TO_CHAR(a.updated_at,'YYYY-MM-DD') AS certified_date
             FROM tbl_assets a
             LEFT JOIN tbl_departments d  ON d.department_id = a.department_id
             LEFT JOIN tbl_categories  c  ON c.category_id   = a.category_id
             LEFT JOIN tbl_custodians  cu ON cu.custodian_id = a.custodian_id
             WHERE a.is_deleted = 0 AND a.is_certified = 1{$f}{$df}
             ORDER BY d.department_name, a.asset_id"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOverdueItemRows(string $scope = 'all', string $deptId = '', string $month = '', string $year = ''): array
    {
        $f    = $this->monthYearClause('b.due_date', $month, $year);
        $df   = $deptId ? ' AND b.department_id = :dept_id' : '';
        $rows = [];

        if (in_array($scope, ['all', 'borrows'])) {
            $stmt = $this->conn->prepare(
                "SELECT b.borrow_id AS ref_id,
                        b.asset_id,
                        b.first_name||' '||b.last_name AS borrower,
                        d.department_name,
                        b.borrow_date, b.due_date,
                        b.status,
                        'Overdue Borrow' AS type
                 FROM tbl_borrows b
                 LEFT JOIN tbl_departments d ON d.department_id = b.department_id
                 WHERE b.status IN ('Overdue','Borrowed')
                   AND b.due_date < TO_CHAR(SYSDATE,'YYYY-MM-DD'){$f}{$df}
                 ORDER BY d.department_name, b.due_date"
            );
            if ($deptId) $stmt->bindValue(':dept_id', $deptId);
            $stmt->execute();
            $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if (in_array($scope, ['all', 'late'])) {
            $stmt = $this->conn->prepare(
                "SELECT b.borrow_id AS ref_id,
                        b.asset_id,
                        b.first_name||' '||b.last_name AS borrower,
                        d.department_name,
                        b.borrow_date, b.due_date,
                        b.return_date AS status,
                        'Late Return' AS type
                 FROM tbl_borrows b
                 LEFT JOIN tbl_departments d ON d.department_id = b.department_id
                 WHERE b.status = 'Returned'
                   AND b.return_date > b.due_date{$f}{$df}
                 ORDER BY d.department_name, b.due_date"
            );
            if ($deptId) $stmt->bindValue(':dept_id', $deptId);
            $stmt->execute();
            $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        return $rows;
    }

    public function getMaintenanceRows(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('m.scheduled_date', $month, $year);
        $df = $deptId ? ' AND a.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT m.maintenance_id, m.asset_id,
                    mt.type_name,
                    m.issue_description,
                    m.tech_first_name||' '||m.tech_last_name AS technician,
                    d.department_name,
                    m.scheduled_date, m.completed_date,
                    m.status, m.notes
             FROM tbl_maintenance m
             LEFT JOIN tbl_maintenance_types mt ON mt.type_id = m.type_id
             LEFT JOIN tbl_assets a ON a.asset_id = m.asset_id
             LEFT JOIN tbl_departments d ON d.department_id = a.department_id
             WHERE 1=1{$f}{$df}
             ORDER BY d.department_name, m.scheduled_date DESC"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssetsByDepartmentRows(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('TO_CHAR(a.created_at,\'YYYY-MM-DD\')', $month, $year);
        $df = $deptId ? ' AND a.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT d.department_name,
                    a.asset_id, a.description, a.status,
                    c.category_name,
                    it.item_type_name,
                    TO_CHAR(a.created_at,'YYYY-MM-DD') AS created_at
             FROM tbl_assets a
             LEFT JOIN tbl_departments d  ON d.department_id = a.department_id
             LEFT JOIN tbl_categories  c  ON c.category_id   = a.category_id
             LEFT JOIN tbl_item_types  it ON it.item_type_id = a.item_type_id
             WHERE a.is_deleted = 0{$f}{$df}
             ORDER BY d.department_name, a.asset_id"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBorrowingActivityRows(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('b.borrow_date', $month, $year);
        $df = $deptId ? ' AND b.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT * FROM (
               SELECT b.borrow_id, b.asset_id,
                      b.first_name||' '||b.last_name AS borrower,
                      d.department_name,
                      b.borrow_date, b.due_date, b.return_date,
                      b.status, b.purpose
               FROM tbl_borrows b
               JOIN tbl_departments d ON d.department_id = b.department_id
               WHERE 1=1{$f}{$df}
               ORDER BY d.department_name, b.borrow_date DESC
             ) WHERE ROWNUM <= 200"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssetUtilizationRows(string $deptId = '', string $month = '', string $year = ''): array
    {
        $f      = $this->monthYearClause('b.borrow_date', $month, $year);
        $bWhere = $f ? ('WHERE 1=1' . $f) : '';
        $df     = $deptId ? ' AND a.department_id = :dept_id' : '';

        $stmt = $this->conn->prepare(
            "SELECT a.asset_id, a.description,
                    d.department_name,
                    a.status,
                    NVL(bstat.borrow_count, 0) AS borrow_count,
                    NVL(mstat.maint_count,  0) AS maint_count
             FROM tbl_assets a
             LEFT JOIN tbl_departments d ON d.department_id = a.department_id
             LEFT JOIN (
                 SELECT asset_id, COUNT(*) AS borrow_count
                 FROM tbl_borrows {$bWhere} GROUP BY asset_id
             ) bstat ON bstat.asset_id = a.asset_id
             LEFT JOIN (
                 SELECT asset_id, COUNT(*) AS maint_count
                 FROM tbl_maintenance GROUP BY asset_id
             ) mstat ON mstat.asset_id = a.asset_id
             WHERE a.is_deleted = 0{$df}
             ORDER BY d.department_name, borrow_count DESC, a.asset_id"
        );
        if ($deptId) $stmt->bindValue(':dept_id', $deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── SUMMARY METHODS ───────────────────────────────────────────────────────

    public function getAssetBreakdown(string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('a.created_at', $month, $year);
        $s1 = $this->conn->prepare(
            "SELECT c.category_name AS label, COUNT(*) AS total
             FROM tbl_assets a
             LEFT JOIN tbl_categories c ON a.category_id = c.category_id
             WHERE a.is_deleted = 0{$f}
             GROUP BY c.category_name ORDER BY total DESC"
        );
        $s1->execute();

        $s2 = $this->conn->prepare(
            "SELECT t.item_type_name AS label, COUNT(*) AS total
             FROM tbl_assets a
             LEFT JOIN tbl_item_types t ON a.item_type_id = t.item_type_id
             WHERE a.is_deleted = 0{$f}
             GROUP BY t.item_type_name ORDER BY total DESC"
        );
        $s2->execute();

        return [
            'by_category'  => $s1->fetchAll(PDO::FETCH_ASSOC),
            'by_item_type' => $s2->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getAssetStatusSummary(string $month = '', string $year = ''): array
    {
        $f    = $this->monthYearClause('a.created_at', $month, $year);
        $stmt = $this->conn->prepare(
            "SELECT a.status, COUNT(*) AS total
             FROM tbl_assets a
             WHERE a.is_deleted = 0{$f}
             GROUP BY a.status ORDER BY a.status"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOverdueItems(string $scope = 'all', string $month = '', string $year = ''): array
    {
        $bf   = $this->monthYearClause('b.due_date', $month, $year);
        $mf   = $this->monthYearClause('m.scheduled_date', $month, $year);
        $over = [];
        $late = [];
        $maint= [];

        if ($scope === 'all' || $scope === 'late') {
            $stmt = $this->conn->prepare(
                "SELECT b.borrow_id, a.asset_id, a.description AS asset_description,
                        d.department_name, b.first_name, b.middle_name, b.last_name, b.suffix,
                        b.due_date, b.return_date
                 FROM tbl_borrows b
                 JOIN tbl_assets a ON b.asset_id = a.asset_id
                 JOIN tbl_departments d ON b.department_id = d.department_id
                 WHERE b.status = 'Returned'
                   AND TO_DATE(SUBSTR(b.return_date,1,10),'YYYY-MM-DD') > TO_DATE(SUBSTR(b.due_date,1,10),'YYYY-MM-DD'){$bf}
                 ORDER BY b.due_date ASC"
            );
            $stmt->execute();
            $late = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($scope === 'all' || $scope === 'borrows') {
            $stmt = $this->conn->prepare(
                "SELECT b.borrow_id, a.asset_id, a.description AS asset_description,
                        d.department_name, b.first_name, b.middle_name, b.last_name, b.suffix,
                        b.due_date
                 FROM tbl_borrows b
                 JOIN tbl_assets a ON b.asset_id = a.asset_id
                 JOIN tbl_departments d ON b.department_id = d.department_id
                 WHERE b.status IN ('Borrowed','Overdue')
                   AND TO_DATE(SUBSTR(b.due_date,1,10),'YYYY-MM-DD') < TRUNC(SYSDATE){$bf}
                 ORDER BY b.due_date ASC"
            );
            $stmt->execute();
            $over = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($scope === 'all') {
            $stmt = $this->conn->prepare(
                "SELECT m.maintenance_id, a.asset_id, a.description AS asset_description,
                        d.department_name, mt.type_name AS maintenance_type, m.scheduled_date
                 FROM tbl_maintenance m
                 JOIN tbl_assets a ON m.asset_id = a.asset_id
                 JOIN tbl_departments d ON a.department_id = d.department_id
                 JOIN tbl_maintenance_types mt ON m.type_id = mt.type_id
                 WHERE m.status IN ('Pending','In Progress')
                   AND TO_DATE(m.scheduled_date,'YYYY-MM-DD') < TRUNC(SYSDATE){$mf}
                 ORDER BY m.scheduled_date ASC"
            );
            $stmt->execute();
            $maint = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return ['overdue_borrows' => $over, 'late_returns' => $late, 'overdue_maint' => $maint];
    }

    public function getOverdueSummary(string $scope = 'all', string $month = '', string $year = ''): array
    {
        $items = $this->getOverdueItems($scope, $month, $year);
        return [
            'overdue_borrows' => count($items['overdue_borrows']),
            'late_returns'    => count($items['late_returns']),
            'overdue_maint'   => count($items['overdue_maint']),
        ];
    }

    public function getBorrowingActivity(string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('b.borrow_date', $month, $year);
        $s1 = $this->conn->prepare(
            "SELECT b.status, COUNT(*) AS total
             FROM tbl_borrows b WHERE 1=1{$f}
             GROUP BY b.status ORDER BY b.status"
        );
        $s1->execute();

        $s2 = $this->conn->prepare(
            "SELECT * FROM (
               SELECT b.borrow_id, a.asset_id, a.description AS asset_description,
                      d.department_name, b.first_name, b.middle_name, b.last_name, b.suffix,
                      b.borrow_date, b.due_date, b.return_date, b.status
               FROM tbl_borrows b
               JOIN tbl_assets a ON b.asset_id = a.asset_id
               JOIN tbl_departments d ON b.department_id = d.department_id
               WHERE 1=1{$f}
               ORDER BY b.borrow_date DESC
             ) WHERE ROWNUM <= 50"
        );
        $s2->execute();

        return [
            'summary' => $s1->fetchAll(PDO::FETCH_ASSOC),
            'records' => $s2->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getMaintenanceSummary(string $month = '', string $year = ''): array
    {
        $sf = $this->monthYearClause('m.scheduled_date', $month, $year);
        $cf = $this->monthYearClause('m.completed_date', $month, $year);
        $df = ($month || $year)
            ? ' AND (' . ltrim($sf, ' AND ') . ' OR ' . ltrim($cf, ' AND ') . ')'
            : '';

        $s1 = $this->conn->prepare(
            "SELECT m.status, COUNT(*) AS total
             FROM tbl_maintenance m WHERE 1=1{$df}
             GROUP BY m.status ORDER BY m.status"
        );
        $s1->execute();

        $s2 = $this->conn->prepare(
            "SELECT mt.type_name AS maintenance_type,
                    COUNT(*) AS total,
                    SUM(CASE WHEN m.status='Completed'   THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN m.status='Pending'     THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN m.status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN m.status='Cancelled'   THEN 1 ELSE 0 END) AS cancelled
             FROM tbl_maintenance m
             JOIN tbl_maintenance_types mt ON m.type_id = mt.type_id
             WHERE 1=1{$df}
             GROUP BY mt.type_name ORDER BY mt.type_name"
        );
        $s2->execute();

        return [
            'summary' => $s1->fetchAll(PDO::FETCH_ASSOC),
            'by_type' => $s2->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getAssetUtilization(string $month = '', string $year = ''): array
    {
        $f  = $this->monthYearClause('a.created_at', $month, $year);
        $s1 = $this->conn->prepare(
            "SELECT d.department_name,
                    COUNT(*) AS total,
                    SUM(CASE WHEN a.status='In Use'      THEN 1 ELSE 0 END) AS in_use,
                    SUM(CASE WHEN a.status='Available'   THEN 1 ELSE 0 END) AS available,
                    SUM(CASE WHEN a.status='Maintenance' THEN 1 ELSE 0 END) AS maintenance,
                    ROUND(SUM(CASE WHEN a.status='In Use' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*),0), 1) AS utilization_pct
             FROM tbl_assets a
             JOIN tbl_departments d ON a.department_id = d.department_id
             WHERE a.is_deleted = 0{$f}
             GROUP BY d.department_name ORDER BY d.department_name"
        );
        $s1->execute();

        $s2 = $this->conn->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN a.status='In Use'    THEN 1 ELSE 0 END) AS in_use,
                    SUM(CASE WHEN a.status='Available' THEN 1 ELSE 0 END) AS available,
                    ROUND(SUM(CASE WHEN a.status='In Use' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*),0), 1) AS utilization_pct
             FROM tbl_assets a WHERE a.is_deleted = 0{$f}"
        );
        $s2->execute();

        return [
            'rows'    => $s1->fetchAll(PDO::FETCH_ASSOC),
            'overall' => $s2->fetch(PDO::FETCH_ASSOC),
        ];
    }

    // ── SCHEDULED REPORTS ─────────────────────────────────────────────────────

    public function createSchedule(array $data): array
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO tbl_scheduled_reports
                 (schedule_id, schedule_name, report_type, frequency,
                  start_date, next_run_date, run_time, created_by, created_by_role, created_at, is_active)
             VALUES
                 (schedule_seq.NEXTVAL, :name, :type, :freq,
                  :start, :next, :run_time, :by, :role, CURRENT_TIMESTAMP, 1)"
        );
        $stmt->bindValue(':name',     $data['schedule_name']);
        $stmt->bindValue(':type',     $data['report_type']);
        $stmt->bindValue(':freq',     $data['frequency']);
        $stmt->bindValue(':start',    $data['start_date']);
        $stmt->bindValue(':next',     $data['start_date']);
        $stmt->bindValue(':run_time', $data['run_time']      ?? '08:00');
        $stmt->bindValue(':by',       $data['created_by']    ?? 'Staff');
        $stmt->bindValue(':role',     $data['created_by_role'] ?? 'Staff');
        $stmt->execute();
        $this->conn->exec('COMMIT');

        $idRow = $this->conn->query("SELECT schedule_seq.CURRVAL AS id FROM dual")->fetch(PDO::FETCH_ASSOC);
        return ['ok' => true, 'message' => 'Schedule created.', 'id' => (int)($idRow['id'] ?? 0)];
    }

    public function getAllSchedules(string $role = 'Admin'): array
    {
        $roleFilter = ($role === 'Staff') ? " WHERE created_by_role = 'Staff'" : '';

        $stmt = $this->conn->prepare(
            "SELECT schedule_id, schedule_name, report_type, frequency,
                    start_date, next_run_date, run_time, created_by, created_by_role,
                    TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                    is_active
             FROM tbl_scheduled_reports{$roleFilter}
             ORDER BY created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getScheduleById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM tbl_scheduled_reports WHERE schedule_id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function toggleSchedule(int $id, int $newState): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE tbl_scheduled_reports SET is_active = :state WHERE schedule_id = :id"
        );
        $stmt->bindValue(':state', $newState);
        $stmt->bindValue(':id',    $id);
        $stmt->execute();
        $this->conn->exec('COMMIT');
        return $stmt->rowCount() > 0;
    }

    public function deleteSchedule(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM tbl_scheduled_reports WHERE schedule_id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $this->conn->exec('COMMIT');
        return $stmt->rowCount() > 0;
    }

    public function updateNextRunDate(int $id, string $nextDate): array
    {
        $stmt = $this->conn->prepare(
            "UPDATE tbl_scheduled_reports SET next_run_date = :next WHERE schedule_id = :id"
        );
        $stmt->bindValue(':next', $nextDate);
        $stmt->bindValue(':id',   $id);
        $stmt->execute();
        $this->conn->exec('COMMIT');
        return ['ok' => true, 'message' => 'Next run date advanced.', 'next_run_date' => $nextDate];
    }

    public function getDueSchedules(string $role = 'Admin'): array
    {
        $today       = date('Y-m-d');
        $currentTime = date('H:i');
        $roleFilter  = ($role === 'Staff') ? " AND created_by_role = 'Staff'" : '';

        $stmt = $this->conn->prepare(
            "SELECT schedule_id, schedule_name, report_type, frequency,
                    next_run_date, run_time, created_by, created_by_role
             FROM tbl_scheduled_reports
             WHERE is_active = 1 AND next_run_date <= :today{$roleFilter}
             ORDER BY next_run_date ASC, run_time ASC"
        );
        $stmt->bindValue(':today', $today);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_filter($rows, function ($row) use ($today, $currentTime) {
            $nextRun = $row['next_run_date'] ?? $row['NEXT_RUN_DATE'];
            $runTime = $row['run_time']      ?? $row['RUN_TIME'];
            if ($nextRun < $today) return true;
            return $runTime <= $currentTime;
        }));
    }

    public function getAuditLogRows(string $module = '', string $action = '', string $month = '', string $year = ''): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($module) { $where[] = 'module = :module'; $params[':module'] = $module; }
        if ($action) { $where[] = 'action_type = :action'; $params[':action'] = $action; }

        $f = $this->monthYearClause("TO_CHAR(created_at,'YYYY-MM-DD')", $month, $year);
        if ($f) $where[] = ltrim($f, ' AND ');

        $clause = implode(' AND ', $where);

        $stmt = $this->conn->prepare(
            "SELECT * FROM (
               SELECT log_id, action_type, module, performed_by, user_role,
                      description, ip_address, reference_id,
                      TO_CHAR(created_at,'YYYY-MM-DD HH24:MI:SS') AS created_at
               FROM tbl_audit_logs
               WHERE {$clause}
               ORDER BY created_at DESC
             ) WHERE ROWNUM <= 500"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActiveSchedules(string $role = 'Admin'): int
    {
        $roleFilter = ($role === 'Staff') ? " AND created_by_role = 'Staff'" : '';

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS CNT FROM tbl_scheduled_reports WHERE is_active = 1{$roleFilter}"
        );
        $stmt->execute();
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
    }
}