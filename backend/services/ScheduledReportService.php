<?php
class ScheduledReportService
{
    private $model;

    private const VALID_FREQUENCIES = ['Daily', 'Weekly', 'Monthly', 'Quarterly'];
    private const VALID_TYPES = [
        'Complete Asset Inventory', 'Asset by Department', 'Asset Status Report',
        'Certified Assets Report',  'Overdue Items Report', 'Borrowing Activity Report',
        'Asset Utilization Report', 'Maintenance Report',   'Audit Logs Report',
    ];

    public function __construct(ReportModel $model)
    {
        $this->model = $model;
    }

    public function create(array $data): array
    {
        $name      = trim($data['schedule_name'] ?? '');
        $type      = trim($data['report_type']   ?? '');
        $frequency = trim($data['frequency']      ?? '');
        $startDate = trim($data['start_date']     ?? '');
        $runTime   = trim($data['run_time']        ?? '08:00'); // ← add
        $createdBy = trim($data['created_by']     ?? ($_SESSION['username'] ?? 'system'));

        if ($name === '' || $type === '' || $frequency === '' || $startDate === '') {
            return $this->err('All fields are required.');
        }
        if (!in_array($frequency, self::VALID_FREQUENCIES, true)) {
            return $this->err('Invalid frequency value.');
        }
        if (!in_array($type, self::VALID_TYPES, true)) {
            return $this->err('Invalid report type.');
        }
        if (!$this->isValidDate($startDate)) {
            return $this->err('Invalid start date format (expected YYYY-MM-DD).');
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $runTime)) {
            return $this->err('Invalid run time format (expected HH:MM, e.g. 07:00).');
        }

        return $this->model->createSchedule([
            'schedule_name' => $name,
            'report_type'   => $type,
            'frequency'     => $frequency,
            'start_date'    => $startDate,
            'run_time'      => $runTime, 
            'created_by'    => $createdBy,
        ]);
    }

    public function getAll(): array
    {
        return $this->model->getAllSchedules();
    }

    public function getById(int $id): array
    {
        $row = $this->model->getScheduleById($id);
        if (!$row) return $this->err('Schedule not found.');
        return ['ok' => true, 'data' => $row];
    }

    public function toggle(int $id): array
    {
        $row = $this->model->getScheduleById($id);
        if (!$row) return $this->err('Schedule not found.');

        // Handle both uppercase (Oracle PDO) and lowercase keys
        $currentState = $row['IS_ACTIVE'] ?? $row['is_active'] ?? 0;
        $newState     = $currentState == 1 ? 0 : 1;

        $this->model->toggleSchedule($id, $newState);

        $label = $newState === 1 ? 'activated' : 'deactivated';
        return ['ok' => true, 'message' => "Schedule {$label}.", 'is_active' => $newState];
    }

    public function delete(int $id): array
    {
        $deleted = $this->model->deleteSchedule($id);
        if (!$deleted) return $this->err('Schedule not found.');
        return ['ok' => true, 'message' => 'Schedule deleted.'];
    }

    public function getDue(): array
    {
        return $this->model->getDueSchedules();
    }

    public function bumpNextRun(int $id): array
{
    $row = $this->model->getScheduleById($id);
    if (!$row) return $this->err('Schedule not found.');

    $nextDate = $this->computeNextRun(
        $row['next_run_date'] ?? $row['NEXT_RUN_DATE'],
        $row['frequency']     ?? $row['FREQUENCY']
    );
    if ($nextDate === null) return $this->err("Unknown frequency.");

    return $this->model->updateNextRunDate($id, $nextDate);
}

    private function computeNextRun(string $fromDate, string $frequency): ?string
    {
        $dt = DateTime::createFromFormat('Y-m-d', $fromDate);
        if (!$dt) return null;

        switch ($frequency) {
            case 'Daily':     $dt->modify('+1 day');    break;
            case 'Weekly':    $dt->modify('+7 days');   break;  
            case 'Monthly':   $dt->modify('+1 month');  break;
            case 'Quarterly': $dt->modify('+3 months'); break;
            default: return null;
        }
        return $dt->format('Y-m-d');
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        [$y, $m, $d] = explode('-', $date);
        return checkdate((int) $m, (int) $d, (int) $y);
    }

    private function err(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'data' => null];
    }
}