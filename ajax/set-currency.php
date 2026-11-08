<?php
// ajax/set-currency.php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$curr = $_GET['curr'] ?? 'PKR';
if (in_array($curr, ['PKR', 'USD', 'SAR'])) {
    $_SESSION['currency'] = $curr;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid currency']);
}
