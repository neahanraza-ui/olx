<?php
// ajax/mark-notifications-read.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$uid = getCurrentUserId();
$id = (int)($_POST['id'] ?? 0); // 0 means mark all as read

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?");
    $ok = $stmt->execute([$id, $uid]);
} else {
    $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL");
    $ok = $stmt->execute([$uid]);
}

echo json_encode(['success' => $ok]);
