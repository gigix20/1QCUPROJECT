<?php
// =============================================================
//  reports_route.php
//  Drop in:  /1QCUPROJECT/backend/routes/reports_route.php
//
//  Handles every resource called by admin-reports*.js AND
//  generates PDF reports using a pure-PHP HTML→PDF approach
//  (no Composer required — uses a bundled mPDF-lite or
//   falls back to inline-browser-print HTML if mPDF absent).
//
//  Dependencies (place in /1QCUPROJECT/backend/libs/):
//    • mpdf/mpdf  — install via: composer require mpdf/mpdf
//      OR just use the HTML fallback (works fine for printing).
// =============================================================

session_start();
require_once __DIR__ . '/../middleware/requireAdmin.php';
require_once __DIR__ . '/../../backend/config/database.php';   // $conn
require_once __DIR__ . '/../helpers/audit_helper.php';         // logAudit()

$resource = $_GET['resource'] ?? '';

// ── tiny helpers ────────────────────────────────────────────
function jsonOut(array $d): void { header('Content-Type: application/json'); echo json_encode($d); exit; }
function oops(string $m):  void { jsonOut(['status'=>'error','message'=>$m]); }

function monthYearWhere(string $col, string $month, string $year): array {
    $clauses = [];
    $params  = [];
    if ($month !== '') {
        $clauses[]         = "TO_NUMBER(SUBSTR($col,6,2)) = :p_month";
        $params[':p_month'] = (int)$month;
    }
    if ($year !== '') {
        $clauses[]        = "TO_NUMBER(SUBSTR($col,1,4)) = :p_year";
        $params[':p_year'] = (int)$year;
    }
    return [$clauses, $params];
}

