<?php
// ajax/block-user.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$blocker_id = getCurrentUserId();
$blocked_id = (int)($_POST['blocked_id'] ?? 0);
$action = $_POST['action'] ?? 'block'; // block | unblock

if (!$blocked_id || $blocker_id == $blocked_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

if ($action === 'block') {
    $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
    $ok = $stmt->execute([$blocker_id, $blocked_id]);
    $message = "User blocked successfully";
} else {
    $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $ok = $stmt->execute([$blocker_id, $blocked_id]);
    $message = "User unblocked successfully";
}

echo json_encode(['success' => $ok, 'message' => $message]);
