<?php
// includes/security.php

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sanitizeInput($data) {
    return trim(strip_tags($data));
}

// ─── Currency Switcher ───
function getCurrentCurrency() {
    return $_SESSION['currency'] ?? 'PKR';
}

function formatPrice($pkr_amount) {
    $currency = getCurrentCurrency();
    $rates = [
        'PKR' => ['sym' => 'Rs', 'rate' => 1],
        'USD' => ['sym' => '$',  'rate' => 0.0036], // Example rate
        'SAR' => ['sym' => 'SR', 'rate' => 0.013]   // Example rate
    ];
    
    $c = $rates[$currency] ?? $rates['PKR'];
    $val = $pkr_amount * $c['rate'];
    
    if ($currency === 'PKR') {
        return $c['sym'] . ' ' . number_format($val);
    }
    return $c['sym'] . ' ' . number_format($val, 2);
}
?>
