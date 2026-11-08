<?php
// ajax/get-messages.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['messages' => []]);
    exit();
}

$conversation_id = (int)($_GET['conversation_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);
$user_id = getCurrentUserId();

// Verify user
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
$stmt->execute([$conversation_id, $user_id, $user_id]);

if ($stmt->fetch()) {
    // Mark messages from the OTHER user as read
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0")
        ->execute([$conversation_id, $user_id]);

    // Fetch new messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.name AS sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.conversation_id = ? AND m.id > ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also send back the read status of the LATEST message sent by the current user
    // to update ticks on the frontend.
    $stmt = $pdo->prepare("SELECT id, is_read FROM messages WHERE conversation_id = ? AND sender_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$conversation_id, $user_id]);
    $read_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'read_statuses' => $read_statuses
    ]);
} else {
    echo json_encode(['success' => false, 'messages' => [], 'error' => 'Unauthorized']);
}
?>
