<?php
// ajax/translate-ad.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/settings.php';

header('Content-Type: application/json');

$product_id = (int)($_GET['id'] ?? 0);
$target_lang = $_GET['to'] ?? 'ur'; // Default to Urdu

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Missing product ID']);
    exit();
}

// Check cache first
$stmt = $pdo->prepare("SELECT * FROM ad_translations WHERE product_id = ? AND language = ?");
$stmt->execute([$product_id, $target_lang]);
$cached = $stmt->fetch();

if ($cached) {
    echo json_encode([
        'success' => true,
        'title' => $cached['title'],
        'description' => $cached['description']
    ]);
    exit();
}

// Not in cache, use AI to translate
$api_key = getSetting('gemini_api_key', '');
if (!$api_key) {
    echo json_encode(['success' => false, 'error' => 'Translation service not configured']);
    exit();
}

// Fetch original content
$stmt = $pdo->prepare("SELECT title, description FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit();
}

$prompt = "Translate the following product title and description to " . ($target_lang == 'ur' ? 'Urdu' : 'English') . ". " .
          "Return ONLY a JSON object with 'title' and 'description' keys. Do not include markdown or other text.\n\n" .
          "Title: " . $product['title'] . "\n" .
          "Description: " . $product['description'];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;
$data = [
    "contents" => [["parts" => [["text" => $prompt]]]]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode($response, true);
$raw_json = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Clean up Gemini output (sometimes it adds ```json ... ```)
$raw_json = trim($raw_json);
if (str_starts_with($raw_json, '```')) {
    $raw_json = preg_replace('/^```(?:json)?\n?/', '', $raw_json);
    $raw_json = preg_replace('/\n?```$/', '', $raw_json);
}

$translated = json_decode($raw_json, true);

if ($translated && isset($translated['title']) && isset($translated['description'])) {
    // Cache it
    $stmt = $pdo->prepare("INSERT INTO ad_translations (product_id, language, title, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $target_lang, $translated['title'], $translated['description']]);
    
    echo json_encode([
        'success' => true,
        'title' => $translated['title'],
        'description' => $translated['description']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Translation failed', 'raw' => $raw_json]);
}
