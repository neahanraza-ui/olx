<?php
// ajax/get-notifications.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$uid = getCurrentUserId();
$limit = (int)($_GET['limit'] ?? 10);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
$stmt->execute([$uid, $limit]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format notifications for display
foreach ($notifications as &$n) {
    $n['data'] = json_decode($n['data'], true);
    $n['time_ago'] = time_ago($n['created_at']); // Assuming time_ago helper exists or will be added
}

echo json_encode([
    'success' => true, 
    'notifications' => $notifications,
    'unread_count' => count(array_filter($notifications, function($notif) { return $notif['read_at'] === null; }))
]);

// Basic time_ago if not in security.php
function time_ago($timestamp) {
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "Just now";
    $min = round($diff / 60);
    if ($min < 60) return $min . "m ago";
    $hrs = round($min / 60);
    if ($hrs < 24) return $hrs . "h ago";
    $days = round($hrs / 24);
    return $days . "d ago";
}
