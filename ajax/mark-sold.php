<?php
// ajax/mark-sold.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("UPDATE products SET status='sold' WHERE id=? AND seller_id=?");
$stmt->execute([$id, getCurrentUserId()]);
echo json_encode(['success' => true]);
