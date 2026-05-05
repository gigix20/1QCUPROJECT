<?php

require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class NotificationController
{
    private $model;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->model = new NotificationModel($conn);
    }

    /**
     * Get notifications for current user
     */
    public function getNotifications(): void
    {
        $userId = $this->getCurrentUserId();
        $includeRead = isset($_GET['include_read']) && $_GET['include_read'] === 'true';
        $limit = (int)($_GET['limit'] ?? 50);

        $notifications = $this->model->getUserNotifications($userId, $includeRead, $limit);
        $unreadCount = $this->model->getUnreadCount($userId);

        ResponseHelper::sendSuccess([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Get unread count for current user
     */
    public function getUnreadCount(): void
    {
        $userId = $this->getCurrentUserId();
        $count = $this->model->getUnreadCount($userId);

        ResponseHelper::sendSuccess(['unread_count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $userId = $this->getCurrentUserId();
        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if (!$notificationId) {
            ResponseHelper::sendError(400, 'Notification ID is required');
            return;
        }

        $success = $this->model->markAsRead($notificationId, $userId);

        if ($success) {
            ResponseHelper::sendSuccess(['message' => 'Notification marked as read']);
        } else {
            ResponseHelper::sendError(404, 'Notification not found or access denied');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): void
    {
        $userId = $this->getCurrentUserId();
        $count = $this->model->markAllAsRead($userId);

        ResponseHelper::sendSuccess([
            'message' => 'All notifications marked as read',
            'marked_count' => $count
        ]);
    }

    /**
     * Create a test notification (for development/testing)
     */
    public function createTestNotification(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ResponseHelper::sendError(405, 'POST required');
            return;
        }

        $userId = $this->getCurrentUserId();
        $title = trim($_POST['title'] ?? 'Test Notification');
        $message = trim($_POST['message'] ?? 'This is a test notification');
        $type = trim($_POST['type'] ?? 'info');
        $priority = trim($_POST['priority'] ?? 'normal');

        $notificationId = $this->model->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority
        ]);

        ResponseHelper::sendSuccess([
            'message' => 'Test notification created',
            'notification_id' => $notificationId
        ]);
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpired(): void
    {
        $deletedCount = $this->model->deleteExpired();

        ResponseHelper::sendSuccess([
            'message' => 'Expired notifications cleaned up',
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Get current user ID from session
     */
    private function getCurrentUserId(): int
    {
        if (!isset($_SESSION['user_id'])) {
            ResponseHelper::sendError(401, 'User not authenticated');
            exit;
        }
        return (int)$_SESSION['user_id'];
    }
}