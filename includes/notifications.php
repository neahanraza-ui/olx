<?php
// includes/notifications.php
require_once __DIR__ . '/db.php';

/**
 * Create a new notification for a user
 * 
 * @param int $user_id The recipient user ID
 * @param string $type The notification type (e.g., 'new_message', 'deal_update', 'admin_alert')
 * @param array $data Additional data for the notification
 * @return bool Success or failure
 */
function createNotification($user_id, $type, $data = []) {
    global $pdo;
    
    $json_data = json_encode($data);
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, data) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $type, $json_data]);
}

/**
 * Get unread notification count for a user
 * 
 * @param int $user_id
 * @return int
 */
function getUnreadNotificationCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}
