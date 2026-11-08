<?php
// ajax/delete-ad.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
$id = (int)($_POST['id'] ?? 0);
// Admins can delete any ad, users only their own
if (!empty($_SESSION['is_admin'])) {
    $pdo->prepare("UPDATE products SET status='deleted' WHERE id=?")->execute([$id]);
} else {
    $pdo->prepare("UPDATE products SET status='deleted' WHERE id=? AND seller_id=?")->execute([$id, getCurrentUserId()]);
}
echo json_encode(['success' => true]);
