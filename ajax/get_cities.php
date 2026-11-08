<?php
// ajax/get_cities.php
require_once '../includes/db.php';
require_once '../includes/security.php';

$country_id = (int)($_GET['country_id'] ?? 0);

if (!$country_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM locations WHERE parent_id = ? AND type = 'city' AND status = 'active' ORDER BY name ASC");
$stmt->execute([$country_id]);
$cities = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($cities);
?>
