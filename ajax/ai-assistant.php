<?php
// ajax/ai-assistant.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/settings.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$prompt = $_POST['prompt'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);

if (!$prompt) {
    echo json_encode(['success' => false, 'error' => 'Missing prompt']);
    exit();
}

$api_key = getSetting('gemini_api_key', '');
if (!$api_key) {
    echo json_encode(['success' => false, 'error' => 'AI Service not configured (missing API key)']);
    exit();
}

// Fetch product context if available
$context = "You are a helpful assistant for OLX Plus, an online marketplace. ";
if ($product_id) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product) {
        $context .= "The user is currently looking at this product: " . 
                    "Title: {$product['title']}, " .
                    "Price: Rs {$product['price']}, " .
                    "Category: {$product['category_name']}, " .
                    "Description: {$product['description']}. ";
    }
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $context . "\n\nUser: " . $prompt]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode($response, true);
$ai_message = $resData['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I am unable to process that right now.';

echo json_encode(['success' => true, 'message' => $ai_message]);
