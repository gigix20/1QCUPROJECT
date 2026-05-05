<?php

/**
 * Notification Processor
 * This script should be run periodically (e.g., via cron job) to generate automated notifications
 * Run with: php notification_processor.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/NotificationService.php';

echo "Starting notification processor...\n";

try {
    $notificationService = new NotificationService($conn);

    // Process overdue borrows
    echo "Processing overdue borrow notifications...\n";
    $overdueCount = $notificationService->notifyOverdueBorrows();
    echo "Created {$overdueCount} overdue borrow notifications\n";

    // Process upcoming maintenance
    echo "Processing upcoming maintenance notifications...\n";
    $maintenanceCount = $notificationService->notifyUpcomingMaintenance();
    echo "Created {$maintenanceCount} maintenance notifications\n";

    // Send daily digest (optional - can be run daily)
    echo "Processing daily digest notifications...\n";
    $digestCount = $notificationService->sendDailyDigest();
    echo "Created {$digestCount} daily digest notifications\n";

    // Clean up expired notifications
    echo "Cleaning up expired notifications...\n";
    $cleanupCount = $notificationService->cleanupOldNotifications();
    echo "Cleaned up {$cleanupCount} expired notifications\n";

    echo "Notification processing completed successfully!\n";

} catch (Exception $e) {
    echo "Error processing notifications: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";