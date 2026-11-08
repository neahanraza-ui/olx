<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    if (isLoggedIn()) {
        $user_id = getCurrentUserId();
        
        // Use REPLACE INTO or similar if available, or just INSERT
        $stmt = $pdo->prepare("INSERT INTO cookies_consent (user_id, analytics, preferences, marketing) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE analytics = VALUES(analytics), preferences = VALUES(preferences), marketing = VALUES(marketing)");
        $stmt->execute([
            $user_id,
            (int)($data['analytics'] ?? 0),
            (int)($data['preferences'] ?? 0),
            (int)($data['marketing'] ?? 0)
        ]);
    } else {
        // Optional: Log by session_id even if not logged in
        $session_id = session_id();
        $stmt = $pdo->prepare("INSERT INTO cookies_consent (session_id, analytics, preferences, marketing) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $session_id,
            (int)($data['analytics'] ?? 0),
            (int)($data['preferences'] ?? 0),
            (int)($data['marketing'] ?? 0)
        ]);
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
