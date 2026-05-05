<?php

require_once __DIR__ . '/../models/NotificationModel.php';

class NotificationService
{
    private $notificationModel;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->notificationModel = new NotificationModel($conn);
    }

    /**
     * Notify about overdue borrows
     */
    public function notifyOverdueBorrows(): int
    {
        $count = 0;

        // Get all overdue borrows that don't have notifications yet
        $stmt = $this->conn->prepare(
            "SELECT b.borrow_id, b.first_name, b.last_name,
                    a.description, b.due_date
             FROM tbl_borrows b
             JOIN tbl_assets a ON a.asset_id = b.asset_id
             WHERE b.status IN ('Overdue', 'Borrowed')
               AND b.due_date < CURRENT_TIMESTAMP
               AND NOT EXISTS (
                 SELECT 1 FROM tbl_notifications n
                 WHERE n.related_entity_type = 'borrow'
                   AND n.related_entity_id = b.borrow_id
                   AND n.title = 'Overdue Item'
               )"
        );

        $stmt->execute();
        $overdueBorrows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get all admin users to notify
        $adminStmt = $this->conn->prepare("SELECT user_id FROM users WHERE ROLE = 'Admin'");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($overdueBorrows as $borrow) {
            foreach ($admins as $admin) {
                $this->notificationModel->create([
                    'user_id' => $admin['USER_ID'],
                    'title' => 'Overdue Item',
                    'message' => "The item '{$borrow['DESCRIPTION']}' borrowed by {$borrow['FIRST_NAME']} {$borrow['LAST_NAME']} is overdue. Due date was {$borrow['DUE_DATE']}.",
                    'type' => 'warning',
                    'priority' => 'high',
                    'related_entity_type' => 'borrow',
                    'related_entity_id' => $borrow['BORROW_ID'],
                    'action_url' => '/1QCUPROJECT/views/admin/borrow_page.php',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notify about upcoming maintenance
     */
    public function notifyUpcomingMaintenance(): int
    {
        $count = 0;

        // Get maintenance due within 7 days that don't have notifications yet
        $stmt = $this->conn->prepare(
            "SELECT m.maintenance_id, m.asset_id, a.description,
                    m.scheduled_date, m.user_id
             FROM tbl_maintenance m
             JOIN tbl_assets a ON a.asset_id = m.asset_id
             WHERE m.status = 'Scheduled'
               AND m.scheduled_date BETWEEN CURRENT_TIMESTAMP
                   AND CURRENT_TIMESTAMP + INTERVAL '7' DAY
               AND NOT EXISTS (
                 SELECT 1 FROM tbl_notifications n
                 WHERE n.related_entity_type = 'maintenance'
                   AND n.related_entity_id = m.maintenance_id
                   AND n.title = 'Maintenance Due Soon'
               )"
        );

        $stmt->execute();
        $upcomingMaintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($upcomingMaintenance as $maintenance) {
            $this->notificationModel->create([
                'user_id' => $maintenance['USER_ID'],
                'title' => 'Maintenance Due Soon',
                'message' => "Maintenance for '{$maintenance['DESCRIPTION']}' is scheduled for {$maintenance['SCHEDULED_DATE']}.",
                'type' => 'info',
                'priority' => 'normal',
                'related_entity_type' => 'maintenance',
                'related_entity_id' => $maintenance['MAINTENANCE_ID'],
                'action_url' => '/1QCUPROJECT/staff/maintenance.php',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Notify about asset status changes
     */
    public function notifyAssetStatusChange(int $assetId, string $oldStatus, string $newStatus): void
    {
        // Get users who might be interested in this asset (borrowers, custodians, admins)
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT u.user_id
             FROM users u
             WHERE u.role = 'Admin'
                OR EXISTS (
                    SELECT 1 FROM tbl_borrows b
                    WHERE b.asset_id = :asset_id AND b.user_id = u.user_id
                )
                OR EXISTS (
                    SELECT 1 FROM tbl_assets a
                    JOIN tbl_custodians c ON c.custodian_id = a.custodian_id
                    WHERE a.asset_id = :asset_id AND c.email = u.email
                )"
        );

        $stmt->bindValue(':asset_id', $assetId);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $this->notificationModel->create([
                'user_id' => $user['USER_ID'],
                'title' => 'Asset Status Changed',
                'message' => "Asset status changed from '{$oldStatus}' to '{$newStatus}'.",
                'type' => 'info',
                'priority' => 'low',
                'related_entity_type' => 'asset',
                'related_entity_id' => $assetId,
                'action_url' => '/1QCUPROJECT/staff/assets.php',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);
        }
    }

    /**
     * Notify about borrow requests (for admins)
     */
    public function notifyBorrowRequest(int $borrowId): void
    {
        // Get all admin users
        $stmt = $this->conn->prepare(
            "SELECT user_id FROM users WHERE ROLE = 'Admin'"
        );
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($admins as $admin) {
            $this->notificationModel->create([
                'user_id' => $admin['USER_ID'],
                'title' => 'New Borrow Request',
                'message' => 'A new borrow request has been submitted and requires approval.',
                'type' => 'info',
                'priority' => 'normal',
                'related_entity_type' => 'borrow',
                'related_entity_id' => $borrowId,
                'action_url' => '/1QCUPROJECT/views/admin/borrow_page.php',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);
        }
    }

    /**
     * Send daily digest notifications
     */
    public function sendDailyDigest(): int
    {
        $count = 0;

        // Get overdue items count per user
        $stmt = $this->conn->prepare(
            "SELECT b.user_id, COUNT(*) as overdue_count
             FROM tbl_borrows b
             WHERE b.status IN ('Overdue', 'Borrowed')
               AND b.due_date < CURRENT_TIMESTAMP
             GROUP BY b.user_id"
        );

        $stmt->execute();
        $overdueStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($overdueStats as $stat) {
            if ($stat['OVERDUE_COUNT'] > 0) {
                $this->notificationModel->create([
                    'user_id' => $stat['USER_ID'],
                    'title' => 'Daily Overdue Summary',
                    'message' => "You have {$stat['OVERDUE_COUNT']} overdue item(s). Please return them as soon as possible.",
                    'type' => 'warning',
                    'priority' => 'high',
                    'action_url' => '/1QCUPROJECT/staff/borrow.php',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(): int
    {
        return $this->notificationModel->deleteExpired();
    }
}