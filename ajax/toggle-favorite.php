<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'auth']); exit(); }

$product_id = (int)($_POST['product_id'] ?? 0);
$user_id    = getCurrentUserId();

if (!$product_id) { echo json_encode(['error' => 'invalid']); exit(); }

// Check if already saved
$exists = $pdo->prepare("SELECT id FROM favorites WHERE user_id=? AND product_id=?");
$exists->execute([$user_id, $product_id]);

if ($exists->fetch()) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND product_id=?")->execute([$user_id, $product_id]);
    $pdo->prepare("UPDATE products SET saves_count = GREATEST(0, saves_count-1) WHERE id=?")->execute([$product_id]);
    echo json_encode(['saved' => false]);
} else {
    $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?,?)")->execute([$user_id, $product_id]);
    $pdo->prepare("UPDATE products SET saves_count = saves_count+1 WHERE id=?")->execute([$product_id]);
    echo json_encode(['saved' => true]);
}
