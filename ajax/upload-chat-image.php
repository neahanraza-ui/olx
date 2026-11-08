<?php
// ajax/upload-chat-image.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uid = getCurrentUserId();
    $file = $_FILES['image'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit();
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
        exit();
    }
    
    $dir = dirname(__DIR__) . '/assets/uploads/chat/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $filename = 'chat_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $dir . $filename;
    $relativePath = 'assets/uploads/chat/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['success' => true, 'path' => $relativePath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No image uploaded']);
}
