<?php
require 'backend/config/database.php';
try {
    $userId = 1;
    $stmt = $conn->prepare('SELECT COUNT(*) AS CNT FROM tbl_notifications WHERE user_id = :user_id AND is_read = 0 AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)');
    $stmt->bindValue(':user_id', $userId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "count=" . ($row['CNT'] ?? 'NULL') . "\n";
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}
