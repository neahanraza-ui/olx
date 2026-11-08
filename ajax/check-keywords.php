<?php
// ajax/check-keywords.php
require_once '../includes/db.php';
require_once '../includes/settings.php';

header('Content-Type: application/json');

$text = $_GET['text'] ?? '';
$keywords = getOnlineKeywords();
$found = [];

foreach ($keywords as $kw) {
    if (trim($kw) !== '' && stripos($text, trim($kw)) !== false) {
        $found[] = trim($kw);
    }
}

// User-specified mandatory keywords for "Online Delivery" detection
$mandatoryKeywords = ['online delivery', 'digital product', 'online service', 'remote delivery', 'virtual product'];
foreach ($mandatoryKeywords as $mkw) {
    if (stripos($text, $mkw) !== false && !in_array($mkw, $found)) {
        $found[] = $mkw;
    }
}

echo json_encode([
    'detected' => !empty($found), 
    'keywords' => $found,
    'message' => !empty($found) ? "This product appears to be an online delivery item." : ""
]);
?>
