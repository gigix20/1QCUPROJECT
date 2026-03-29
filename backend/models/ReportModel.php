<?php

class ReportModel {

  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // ─── REPORTS ────────────────────────────────────────────────────────────────

  public function saveReport($name, $type, $url) {
    $sql = "INSERT INTO tbl_reports (report_id, report_name, report_type, generated_by, generated_at, format, file_url)
            VALUES (report_seq.NEXTVAL, :name, :type, 'Staff', CURRENT_TIMESTAMP, 'PDF', :url)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':type', $type);
    $stmt->bindValue(':url',  $url);
    $stmt->execute();
    $this->conn->exec("COMMIT");
  }

  public function countAllReports(): int {
    $stmt = $this->conn->prepare("SELECT COUNT(*) AS CNT FROM tbl_reports");
    $stmt->execute();
    return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
  }

  public function countReportsThisMonth(): int {
    $stmt = $this->conn->prepare(
      "SELECT COUNT(*) AS CNT FROM tbl_reports
       WHERE SUBSTR(TO_CHAR(generated_at, 'YYYY-MM-DD'), 1, 7) = :month"
    );
    $stmt->bindValue(':month', date('Y-m'));
    $stmt->execute();
    return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
  }

  public function getRecentReports() {
    $stmt = $this->conn->prepare(
      "SELECT * FROM (
         SELECT report_id, report_name, report_type, generated_by,
                TO_CHAR(generated_at, 'YYYY-MM-DD HH24:MI:SS') AS generated_at,
                format, file_url
         FROM tbl_reports
         ORDER BY generated_at DESC
       ) WHERE ROWNUM <= 10"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getDepartments() {
    $stmt = $this->conn->prepare(
      "SELECT department_id, department_name FROM tbl_departments ORDER BY department_name"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // ─── HELPERS ────────────────────────────────────────────────────────────────

  private function monthYearClause($col, $month, $year) {
    if ($year && $month) {
      $ym = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
      return " AND SUBSTR({$col},1,7) = '{$ym}'";
    }
    if ($year) return " AND SUBSTR({$col},1,4) = '{$year}'";
    return '';
  }

  // ─── REPORT QUERIES ─────────────────────────────────────────────────────────

  public function getAssetBreakdown($month = '', $year = '') {
    $f = $this->monthYearClause('a.created_at', $month, $year);

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

  public function getAssetStatusSummary($month = '', $year = '') {
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

  public function getAssetsByDepartment($deptId = '', $month = '', $year = '') {
    $f  = $this->monthYearClause('a.created_at', $month, $year);
    $df = $deptId ? " AND a.department_id = :dept_id" : '';
    $stmt = $this->conn->prepare(
      "SELECT d.department_name,
              COUNT(*) AS total,
              SUM(CASE WHEN a.status='Available'   THEN 1 ELSE 0 END) AS available,
              SUM(CASE WHEN a.status='In Use'      THEN 1 ELSE 0 END) AS in_use,
              SUM(CASE WHEN a.status='Maintenance' THEN 1 ELSE 0 END) AS maintenance
       FROM tbl_assets a
       JOIN tbl_departments d ON a.department_id = d.department_id
       WHERE a.is_deleted = 0{$f}{$df}
       GROUP BY d.department_name ORDER BY d.department_name"
    );
    if ($deptId) $stmt->bindValue(':dept_id', $deptId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getCertifiedAssets($deptId = '', $month = '', $year = '') {
    $f  = $this->monthYearClause('a.created_at', $month, $year);
    $df = $deptId ? " AND a.department_id = :dept_id" : '';
    $stmt = $this->conn->prepare(
      "SELECT d.department_name,
              COUNT(*) AS total,
              SUM(CASE WHEN a.is_certified=1 THEN 1 ELSE 0 END) AS certified,
              SUM(CASE WHEN a.is_certified=0 THEN 1 ELSE 0 END) AS not_certified
       FROM tbl_assets a
       JOIN tbl_departments d ON a.department_id = d.department_id
       WHERE a.is_deleted = 0{$f}{$df}
       GROUP BY d.department_name ORDER BY d.department_name"
    );
    if ($deptId) $stmt->bindValue(':dept_id', $deptId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getOverdueItems($scope = 'all', $month = '', $year = '') {
    $bf = $this->monthYearClause('b.due_date', $month, $year);
    $mf = $this->monthYearClause('m.scheduled_date', $month, $year);

    $overdueBorrows = [];
    $lateReturns    = [];
    $overdueMaint   = [];

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
      $lateReturns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($scope === 'all' || $scope === 'borrows') {
      $stmt = $this->conn->prepare(
        "SELECT b.borrow_id, a.asset_id, a.description AS asset_description,
                d.department_name, b.first_name, b.middle_name, b.last_name, b.suffix,
                b.due_date
         FROM tbl_borrows b
         JOIN tbl_assets a ON b.asset_id = a.asset_id
         JOIN tbl_departments d ON b.department_id = d.department_id
         WHERE b.status IN ('Borrowed', 'Overdue')
           AND TO_DATE(SUBSTR(b.due_date,1,10),'YYYY-MM-DD') < TRUNC(SYSDATE){$bf}
         ORDER BY b.due_date ASC"
      );
      $stmt->execute();
      $overdueBorrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      $overdueMaint = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return [
      'overdue_borrows' => $overdueBorrows,
      'late_returns'    => $lateReturns,
      'overdue_maint'   => $overdueMaint,
    ];
  }

  public function getOverdueSummary($scope = 'all', $month = '', $year = '') {
    $items = $this->getOverdueItems($scope, $month, $year);
    return [
      'overdue_borrows' => count($items['overdue_borrows']),
      'late_returns'    => count($items['late_returns']),
      'overdue_maint'   => count($items['overdue_maint']),
    ];
  }

  public function getBorrowingActivity($month = '', $year = '') {
    $f = $this->monthYearClause('b.borrow_date', $month, $year);

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

  public function getAssetUtilization($month = '', $year = '') {
    $f = $this->monthYearClause('a.created_at', $month, $year);

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

  public function getMaintenanceSummary($month = '', $year = '') {
    $sf = $this->monthYearClause('m.scheduled_date', $month, $year);
    $cf = $this->monthYearClause('m.completed_date',  $month, $year);
    $df = ($month || $year)
      ? " AND (" . ltrim($sf, ' AND ') . " OR " . ltrim($cf, ' AND ') . ")"
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

  // ─── SCHEDULED REPORTS ──────────────────────────────────────────────────────

  public function createSchedule(array $data): array {
    $stmt = $this->conn->prepare(
      "INSERT INTO tbl_scheduled_reports
           (schedule_id, schedule_name, report_type, frequency,
            start_date, next_run_date, run_time, created_by, created_at, is_active)
       VALUES
           (schedule_seq.NEXTVAL, :name, :type, :freq,
            :start, :next, :run_time, :by, CURRENT_TIMESTAMP, 1)"
    );
    $stmt->bindValue(':name',     $data['schedule_name']);
    $stmt->bindValue(':type',     $data['report_type']);
    $stmt->bindValue(':freq',     $data['frequency']);
    $stmt->bindValue(':start',    $data['start_date']);
    $stmt->bindValue(':next',     $data['start_date']);
    $stmt->bindValue(':run_time', $data['run_time']    ?? '08:00');
    $stmt->bindValue(':by',       $data['created_by']  ?? 'Staff');
    $stmt->execute();
    $this->conn->exec("COMMIT");

    $idRow = $this->conn->query("SELECT schedule_seq.CURRVAL AS id FROM dual")->fetch(PDO::FETCH_ASSOC);
    return ['ok' => true, 'message' => 'Schedule created.', 'id' => (int) ($idRow['id'] ?? 0)];
  }

  public function getAllSchedules(): array {
    $stmt = $this->conn->prepare(
      "SELECT schedule_id, schedule_name, report_type, frequency,
              start_date, next_run_date, run_time, created_by,
              TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
              is_active
       FROM tbl_scheduled_reports ORDER BY created_at DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getScheduleById(int $id): ?array {
    $stmt = $this->conn->prepare("SELECT * FROM tbl_scheduled_reports WHERE schedule_id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  public function toggleSchedule(int $id, int $newState): bool {
    $stmt = $this->conn->prepare(
      "UPDATE tbl_scheduled_reports SET is_active = :state WHERE schedule_id = :id"
    );
    $stmt->bindValue(':state', $newState);
    $stmt->bindValue(':id',    $id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    return $stmt->rowCount() > 0;
  }

  public function deleteSchedule(int $id): bool {
    $stmt = $this->conn->prepare("DELETE FROM tbl_scheduled_reports WHERE schedule_id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    return $stmt->rowCount() > 0;
  }

  public function updateNextRunDate(int $id, string $nextDate): array {
    $stmt = $this->conn->prepare(
      "UPDATE tbl_scheduled_reports SET next_run_date = :next WHERE schedule_id = :id"
    );
    $stmt->bindValue(':next', $nextDate);
    $stmt->bindValue(':id',   $id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    return ['ok' => true, 'message' => 'Next run date advanced.', 'next_run_date' => $nextDate];
  }

  public function getDueSchedules(): array {
    $today       = date('Y-m-d');
    $currentTime = date('H:i');

    $stmt = $this->conn->prepare(
      "SELECT schedule_id, schedule_name, report_type, frequency,
              next_run_date, run_time, created_by
       FROM tbl_scheduled_reports
       WHERE is_active = 1 AND next_run_date <= :today
       ORDER BY next_run_date ASC, run_time ASC"
    );
    $stmt->bindValue(':today', $today);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_values(array_filter($rows, function($row) use ($today, $currentTime) {
      $nextRun = $row['next_run_date'] ?? $row['NEXT_RUN_DATE'];
      $runTime = $row['run_time']      ?? $row['RUN_TIME'];
      if ($nextRun < $today) return true;
      return $runTime <= $currentTime;
    }));
  }

  public function countActiveSchedules(): int {
    $stmt = $this->conn->prepare(
      "SELECT COUNT(*) AS CNT FROM tbl_scheduled_reports WHERE is_active = 1"
    );
    $stmt->execute();
    return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
  }
}