<?php
// ajax/report-chat.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$reporter_id = getCurrentUserId();
$conversation_id = (int)($_POST['conversation_id'] ?? 0);
$reason = sanitizeInput($_POST['reason'] ?? '');

if (!$conversation_id || !$reason) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

// Verify user is part of the conversation
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
$stmt->execute([$conversation_id, $reporter_id, $reporter_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$stmt = $pdo->prepare("INSERT INTO chat_reports (reporter_id, conversation_id, reason) VALUES (?, ?, ?)");
$ok = $stmt->execute([$reporter_id, $conversation_id, $reason]);

echo json_encode(['success' => $ok, 'message' => 'Chat reported successfully. Our team will review it.']);
