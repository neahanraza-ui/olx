<?php
// ajax/respond-deal.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

if (!isLoggedIn()) exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversation_id = $_POST['conversation_id'];
    $action = $_POST['action']; // 'accept' or 'reject'
    $user_id = getCurrentUserId();

    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND seller_id = ?");
    $stmt->execute([$conversation_id, $user_id]);
    $conv = $stmt->fetch();

    if ($conv) {
        if ($action === 'accept') {
            // Complex: Normally we'd parse the last message or pass data back
            // For MVP, we'll assume the seller confirms the LAST proposal price/loc
            // In a real app, you'd store proposal state in a 'deals' table
            
            $uid = 'REC-' . strtoupper(bin2hex(random_bytes(4)));
            
            // Logically we need the price and location from the last proposal
            // Simplifying for this flow:
            $agreed_price = $_POST['price'] ?? 0;
            $location = $_POST['location'] ?? 'Agreed Location';

            $stmt = $pdo->prepare("INSERT INTO receipts (receipt_uid, product_id, buyer_id, seller_id, agreed_price, meeting_location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$uid, $conv['product_id'], $conv['buyer_id'], $conv['seller_id'], $agreed_price, $location]);
            
            // Mark product as sold
            $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ?")->execute([$conv['product_id']]);
            
            // System message
            $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_system) VALUES (?, ?, ?, 1)")
                ->execute([$conversation_id, $user_id, "DEAL ACCEPTED. Receipt generated: $uid"]);
        } else {
            $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_system) VALUES (?, ?, ?, 1)")
                ->execute([$conversation_id, $user_id, "DEAL REJECTED by seller."]);
        }
        echo json_encode(['success' => true]);
    }
}
?>
