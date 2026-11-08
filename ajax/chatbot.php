<?php
// ajax/chatbot.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/lang.php';

// Groq API Configuration
$GROQ_API_KEY = getenv('GROQ_API_KEY') ?: '';
$GROQ_URL     = 'https://api.groq.com/openai/v1/chat/completions';
$MODEL        = 'llama-3.1-8b-instant';

if (!$GROQ_API_KEY) {
    http_response_code(503);
    echo json_encode(['error' => 'Chatbot is not configured']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (!$message) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// ── System context ──
$systemContext = "You are a helpful AI assistant for OLX Plus, Pakistan's trust-focused online marketplace. " .
    "You help users with: buying and selling items, posting ads, understanding how mutual receipts work, " .
    "verifying sellers, staying safe from scams, navigating the site, and general marketplace questions. " .
    "Keep responses short, friendly, and practical. If the user writes in Urdu, reply in Urdu. " .
    "If the user writes in English, reply in English. " .
    "Never translate or change the name 'OLX Plus'. " .
    "Do not answer questions unrelated to OLX Plus or general marketplace topics. " .
    "If asked something off-topic, politely redirect to OLX Plus topics.";

// Build messages array (OpenAI format used by Groq)
$messages = [];
$messages[] = ['role' => 'system', 'content' => $systemContext];

foreach ($history as $turn) {
    $role = ($turn['role'] === 'user') ? 'user' : 'assistant';
    $messages[] = ['role' => $role, 'content' => $turn['text']];
}
$messages[] = ['role' => 'user', 'content' => $message];

$payload = json_encode([
    'model'       => $MODEL,
    'messages'    => $messages,
    'temperature' => 0.7,
    'max_tokens'  => 512,
]);

// Call Groq API
$ch = curl_init($GROQ_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $GROQ_API_KEY
    ],
    CURLOPT_TIMEOUT        => 20,
    // Fix SSL certificate issue common on XAMPP/Windows
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$raw      = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// cURL failure
if ($curlErr) {
    echo json_encode(['error' => 'Connection failed: ' . $curlErr]);
    exit;
}

// API error
if ($httpCode !== 200) {
    $apiError = json_decode($raw, true);
    $msg = $apiError['error']['message'] ?? ('HTTP ' . $httpCode);
    echo json_encode(['error' => 'Groq API error: ' . $msg]);
    exit;
}

$data  = json_decode($raw, true);
$reply = $data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';

echo json_encode(['reply' => $reply]);
