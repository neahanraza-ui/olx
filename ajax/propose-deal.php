<?php
// ajax/propose-deal.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

if (!isLoggedIn()) exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $price = $_POST['agreed_price'];
    $location = sanitizeInput($_POST['meeting_location']);
    $buyer_id = getCurrentUserId();

    $stmt = $pdo->prepare("SELECT seller_id, title FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product && $product['seller_id'] != $buyer_id) {
        $msg = "DEAL PROPOSAL: {$product['title']} for \${$price} at {$location}.";
        
        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE product_id = ? AND buyer_id = ?");
        $stmt->execute([$product_id, $buyer_id]);
        $conv = $stmt->fetch();
        
        if ($conv) {
            $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_system) VALUES (?, ?, ?, 1)")
                ->execute([$conv['id'], $buyer_id, $msg]);
            
            echo json_encode(['success' => true]);
        }
    }
}
?>
