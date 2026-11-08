<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'auth']); exit(); }

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'pause'; // pause | resume
$user_id = getCurrentUserId();

$new_status = ($action === 'resume') ? 'active' : 'paused';
$stmt = $pdo->prepare("UPDATE products SET status=? WHERE id=? AND seller_id=?");
$ok = $stmt->execute([$new_status, $id, $user_id]);

echo json_encode(['success' => (bool)$ok, 'status' => $new_status]);
