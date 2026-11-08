<?php
// ajax/confirm-sale.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

requireLogin();

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$action     = $_POST['action'] ?? ''; // 'buyer_confirm' or 'seller_accept'
$user_id    = getCurrentUserId();

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid product.']);
    exit;
}

// Fetch product and current transaction if any
$prod = $pdo->prepare("SELECT seller_id, title, price, online_product FROM products WHERE id = ?");
$prod->execute([$product_id]);
$prod = $prod->fetch();

if (!$prod || !$prod['online_product']) {
    echo json_encode(['success' => false, 'error' => 'Not an online product.']);
    exit;
}

// Find existing pending transaction or create one
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE product_id = ? AND status != 'completed' LIMIT 1");
$stmt->execute([$product_id]);
$trans = $stmt->fetch();

if (!$trans) {
    if ($action === 'buyer_confirm') {
        // Create new pending transaction
        $pdo->prepare("INSERT INTO transactions (product_id, buyer_id, seller_id, agreed_price, status) VALUES (?, ?, ?, ?, 'pending')")
            ->execute([$product_id, $user_id, $prod['seller_id'], $prod['price']]);
        $trans_id = $pdo->lastInsertId();
        
        // Add system message to chat
        $conv = $pdo->prepare("SELECT id FROM conversations WHERE product_id = ? AND buyer_id = ?");
        $conv->execute([$product_id, $user_id]);
        $conv = $conv->fetch();
        if ($conv) {
            $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_system) VALUES (?, ?, ?, 1)")
                ->execute([$conv['id'], $user_id, "Buyer has initiated the purchase. Seller, please accept to generate a receipt."]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Purchase initiated. Waiting for seller to accept.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No pending purchase found for this product.']);
    }
} else {
    if ($action === 'seller_accept' && $user_id == $prod['seller_id']) {
        if ($trans['status'] === 'pending') {
            $pdo->prepare("UPDATE transactions SET status = 'confirmed' WHERE id = ?")
                ->execute([$trans['id']]);
            
            // System message
            $stmt = $pdo->prepare("SELECT id FROM conversations WHERE product_id = ? AND buyer_id = ?");
            $stmt->execute([$product_id, $trans['buyer_id']]);
            $conv = $stmt->fetch();
            if ($conv) {
                $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_system) VALUES (?, ?, ?, 1)")
                    ->execute([$conv['id'], $user_id, "Seller has accepted the sale! A transaction receipt has been generated."]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Sale accepted! Receipt generated.', 'status' => 'confirmed']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Transaction already confirmed.']);
        }
    } elseif ($action === 'buyer_confirm' && $user_id == $trans['buyer_id']) {
        echo json_encode(['success' => false, 'error' => 'You already initiated this purchase.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
    }
}
?>
