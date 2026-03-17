<?php

class ReportModel {

  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // SAVE REPORT TO DB
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

  // GET RECENT REPORTS (latest 10)
  public function getRecentReports() {
    $sql = "SELECT * FROM (
              SELECT report_id, report_name, report_type, generated_by,
                     TO_CHAR(generated_at, 'YYYY-MM-DD HH24:MI:SS') AS generated_at,
                     format, file_url
              FROM tbl_reports
              ORDER BY generated_at DESC
            ) WHERE ROWNUM <= 10";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // GET DEPARTMENTS
  public function getDepartments() {
    $sql = "SELECT department_id, department_name FROM tbl_departments ORDER BY department_name";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }


  // Helper: build month/year WHERE clause
  // $col   = column name (e.g. 'a.created_at')
  // $month = '03' or '' if not filtering
  // $year  = '2025' or '' if not filtering
  // Returns string like " AND SUBSTR(a.created_at,1,7)='2025-03'"
  private function monthYearClause($col, $month, $year) {
    if ($year && $month) {
      $ym = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
      return " AND SUBSTR({$col},1,7) = '{$ym}'";
    }
    if ($year) {
      return " AND SUBSTR({$col},1,4) = '{$year}'";
    }
    return '';
  }

  // REPORT 1 & 3: ASSET STATUS SUMMARY
  // Filters tbl_assets.created_at
  public function getAssetStatusSummary($month = '', $year = '') {
    $filter = $this->monthYearClause('a.created_at', $month, $year);
    $sql = "SELECT a.status, COUNT(*) AS total
            FROM tbl_assets a
            WHERE a.is_deleted = 0{$filter}
            GROUP BY a.status
            ORDER BY a.status";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // REPORT 2: CERTIFIED ASSETS
  public function getCertifiedAssets($deptId = '', $month = '', $year = '') {
    $filter = $this->monthYearClause('a.created_at', $month, $year);
    $deptFilter = $deptId ? " AND a.department_id = :dept_id" : '';

    $sql = "SELECT d.department_name,
                   COUNT(*) AS total,
                   SUM(CASE WHEN a.is_certified=1 THEN 1 ELSE 0 END) AS certified,
                   SUM(CASE WHEN a.is_certified=0 THEN 1 ELSE 0 END) AS not_certified
            FROM tbl_assets a
            JOIN tbl_departments d ON a.department_id = d.department_id
            WHERE a.is_deleted = 0{$filter}{$deptFilter}
            GROUP BY d.department_name
            ORDER BY d.department_name";
    $stmt = $this->conn->prepare($sql);
    if ($deptId) $stmt->bindValue(':dept_id', $deptId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // REPORT 3: OVERDUE ITEMS
  // Filters: due_date (borrows) / scheduled_date (maintenance)
  public function getOverdueItems($scope = 'all', $month = '', $year = '') {
    $borrowFilter = $this->monthYearClause('b.due_date', $month, $year);
    $maintFilter  = $this->monthYearClause('m.scheduled_date', $month, $year);

    $overdueBorrows = [];
    $lateReturns    = [];
    $overdueMaint   = [];

    // Overdue borrows
    if ($scope === 'all' || $scope === 'borrows') {
      $sql = "SELECT b.borrow_id, a.asset_id, a.description AS asset_description,
                     d.department_name,
                     b.first_name, b.middle_name, b.last_name, b.suffix,
                     b.due_date
              FROM tbl_borrows b
              JOIN tbl_assets a ON b.asset_id = a.id
              JOIN tbl_departments d ON b.department_id = d.department_id
              WHERE b.status IN ('Borrowed','Overdue')
                AND TO_DATE(b.due_date,'YYYY-MM-DD') < TRUNC(SYSDATE){$borrowFilter}
              ORDER BY b.due_date ASC";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();
      $overdueBorrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Late returns
    if ($scope === 'all' || $scope === 'late') {
      $sql = "SELECT b.borrow_id, a.asset_id, a.description AS asset_description,
                     b.first_name, b.middle_name, b.last_name, b.suffix,
                     b.due_date, b.return_date
              FROM tbl_borrows b
              JOIN tbl_assets a ON b.asset_id = a.id
              WHERE b.status = 'Returned'
                AND TO_DATE(b.return_date,'YYYY-MM-DD') > TO_DATE(b.due_date,'YYYY-MM-DD'){$borrowFilter}
              ORDER BY b.due_date ASC";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();
      $lateReturns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Overdue maintenance
    if ($scope === 'all') {
      $sql = "SELECT m.maintenance_id, a.asset_id, a.description AS asset_description,
                     d.department_name, mt.type_name AS maintenance_type, m.scheduled_date
              FROM tbl_maintenance m
              JOIN tbl_assets a ON m.asset_id = a.id
              JOIN tbl_departments d ON a.department_id = d.department_id
              JOIN tbl_maintenance_types mt ON m.type_id = mt.type_id
              WHERE m.status IN ('Pending','In Progress')
                AND TO_DATE(m.scheduled_date,'YYYY-MM-DD') < TRUNC(SYSDATE){$maintFilter}
              ORDER BY m.scheduled_date ASC";
      $stmt = $this->conn->prepare($sql);
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

  // MAINTENANCE REPORT
  // Filters by scheduled_date OR completed_date
  public function getMaintenanceSummary($month = '', $year = '') {
    // Build OR filter on both date columns
    $schedFilter = $this->monthYearClause('m.scheduled_date', $month, $year);
    $compFilter  = $this->monthYearClause('m.completed_date',  $month, $year);

    // Combine: if a filter exists, wrap as OR; otherwise no filter
    if ($month || $year) {
      $dateFilter = " AND (" . ltrim($schedFilter, ' AND ') . " OR " . ltrim($compFilter, ' AND ') . ")";
    } else {
      $dateFilter = '';
    }

    // Status summary
    $sql = "SELECT m.status, COUNT(*) AS total
            FROM tbl_maintenance m
            WHERE 1=1{$dateFilter}
            GROUP BY m.status
            ORDER BY m.status";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Per-type breakdown
    $sql2 = "SELECT mt.type_name AS maintenance_type,
                    COUNT(*) AS total,
                    SUM(CASE WHEN m.status='Completed'   THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN m.status='Pending'     THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN m.status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN m.status='Cancelled'   THEN 1 ELSE 0 END) AS cancelled
             FROM tbl_maintenance m
             JOIN tbl_maintenance_types mt ON m.type_id = mt.type_id
             WHERE 1=1{$dateFilter}
             GROUP BY mt.type_name
             ORDER BY mt.type_name";
    $stmt2 = $this->conn->prepare($sql2);
    $stmt2->execute();
    $byType = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    return ['summary' => $summary, 'by_type' => $byType];
  }

  //SCHEDULED REPORTS
public function createSchedule(array $data): array {
    $sql = "INSERT INTO tbl_scheduled_reports
                (schedule_id, schedule_name, report_type, frequency,
                 start_date, next_run_date, run_time, created_by, created_at, is_active)
            VALUES
                (schedule_seq.NEXTVAL, :name, :type, :freq,
                 :start, :next,
                 :run_time, :by, CURRENT_TIMESTAMP, 1)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':name',     $data['schedule_name']);
    $stmt->bindValue(':type',     $data['report_type']);
    $stmt->bindValue(':freq',     $data['frequency']);
    $stmt->bindValue(':start',    $data['start_date']);
    $stmt->bindValue(':next',     $data['start_date']);
    $stmt->bindValue(':run_time', $data['run_time'] ?? '08:00');
    $stmt->bindValue(':by',       $data['created_by'] ?? 'Staff');
    $stmt->execute();
    $this->conn->exec("COMMIT");

    $idStmt = $this->conn->query("SELECT schedule_seq.CURRVAL AS id FROM dual");
    $idRow  = $idStmt->fetch(PDO::FETCH_ASSOC);

    return ['ok' => true, 'message' => 'Schedule created.', 'id' => (int) ($idRow['id'] ?? 0)];
}

public function getAllSchedules(): array {
    $sql = "SELECT schedule_id, schedule_name, report_type, frequency,
                   start_date,       
                   next_run_date,   
                   run_time,
                   created_by,
                   TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                   is_active
            FROM tbl_scheduled_reports
            ORDER BY created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getScheduleById(int $id): ?array {
    $sql  = "SELECT * FROM tbl_scheduled_reports WHERE schedule_id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function toggleSchedule(int $id, int $newState): bool {
    $sql  = "UPDATE tbl_scheduled_reports SET is_active = :state WHERE schedule_id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':state', $newState);
    $stmt->bindValue(':id',    $id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    return $stmt->rowCount() > 0;
}

public function deleteSchedule(int $id): bool {
    $sql  = "DELETE FROM tbl_scheduled_reports WHERE schedule_id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    return $stmt->rowCount() > 0;
}

public function updateNextRunDate(int $id, string $nextDate): array {
    $sql  = "UPDATE tbl_scheduled_reports SET next_run_date = :next WHERE schedule_id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':next', $nextDate);
    $stmt->bindValue(':id',   $id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    return ['ok' => true, 'message' => 'Next run date advanced.', 'next_run_date' => $nextDate];
}

public function getDueSchedules(): array {
    // Get today's date and current hour:minute
    $today       = date('Y-m-d');
    $currentTime = date('H:i'); // e.g. '14:35'

    $sql = "SELECT schedule_id, schedule_name, report_type, frequency,
                   next_run_date, run_time, created_by
            FROM tbl_scheduled_reports
            WHERE is_active = 1
              AND next_run_date <= :today
            ORDER BY next_run_date ASC, run_time ASC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':today', $today);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter in PHP — only return rows where run_time has passed
    return array_values(array_filter($rows, function($row) use ($today, $currentTime) {
        $nextRun = $row['next_run_date'] ?? $row['NEXT_RUN_DATE'];
        $runTime = $row['run_time']      ?? $row['RUN_TIME'];

        // If next_run_date is in the past — always due regardless of time
        if ($nextRun < $today) return true;

        // If next_run_date is today — only due if run_time has passed
        return $runTime <= $currentTime;
    }));
}

public function countActiveSchedules(): int {
    $sql  = "SELECT COUNT(*) AS CNT FROM tbl_scheduled_reports WHERE is_active = 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
}
}
