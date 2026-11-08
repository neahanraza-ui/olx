<?php
require_once 'includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'resolve') $pdo->prepare("UPDATE reports SET status='resolved' WHERE id=?")->execute([$id]);
    if ($action === 'remove')  $pdo->prepare("UPDATE products SET status='deleted' WHERE id=(SELECT product_id FROM reports WHERE id=?)")->execute([$id]);
    header("Location: moderation.php"); exit();
}

$reports = $pdo->query("
  SELECT r.*, COALESCE(p.title, 'Unknown Product') AS product_title, u.name AS reporter_name
  FROM reports r
  LEFT JOIN products p ON r.product_id = p.id
  JOIN users u ON r.reporter_id = u.id
  WHERE r.status = 'pending'
  ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moderation – Admin – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>Moderation Queue</h1>
    <span style="color:var(--danger);"><?php echo count($reports); ?> pending report<?php echo count($reports) !== 1 ? 's' : ''; ?></span>
  </div>

  <?php if (empty($reports)): ?>
    <div class="card" style="text-align:center;padding:64px;">
      <i class="fas fa-shield-alt" style="font-size:3rem;display:block;margin-bottom:16px;color:var(--success);"></i>
      <h3>All Clear!</h3>
      <p style="color:var(--text-3);margin-top:8px;">No pending reports.</p>
    </div>
  <?php else: ?>
    <div class="card" style="padding:0;overflow:hidden;">
      <table class="data-table">
        <thead><tr><th>Report ID</th><th>Product</th><th>Reporter</th><th>Reason</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
          <tr>
            <td style="color:var(--text-3);font-size:.8rem;">#<?php echo $r['id']; ?></td>
            <td>
              <a href="/olx1/product.php?id=<?php echo $r['product_id']; ?>" target="_blank" style="font-weight:600;">
                <?php echo h($r['product_title']); ?>
              </a>
            </td>
            <td><?php echo h($r['reporter_name']); ?></td>
            <td style="color:var(--text-2);font-size:.875rem;max-width:220px;"><?php echo h($r['reason'] ?? '–'); ?></td>
            <td style="color:var(--text-3);font-size:.8rem;"><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
            <td>
              <form method="POST" style="display:inline;"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button name="action" value="resolve" class="btn btn-success btn-sm" title="Dismiss"><i class="fas fa-check"></i> Dismiss</button>
                <button name="action" value="remove"  class="btn btn-danger  btn-sm" title="Remove Ad"><i class="fas fa-trash"></i> Remove Ad</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
</div>
</body>
</html>
