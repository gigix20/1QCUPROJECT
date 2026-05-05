<?php

class NotificationModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Create a new notification
     */
    public function create(array $data): int
    {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO tbl_notifications
                    (user_id, title, message, type, priority, related_entity_type,
                     related_entity_id, action_url, expires_at)
                 VALUES
                    (:user_id, :title, :message, :type, :priority, :related_entity_type,
                     :related_entity_id, :action_url, :expires_at)"
            );

            $stmt->bindValue(':user_id', $data['user_id']);
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':message', $data['message']);
            $stmt->bindValue(':type', $data['type'] ?? 'info');
            $stmt->bindValue(':priority', $data['priority'] ?? 'normal');
            $stmt->bindValue(':related_entity_type', $data['related_entity_type'] ?? null);
            $stmt->bindValue(':related_entity_id', $data['related_entity_id'] ?? null);
            $stmt->bindValue(':action_url', $data['action_url'] ?? null);
            $stmt->bindValue(':expires_at', $data['expires_at'] ?? null);

            $stmt->execute();
            $this->conn->exec('COMMIT');

            // Get the inserted notification ID
            $stmt = $this->conn->prepare("SELECT notifications_seq.CURRVAL FROM dual");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            throw new Exception('Failed to create notification: ' . $e->getMessage());
        }
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications(int $userId, bool $includeRead = true, int $limit = 50): array
    {
        try {
            $readFilter = $includeRead ? '' : ' AND is_read = 0';

            $stmt = $this->conn->prepare(
                "SELECT * FROM (
                   SELECT notification_id, user_id, title, message, type, priority,
                          is_read, related_entity_type, related_entity_id, action_url,
                          TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                          TO_CHAR(expires_at, 'YYYY-MM-DD HH24:MI:SS') AS expires_at
                   FROM tbl_notifications
                   WHERE user_id = :user_id{$readFilter}
                     AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
                   ORDER BY priority DESC, created_at DESC
                 ) WHERE ROWNUM <= :limit"
            );

            $stmt->bindValue(':user_id', $userId);
            $stmt->bindValue(':limit', $limit);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch user notifications: ' . $e->getMessage());
        }
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) AS CNT FROM tbl_notifications
                 WHERE user_id = :user_id AND is_read = 0
                   AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)"
            );
            $stmt->bindValue(':user_id', $userId);
            $stmt->execute();
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['CNT'] ?? 0);
        } catch (Exception $e) {
            throw new Exception('Failed to get unread count: ' . $e->getMessage());
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE tbl_notifications
                 SET is_read = 1
                 WHERE notification_id = :notification_id AND user_id = :user_id"
            );
            $stmt->bindValue(':notification_id', $notificationId);
            $stmt->bindValue(':user_id', $userId);
            $stmt->execute();
            $this->conn->exec('COMMIT');
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('Failed to mark as read: ' . $e->getMessage());
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE tbl_notifications
                 SET is_read = 1
                 WHERE user_id = :user_id AND is_read = 0"
            );
            $stmt->bindValue(':user_id', $userId);
            $stmt->execute();
            $this->conn->exec('COMMIT');
            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception('Failed to mark all as read: ' . $e->getMessage());
        }
    }

    /**
     * Delete expired notifications
     */
    public function deleteExpired(): int
    {
        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM tbl_notifications
                 WHERE expires_at IS NOT NULL AND expires_at < CURRENT_TIMESTAMP"
            );
            $stmt->execute();
            $this->conn->exec('COMMIT');
            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception('Failed to delete expired: ' . $e->getMessage());
        }
    }

    /**
     * Get notifications by type and entity
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM tbl_notifications
             WHERE related_entity_type = :entity_type
               AND related_entity_id = :entity_id
             ORDER BY created_at DESC"
        );
        $stmt->bindValue(':entity_type', $entityType);
        $stmt->bindValue(':entity_id', $entityId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create notification for overdue items
     */
    public function createOverdueNotification(int $borrowId, int $userId): void
    {
        $this->create([
            'user_id' => $userId,
            'title' => 'Overdue Item',
            'message' => 'You have an overdue borrowed item that needs to be returned.',
            'type' => 'warning',
            'priority' => 'high',
            'related_entity_type' => 'borrow',
            'related_entity_id' => $borrowId,
            'action_url' => '/1QCUPROJECT/staff/borrow.php',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ]);
    }

    /**
     * Create notification for maintenance due
     */
    public function createMaintenanceDueNotification(int $maintenanceId, int $userId): void
    {
        $this->create([
            'user_id' => $userId,
            'title' => 'Maintenance Due',
            'message' => 'Scheduled maintenance is due for one of your assets.',
            'type' => 'info',
            'priority' => 'normal',
            'related_entity_type' => 'maintenance',
            'related_entity_id' => $maintenanceId,
            'action_url' => '/1QCUPROJECT/staff/maintenance.php',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ]);
    }

    /**
     * Create notification for asset status change
     */
    public function createAssetStatusNotification(int $assetId, string $oldStatus, string $newStatus, int $userId): void
    {
        $this->create([
            'user_id' => $userId,
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