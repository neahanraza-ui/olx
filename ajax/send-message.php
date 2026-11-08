<?php
// ajax/send-message.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

if (!isLoggedIn()) exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/notifications.php';
    
    $conversation_id = (int)$_POST['conversation_id'];
    $message = sanitizeInput($_POST['message'] ?? '');
    $image_path = sanitizeInput($_POST['image_path'] ?? null);
    $sender_id = getCurrentUserId();

    if ($message === '' && !$image_path) {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit();
    }

    // Verify user is part of conversation and get the other user's ID
    $stmt = $pdo->prepare("SELECT id, buyer_id, seller_id, product_id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
    $stmt->execute([$conversation_id, $sender_id, $sender_id]);
    $conv = $stmt->fetch();
    
    if ($conv) {
        $recipient_id = ($conv['buyer_id'] == $sender_id) ? $conv['seller_id'] : $conv['buyer_id'];
        
        // Insert message
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, image_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$conversation_id, $sender_id, $message, $image_path]);
        $message_id = $pdo->lastInsertId();
        
        // Update conversation timestamp
        $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conversation_id]);
        
        // Create notification for recipient
        createNotification($recipient_id, 'new_message', [
            'conversation_id' => $conversation_id,
            'sender_name' => $_SESSION['user_name'],
            'message_snippet' => $message ?: "(Image attachment)"
        ]);
        
        echo json_encode(['success' => true, 'id' => $message_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
}
?>
