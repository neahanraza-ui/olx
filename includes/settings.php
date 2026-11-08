<?php
// includes/settings.php
require_once 'db.php';

function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

function getOnlineKeywords() {
    $keywords = getSetting('online_keywords');
    return array_map('trim', explode(',', $keywords));
}
?>
