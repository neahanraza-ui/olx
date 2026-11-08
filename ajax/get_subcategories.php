<?php
// ajax/get_subcategories.php
require_once '../includes/db.php';

$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

if ($parent_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$stmt->execute([$parent_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
?>
