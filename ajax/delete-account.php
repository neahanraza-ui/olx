<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'auth']); exit(); }

$uid = getCurrentUserId();
// Soft-delete: set deleted_at, clear session
$pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?")->execute([$uid]);
session_destroy();
echo json_encode(['success' => true]);
