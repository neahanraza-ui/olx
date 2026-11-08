<?php
// ajax/submit-review.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

requireLogin();

$product_id  = (int)($_POST['product_id'] ?? 0);
$rating      = (int)($_POST['rating'] ?? 0);
$review_text = sanitizeInput($_POST['review_text'] ?? '');
$user_id     = getCurrentUserId();

if (!$product_id || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

// 1. Check if the user has a confirmed transaction for this product
$stmt = $pdo->prepare("SELECT id FROM transactions WHERE product_id = ? AND buyer_id = ? AND status = 'confirmed' LIMIT 1");
$stmt->execute([$product_id, $user_id]);
$trans = $stmt->fetch();

if (!$trans) {
    echo json_encode(['success' => false, 'error' => 'You can only review items you have successfully purchased through our Win System.']);
    exit;
}

// 2. Check if already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND reviewer_id = ?");
$stmt->execute([$product_id, $user_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You have already reviewed this product.']);
    exit;
}

// 3. Insert review
$stmt = $pdo->prepare("INSERT INTO reviews (product_id, reviewer_id, rating, review_text) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$product_id, $user_id, $rating, $review_text])) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