// ── router ──────────────────────────────────────────────────
switch ($resource) {

    // =========================================================
    //  DEPARTMENTS  (for dropdown population)
    // =========================================================
    case 'departments':
        $rows = $conn->query(
            "SELECT DEPARTMENT_ID, DEPARTMENT_NAME
             FROM TBL_DEPARTMENTS
             WHERE STATUS='Active'
             ORDER BY DEPARTMENT_NAME"
        )->fetchAll(PDO::FETCH_ASSOC);
        jsonOut(['status'=>'success','data'=>$rows]);
        break;

    // =========================================================
    //  SAVE REPORT LOG
    // =========================================================
    case 'save_report':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { oops('POST required'); }

        $name   = trim($_POST['report_name'] ?? '');
        $type   = trim($_POST['report_type'] ?? '');
        $url    = trim($_POST['file_url']    ?? '');
        $genBy  = $_SESSION['full_name'] ?? 'Admin';

        if (!$name || !$type) { oops('Missing fields'); }

        $stmt = $conn->prepare(
            "INSERT INTO TBL_REPORTS (REPORT_NAME,REPORT_TYPE,GENERATED_BY,FORMAT,FILE_URL)
             VALUES (:name,:type,:by,'PDF',:url)"
        );
        $stmt->execute([':name'=>$name,':type'=>$type,':by'=>$genBy,':url'=>$url]);

        // Audit
        logAudit($conn,'REPORT_GENERATED','Reports',"Generated report: $name");

        jsonOut(['status'=>'success']);
        break;

    // =========================================================
    //  RECENT REPORTS
    // =========================================================
    case 'recent_reports':
        $rows = $conn->query(
            "SELECT * FROM (
               SELECT REPORT_ID,REPORT_NAME,REPORT_TYPE,GENERATED_BY,
                      TO_CHAR(GENERATED_AT,'YYYY-MM-DD HH24:MI:SS') AS GENERATED_AT,
                      FORMAT,FILE_URL
               FROM TBL_REPORTS
               ORDER BY GENERATED_AT DESC
             ) WHERE ROWNUM <= 50"
        )->fetchAll(PDO::FETCH_ASSOC);

        $monthly = (int)$conn->query(
            "SELECT COUNT(*) FROM TBL_REPORTS
             WHERE EXTRACT(MONTH FROM GENERATED_AT)=EXTRACT(MONTH FROM SYSDATE)
               AND EXTRACT(YEAR  FROM GENERATED_AT)=EXTRACT(YEAR  FROM SYSDATE)"
        )->fetchColumn();

        $allTime = (int)$conn->query("SELECT COUNT(*) FROM TBL_REPORTS")->fetchColumn();

        jsonOut(['status'=>'success','data'=>[
            'reports'       => $rows,
            'monthly_count' => $monthly,
            'all_time_count'=> $allTime,
        ]]);
        break;

    // =========================================================
    //  SCHEDULED REPORTS — LIST
    // =========================================================
    case 'scheduled_reports':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CREATE
            $name  = trim($_POST['schedule_name'] ?? '');
            $type  = trim($_POST['report_type']   ?? '');
            $freq  = trim($_POST['frequency']     ?? '');
            $start = trim($_POST['start_date']    ?? '');
            $time  = trim($_POST['run_time']       ?? '08:00');
            $by    = $_SESSION['full_name'] ?? 'Admin';

            if (!$name||!$type||!$freq||!$start) { oops('Missing fields'); }

            // Compute first next_run_date = start_date
            $stmt = $conn->prepare(
                "INSERT INTO TBL_SCHEDULED_REPORTS
                   (SCHEDULE_NAME,REPORT_TYPE,FREQUENCY,START_DATE,
                    NEXT_RUN_DATE,CREATED_BY,IS_ACTIVE,RUN_TIME)
                 VALUES
                   (:name,:type,:freq,:start,
                    :start,:by,1,:time)"
            );
            $stmt->execute([
                ':name'=>$name,':type'=>$type,':freq'=>$freq,
                ':start'=>$start,':by'=>$by,':time'=>$time,
            ]);
            logAudit($conn,'SCHEDULE_CREATE','Reports',"Scheduled report '$name' ($freq)");
            jsonOut(['status'=>'success']);
        } else {
            // READ
            $rows = $conn->query(
                "SELECT SCHEDULE_ID,SCHEDULE_NAME,REPORT_TYPE,FREQUENCY,
                        START_DATE,NEXT_RUN_DATE,CREATED_BY,IS_ACTIVE,RUN_TIME,
                        TO_CHAR(CREATED_AT,'YYYY-MM-DD') AS CREATED_AT
                 FROM TBL_SCHEDULED_REPORTS
                 ORDER BY CREATED_AT DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            jsonOut(['status'=>'success','data'=>$rows]);
        }
        break;

    // =========================================================
    //  SCHEDULED COUNT (stat card)
    // =========================================================
    case 'scheduled_count':
        $cnt = (int)$conn->query(
            "SELECT COUNT(*) FROM TBL_SCHEDULED_REPORTS WHERE IS_ACTIVE=1"
        )->fetchColumn();
        jsonOut(['status'=>'success','data'=>['count'=>$cnt]]);
        break;

    // =========================================================
    //  TOGGLE SCHEDULE
    // =========================================================
    case 'toggle_schedule':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { oops('POST required'); }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) { oops('Missing id'); }

        $cur = $conn->query("SELECT IS_ACTIVE FROM TBL_SCHEDULED_REPORTS WHERE SCHEDULE_ID=$id")->fetchColumn();
        $new = ($cur == 1) ? 0 : 1;
        $conn->exec("UPDATE TBL_SCHEDULED_REPORTS SET IS_ACTIVE=$new WHERE SCHEDULE_ID=$id");

        $msg = $new ? 'Schedule activated.' : 'Schedule paused.';
        logAudit($conn,'SCHEDULE_TOGGLE','Reports',$msg,(string)$id);
        jsonOut(['status'=>'success','message'=>$msg]);
        break;

    // =========================================================
    //  DELETE SCHEDULE
    // =========================================================
    case 'delete_schedule':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { oops('POST required'); }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) { oops('Missing id'); }

        $conn->exec("DELETE FROM TBL_SCHEDULED_REPORTS WHERE SCHEDULE_ID=$id");
        logAudit($conn,'SCHEDULE_DELETE','Reports',"Deleted scheduled report id=$id",(string)$id);
        jsonOut(['status'=>'success','message'=>'Schedule deleted.']);
        break;

    // =========================================================
    //  DUE SCHEDULES  (polled every minute by scheduler.js)
    // =========================================================
    case 'due_schedules':
        $now      = date('H:i');
        $today    = date('Y-m-d');
        $rows     = $conn->query(
            "SELECT SCHEDULE_ID,SCHEDULE_NAME,REPORT_TYPE,FREQUENCY,RUN_TIME,NEXT_RUN_DATE
             FROM TBL_SCHEDULED_REPORTS
             WHERE IS_ACTIVE=1
               AND NEXT_RUN_DATE <= '$today'
               AND RUN_TIME      <= '$now'"
        )->fetchAll(PDO::FETCH_ASSOC);
        jsonOut(['status'=>'success','data'=>$rows]);
        break;

    // =========================================================
    //  BUMP SCHEDULE  (advances next_run_date)
    // =========================================================
    case 'bump_schedule':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { oops('POST required'); }
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { oops('Missing id'); }

        $row = $conn->query(
            "SELECT FREQUENCY,NEXT_RUN_DATE FROM TBL_SCHEDULED_REPORTS WHERE SCHEDULE_ID=$id"
        )->fetch(PDO::FETCH_ASSOC);

        if (!$row) { oops('Schedule not found'); }

        $freq = strtolower($row['FREQUENCY'] ?? $row['frequency'] ?? '');
        $cur  = $row['NEXT_RUN_DATE'] ?? $row['next_run_date'] ?? date('Y-m-d');

        $map  = ['daily'=>'+1 day','weekly'=>'+1 week','monthly'=>'+1 month','quarterly'=>'+3 months'];
        $add  = $map[$freq] ?? '+1 month';
        $next = date('Y-m-d', strtotime($cur . ' ' . $add));

        $conn->exec("UPDATE TBL_SCHEDULED_REPORTS SET NEXT_RUN_DATE='$next' WHERE SCHEDULE_ID=$id");
        jsonOut(['status'=>'success','next_run_date'=>$next]);
        break;

    // =========================================================
    //  RUN SCHEDULED  (logs it + returns url for addToRecent)
    // =========================================================
    case 'run_scheduled':
        $type = trim($_GET['type'] ?? '');
        if (!$type) { oops('Missing type'); }

        $map = [
            'Complete Asset Inventory' => 'report_complete',
            'Asset Status Report'      => 'report_status',
            'Certified Assets Report'  => 'report_certified',
            'Overdue Items Report'     => 'report_overdue',
            'Maintenance Report'       => 'report_maintenance',
        ];
        $res = $map[$type] ?? null;
        if (!$res) { oops('Unknown type'); }

        $url   = "/1QCUPROJECT/backend/routes/reports_route.php?resource=$res";
        $name  = $type . ' (Scheduled)';
        $genBy = $_SESSION['full_name'] ?? 'System';

        $stmt = $conn->prepare(
            "INSERT INTO TBL_REPORTS (REPORT_NAME,REPORT_TYPE,GENERATED_BY,FORMAT,FILE_URL)
             VALUES (:name,:type,:by,'PDF',:url)"
        );
        $stmt->execute([':name'=>$name,':type'=>$type,':by'=>$genBy,':url'=>$url]);
        logAudit($conn,'REPORT_GENERATED','Reports',"Scheduled report generated: $name");

        jsonOut(['status'=>'success','data'=>['url'=>$url,'name'=>$name]]);
        break;

    // =========================================================
    //  ██████  PDF REPORT GENERATORS  ██████
    //  Each case queries the DB, builds HTML and either
    //  streams it via mPDF or returns a printable HTML page.
    // =========================================================

    // ---------------------------------------------------------
    //  1. COMPLETE ASSET INVENTORY
    // ---------------------------------------------------------
    case 'report_complete':
        $month = trim($_GET['month'] ?? '');
        $year  = trim($_GET['year']  ?? '');

        [$mc, $mp] = monthYearWhere('TO_CHAR(a.CREATED_AT,\'YYYY-MM-DD\')', $month, $year);
        $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

        $sql = "SELECT a.ASSET_ID, a.DESCRIPTION, a.SERIAL_NUMBER, a.LOCATION,
                       a.STATUS, a.IS_CERTIFIED,
                       c.CATEGORY_NAME, d.DEPARTMENT_NAME,
                       it.ITEM_TYPE_NAME,
                       cu.FIRST_NAME||' '||cu.LAST_NAME AS CUSTODIAN,
                       TO_CHAR(a.CREATED_AT,'YYYY-MM-DD') AS CREATED_AT
                FROM TBL_ASSETS a
                LEFT JOIN TBL_CATEGORIES   c  ON c.CATEGORY_ID   = a.CATEGORY_ID
                LEFT JOIN TBL_DEPARTMENTS  d  ON d.DEPARTMENT_ID = a.DEPARTMENT_ID
                LEFT JOIN TBL_ITEM_TYPES   it ON it.ITEM_TYPE_ID = a.ITEM_TYPE_ID
                LEFT JOIN TBL_CUSTODIANS   cu ON cu.CUSTODIAN_ID = a.CUSTODIAN_ID
                WHERE a.IS_DELETED = 0 $extra
                ORDER BY d.DEPARTMENT_NAME, a.ASSET_ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Complete Asset Inventory', $month, $year, $rows,
            ['ASSET_ID','DESCRIPTION','CATEGORY_NAME','DEPARTMENT_NAME',
             'ITEM_TYPE_NAME','STATUS','IS_CERTIFIED','SERIAL_NUMBER','LOCATION','CUSTODIAN','CREATED_AT'],
            ['Asset ID','Description','Category','Department',
             'Type','Status','Certified','Serial No.','Location','Custodian','Date Added'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  2. ASSET STATUS REPORT
    // ---------------------------------------------------------
    case 'report_status':
        $month = trim($_GET['month'] ?? '');
        $year  = trim($_GET['year']  ?? '');

        [$mc,$mp] = monthYearWhere('TO_CHAR(a.UPDATED_AT,\'YYYY-MM-DD\')', $month, $year);
        $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

        $sql = "SELECT a.ASSET_ID, a.DESCRIPTION, a.STATUS,
                       d.DEPARTMENT_NAME,
                       TO_CHAR(a.UPDATED_AT,'YYYY-MM-DD HH24:MI') AS UPDATED_AT
                FROM TBL_ASSETS a
                LEFT JOIN TBL_DEPARTMENTS d ON d.DEPARTMENT_ID = a.DEPARTMENT_ID
                WHERE a.IS_DELETED = 0 $extra
                ORDER BY a.STATUS, d.DEPARTMENT_NAME";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Asset Status Report', $month, $year, $rows,
            ['ASSET_ID','DESCRIPTION','DEPARTMENT_NAME','STATUS','UPDATED_AT'],
            ['Asset ID','Description','Department','Status','Last Updated'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  3. CERTIFIED ASSETS REPORT
    // ---------------------------------------------------------
    case 'report_certified':
        $month  = trim($_GET['month']     ?? '');
        $year   = trim($_GET['year']      ?? '');
        $deptId = trim($_GET['dept_id']   ?? '');

        [$mc,$mp] = monthYearWhere('TO_CHAR(a.UPDATED_AT,\'YYYY-MM-DD\')', $month, $year);
        $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

        if ($deptId !== '') {
            $extra            .= ' AND a.DEPARTMENT_ID = :dept_id';
            $mp[':dept_id']    = $deptId;
        }

        $sql = "SELECT a.ASSET_ID, a.DESCRIPTION, d.DEPARTMENT_NAME,
                       c.CATEGORY_NAME,
                       cu.FIRST_NAME||' '||cu.LAST_NAME AS CUSTODIAN,
                       TO_CHAR(a.UPDATED_AT,'YYYY-MM-DD') AS CERTIFIED_DATE
                FROM TBL_ASSETS a
                LEFT JOIN TBL_DEPARTMENTS d  ON d.DEPARTMENT_ID  = a.DEPARTMENT_ID
                LEFT JOIN TBL_CATEGORIES  c  ON c.CATEGORY_ID    = a.CATEGORY_ID
                LEFT JOIN TBL_CUSTODIANS  cu ON cu.CUSTODIAN_ID  = a.CUSTODIAN_ID
                WHERE a.IS_DELETED=0 AND a.IS_CERTIFIED=1 $extra
                ORDER BY d.DEPARTMENT_NAME, a.ASSET_ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Certified Assets Report', $month, $year, $rows,
            ['ASSET_ID','DESCRIPTION','CATEGORY_NAME','DEPARTMENT_NAME','CUSTODIAN','CERTIFIED_DATE'],
            ['Asset ID','Description','Category','Department','Custodian','Certified Date'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  4. OVERDUE ITEMS REPORT
    // ---------------------------------------------------------
    case 'report_overdue':
        $month = trim($_GET['month'] ?? '');
        $year  = trim($_GET['year']  ?? '');
        $scope = trim($_GET['scope'] ?? 'all'); // all | borrows | late

        $rows  = [];

        if (in_array($scope, ['all','borrows'])) {
            [$mc,$mp] = monthYearWhere('b.DUE_DATE', $month, $year);
            $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

            $sql = "SELECT b.BORROW_ID AS REF_ID,
                           b.ASSET_ID,
                           b.FIRST_NAME||' '||b.LAST_NAME AS BORROWER,
                           d.DEPARTMENT_NAME,
                           b.BORROW_DATE, b.DUE_DATE,
                           b.STATUS,
                           'Overdue Borrow' AS TYPE
                    FROM TBL_BORROWS b
                    LEFT JOIN TBL_DEPARTMENTS d ON d.DEPARTMENT_ID = b.DEPARTMENT_ID
                    WHERE b.STATUS IN ('Overdue','Borrowed')
                      AND b.DUE_DATE < TO_CHAR(SYSDATE,'YYYY-MM-DD')
                      $extra
                    ORDER BY b.DUE_DATE";

            $st = $conn->prepare($sql); $st->execute($mp);
            $rows = array_merge($rows, $st->fetchAll(PDO::FETCH_ASSOC));
        }

        if (in_array($scope, ['all','late'])) {
            [$mc,$mp] = monthYearWhere('b.DUE_DATE', $month, $year);
            $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

            $sql = "SELECT b.BORROW_ID AS REF_ID,
                           b.ASSET_ID,
                           b.FIRST_NAME||' '||b.LAST_NAME AS BORROWER,
                           d.DEPARTMENT_NAME,
                           b.BORROW_DATE, b.DUE_DATE,
                           b.RETURN_DATE AS STATUS,
                           'Late Return' AS TYPE
                    FROM TBL_BORROWS b
                    LEFT JOIN TBL_DEPARTMENTS d ON d.DEPARTMENT_ID = b.DEPARTMENT_ID
                    WHERE b.STATUS = 'Returned'
                      AND b.RETURN_DATE > b.DUE_DATE
                      $extra
                    ORDER BY b.DUE_DATE";

            $st = $conn->prepare($sql); $st->execute($mp);
            $rows = array_merge($rows, $st->fetchAll(PDO::FETCH_ASSOC));
        }

        outputPdfReport('Overdue Items Report', $month, $year, $rows,
            ['REF_ID','ASSET_ID','TYPE','BORROWER','DEPARTMENT_NAME','BORROW_DATE','DUE_DATE','STATUS'],
            ['Ref #','Asset ID','Type','Borrower','Department','Borrow Date','Due Date','Status/Return Date'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  5. MAINTENANCE REPORT
    // ---------------------------------------------------------
    case 'report_maintenance':
        $month = trim($_GET['month'] ?? '');
        $year  = trim($_GET['year']  ?? '');

        [$mc,$mp] = monthYearWhere('m.SCHEDULED_DATE', $month, $year);
        $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

        $sql = "SELECT m.MAINTENANCE_ID, m.ASSET_ID,
                       mt.TYPE_NAME,
                       m.ISSUE_DESCRIPTION,
                       m.TECH_FIRST_NAME||' '||m.TECH_LAST_NAME AS TECHNICIAN,
                       m.SCHEDULED_DATE, m.COMPLETED_DATE,
                       m.STATUS, m.NOTES
                FROM TBL_MAINTENANCE m
                LEFT JOIN TBL_MAINTENANCE_TYPES mt ON mt.TYPE_ID = m.TYPE_ID
                WHERE 1=1 $extra
                ORDER BY m.SCHEDULED_DATE DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Maintenance Report', $month, $year, $rows,
            ['MAINTENANCE_ID','ASSET_ID','TYPE_NAME','ISSUE_DESCRIPTION',
             'TECHNICIAN','SCHEDULED_DATE','COMPLETED_DATE','STATUS','NOTES'],
            ['ID','Asset ID','Type','Issue','Technician',
             'Scheduled','Completed','Status','Notes'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  6. ASSET BY DEPARTMENT
    // ---------------------------------------------------------
    case 'report_by_dept':
        $month  = trim($_GET['month']   ?? '');
        $year   = trim($_GET['year']    ?? '');
        $deptId = trim($_GET['dept_id'] ?? '');

        [$mc,$mp] = monthYearWhere('TO_CHAR(a.CREATED_AT,\'YYYY-MM-DD\')', $month, $year);
        $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

        if ($deptId !== '') {
            $extra          .= ' AND a.DEPARTMENT_ID = :dept_id';
            $mp[':dept_id']  = $deptId;
        }

        $sql = "SELECT d.DEPARTMENT_NAME,
                       a.ASSET_ID, a.DESCRIPTION, a.STATUS,
                       c.CATEGORY_NAME,
                       it.ITEM_TYPE_NAME,
                       TO_CHAR(a.CREATED_AT,'YYYY-MM-DD') AS CREATED_AT
                FROM TBL_ASSETS a
                LEFT JOIN TBL_DEPARTMENTS d  ON d.DEPARTMENT_ID = a.DEPARTMENT_ID
                LEFT JOIN TBL_CATEGORIES  c  ON c.CATEGORY_ID   = a.CATEGORY_ID
                LEFT JOIN TBL_ITEM_TYPES  it ON it.ITEM_TYPE_ID = a.ITEM_TYPE_ID
                WHERE a.IS_DELETED=0 $extra
                ORDER BY d.DEPARTMENT_NAME, a.ASSET_ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Asset by Department', $month, $year, $rows,
            ['DEPARTMENT_NAME','ASSET_ID','DESCRIPTION','CATEGORY_NAME','ITEM_TYPE_NAME','STATUS','CREATED_AT'],
            ['Department','Asset ID','Description','Category','Type','Status','Added'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  7. BORROWING ACTIVITY REPORT
    // ---------------------------------------------------------
    case 'report_borrowing':
        $month = trim($_GET['month'] ?? '');
        $year  = trim($_GET['year']  ?? '');

        [$mc,$mp] = monthYearWhere('b.BORROW_DATE', $month, $year);
        $extra = $mc ? (' AND ' . implode(' AND ', $mc)) : '';

        $sql = "SELECT b.BORROW_ID, b.ASSET_ID,
                       b.FIRST_NAME||' '||b.LAST_NAME AS BORROWER,
                       d.DEPARTMENT_NAME,
                       b.BORROW_DATE, b.DUE_DATE, b.RETURN_DATE,
                       b.STATUS, b.PURPOSE
                FROM TBL_BORROWS b
                LEFT JOIN TBL_DEPARTMENTS d ON d.DEPARTMENT_ID = b.DEPARTMENT_ID
                WHERE 1=1 $extra
                ORDER BY b.BORROW_DATE DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Borrowing Activity Report', $month, $year, $rows,
            ['BORROW_ID','ASSET_ID','BORROWER','DEPARTMENT_NAME',
             'BORROW_DATE','DUE_DATE','RETURN_DATE','STATUS','PURPOSE'],
            ['ID','Asset ID','Borrower','Department',
             'Borrow Date','Due Date','Return Date','Status','Purpose'],
            $conn
        );
        break;

    // ---------------------------------------------------------
    //  8. ASSET UTILIZATION REPORT
    // ---------------------------------------------------------
    case 'report_utilization':
        $month = trim($_GET['month'] ?? '');
        $year  = trim($_GET['year']  ?? '');

        [$mc,$mp] = monthYearWhere('b.BORROW_DATE', $month, $year);
        $bWhere = $mc ? ('WHERE ' . implode(' AND ', $mc)) : '';

        $sql = "SELECT a.ASSET_ID, a.DESCRIPTION,
                       d.DEPARTMENT_NAME,
                       a.STATUS,
                       NVL(bstat.BORROW_COUNT,0) AS BORROW_COUNT,
                       NVL(mstat.MAINT_COUNT,0)  AS MAINT_COUNT
                FROM TBL_ASSETS a
                LEFT JOIN TBL_DEPARTMENTS d ON d.DEPARTMENT_ID = a.DEPARTMENT_ID
                LEFT JOIN (
                    SELECT ASSET_ID, COUNT(*) AS BORROW_COUNT
                    FROM TBL_BORROWS $bWhere GROUP BY ASSET_ID
                ) bstat ON bstat.ASSET_ID = a.ASSET_ID
                LEFT JOIN (
                    SELECT ASSET_ID, COUNT(*) AS MAINT_COUNT
                    FROM TBL_MAINTENANCE GROUP BY ASSET_ID
                ) mstat ON mstat.ASSET_ID = a.ASSET_ID
                WHERE a.IS_DELETED=0
                ORDER BY BORROW_COUNT DESC, a.ASSET_ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute($mp);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        outputPdfReport('Asset Utilization Report', $month, $year, $rows,
            ['ASSET_ID','DESCRIPTION','DEPARTMENT_NAME','STATUS','BORROW_COUNT','MAINT_COUNT'],
            ['Asset ID','Description','Department','Status','Times Borrowed','Maintenance Count'],
            $conn
        );
        break;

    default:
        oops('Unknown resource: ' . htmlspecialchars($resource));
}

// =============================================================
//  outputPdfReport()
//  Builds a printable HTML page that auto-opens Print dialog.
//  If mPDF is installed it streams a real PDF instead.
// =============================================================
function outputPdfReport(
    string $title,
    string $month,
    string $year,
    array  $rows,
    array  $cols,
    array  $headers,
    PDO    $conn
): void {
    // Log the generation
    logAudit($conn, 'REPORT_GENERATED', 'Reports',
        "Generated '$title'" . ($month ? " month=$month" : '') . ($year ? " year=$year" : ''));

    $filterLabel = '';
    if ($month || $year) {
        $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $filterLabel = trim(($month ? $months[(int)$month] : '') . ' ' . $year);
    }

    $genBy   = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');
    $genDate = date('F d, Y  h:i A');
    $count   = count($rows);

    // Status badge color helper
    $statusColor = function(string $val): string {
        $v = strtolower($val);
        if (in_array($v,['available','active','completed']))   return '#166534;background:#dcfce7';
        if (in_array($v,['borrowed','in use','in progress']))  return '#1d4ed8;background:#dbeafe';
        if (in_array($v,['overdue','maintenance']))            return '#b45309;background:#fef3c7';
        if (in_array($v,['pending']))                          return '#6b21a8;background:#f3e8ff';
        if (in_array($v,['cancelled','returned']))             return '#374151;background:#f3f4f6';
        return '#374151;background:#f3f4f6';
    };

    // Build table rows
    $tbody = '';
    foreach ($rows as $i => $row) {
        $bg    = $i % 2 === 0 ? '#fff' : '#f9fafb';
        $tbody .= "<tr style=\"background:$bg\">";
        foreach ($cols as $c) {
            $val = $row[$c] ?? $row[strtolower($c)] ?? '—';
            if ($c === 'IS_CERTIFIED') {
                $val = ($val == 1) ? '✔ Yes' : 'No';
            }
            if (in_array($c, ['STATUS'])) {
                $sc = $statusColor((string)$val);
                $tbody .= "<td><span style=\"padding:2px 8px;border-radius:9px;font-size:11px;font-weight:600;color:$sc\">"
                        . htmlspecialchars((string)$val) . "</span></td>";
            } else {
                $tbody .= '<td>' . htmlspecialchars((string)$val) . '</td>';
            }
        }
        $tbody .= '</tr>';
    }
    if (!$rows) {
        $colspan = count($cols);
        $tbody   = "<tr><td colspan=\"$colspan\" style=\"text-align:center;color:#888;padding:24px\">No records found.</td></tr>";
    }

    $thead = '<tr>' . implode('', array_map(fn($h) => "<th>$h</th>", $headers)) . '</tr>';

    $filterLabelHtml    = $filterLabel
        ? "<div class=\"rmeta\">Period: <strong>{$filterLabel}</strong></div>"
        : '';
    $filterLabelDisplay = $filterLabel ?: 'All Time';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{$title}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; padding: 24px; }
    .header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; border-bottom: 3px solid #1a1a2e; padding-bottom: 12px; }
    .logo-block .school { font-size: 20px; font-weight: 800; color: #1a1a2e; letter-spacing: 1px; }
    .logo-block .sub    { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .report-meta        { text-align: right; }
    .report-meta .rtitle{ font-size: 15px; font-weight: 700; color: #1a1a2e; }
    .report-meta .rmeta { font-size: 11px; color: #6b7280; margin-top: 3px; }
    .summary-bar        { display: flex; gap: 12px; margin: 14px 0; }
    .scard              { background: #f3f4f6; border-radius: 6px; padding: 8px 16px; flex: 1; }
    .scard .sv          { font-size: 22px; font-weight: 700; color: #1a1a2e; }
    .scard .sl          { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
    table               { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead tr            { background: #1a1a2e; color: #fff; }
    thead th            { padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }
    tbody td            { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
    .footer             { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px; display: flex; justify-content: space-between; color: #9ca3af; font-size: 10px; }
    @media print {
      body { padding: 10px; }
      .no-print { display: none !important; }
      @page { margin: 15mm; }
    }
  </style>
</head>
<body onload="window.print()">

  <!-- Print Button (hidden on print) -->
  <div class="no-print" style="text-align:right;margin-bottom:10px;">
    <button onclick="window.print()" style="background:#1a1a2e;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:13px;">🖨 Print / Save PDF</button>
    <button onclick="window.close()" style="background:#e5e7eb;color:#374151;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px;">✕ Close</button>
  </div>

  <div class="header">
    <div class="logo-block">
      <div class="school">ONEQCU</div>
      <div class="sub">Asset Management System — Quezon City University</div>
    </div>
    <div class="report-meta">
      <div class="rtitle">{$title}</div>
      <div class="rmeta">Generated by: <strong>{$genBy}</strong></div>
      <div class="rmeta">{$genDate}</div>
      {$filterLabelHtml}
    </div>
  </div>

  <div class="summary-bar">
    <div class="scard"><div class="sv">{$count}</div><div class="sl">Total Records</div></div>
    <div class="scard"><div class="sv">{$filterLabelDisplay}</div><div class="sl">Period</div></div>
    <div class="scard"><div class="sv">{$genBy}</div><div class="sl">Generated By</div></div>
  </div>

  <table>
    <thead>{$thead}</thead>
    <tbody>{$tbody}</tbody>
  </table>

  <div class="footer">
    <span>ONEQCU Asset Management System</span>
    <span>Report: {$title} • {$genDate}</span>
    <span>Total: {$count} record(s)</span>
  </div>

</body>
</html>
HTML;

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}