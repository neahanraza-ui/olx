<?php
require_once 'includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete')       $pdo->prepare("UPDATE products SET status='deleted' WHERE id=?")->execute([$id]);
    if ($action === 'force_online') $pdo->prepare("UPDATE products SET is_online=1 WHERE id=?")->execute([$id]);
    if ($action === 'approve')      $pdo->prepare("UPDATE products SET status='active' WHERE id=?")->execute([$id]);
    header("Location: ads.php?saved=1"); exit();
}

$filter = $_GET['filter'] ?? 'all';
$where  = $filter === 'online' ? "WHERE p.is_online=1" : ($filter === 'reported' ? "WHERE p.id IN (SELECT product_id FROM reports)" : "WHERE p.status != 'deleted'");

$ads = $pdo->query("SELECT p.*, u.name AS seller_name, c.name AS cat_name FROM products p JOIN users u ON p.seller_id=u.id JOIN categories c ON p.category_id=c.id $where ORDER BY p.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ads Management – Admin – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>Ads Management</h1>
    <div style="display:flex;gap:8px;">
      <a href="ads.php" class="btn btn-sm <?php echo $filter==='all'?'btn-primary':'btn-ghost'; ?>">All</a>
      <a href="ads.php?filter=online" class="btn btn-sm <?php echo $filter==='online'?'btn-warning':'btn-ghost'; ?>"><i class="fas fa-globe"></i> Online Only</a>
      <a href="ads.php?filter=reported" class="btn btn-sm <?php echo $filter==='reported'?'btn-danger':'btn-ghost'; ?>"><i class="fas fa-flag"></i> Reported</a>
    </div>
  </div>
  <?php if (!empty($_GET['saved'])): ?><div class="alert alert-success"><i class="fas fa-check"></i> Action applied.</div><?php endif; ?>

  <div class="card" style="padding:0;overflow:hidden;">
    <table class="data-table">
      <thead>
        <tr><th>ID</th><th>Title</th><th>Seller</th><th>Category</th><th>Price</th><th>Online</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($ads as $ad): ?>
        <tr>
          <td style="color:var(--text-3);font-size:.8rem;">#<?php echo $ad['id']; ?></td>
          <td><a href="/olx1/product.php?id=<?php echo $ad['id']; ?>" target="_blank" style="font-weight:600;color:var(--text);"><?php echo h($ad['title']); ?></a></td>
          <td><?php echo h($ad['seller_name']); ?></td>
          <td><?php echo h($ad['cat_name']); ?></td>
          <td style="font-weight:700;">Rs <?php echo number_format($ad['price']); ?></td>
          <td>
            <?php if ($ad['is_online']): ?>
              <span class="badge badge-online"><i class="fas fa-globe"></i> Yes</span>
            <?php else: ?>
              <span style="color:var(--text-3);">No</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-<?php echo $ad['status']==='active'?'active':($ad['status']==='sold'?'sold':'new'); ?>"><?php echo ucfirst($ad['status']); ?></span></td>
          <td>
            <form method="POST" style="display:inline;"><input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
              <?php if (!$ad['is_online']): ?><button name="action" value="force_online" class="btn btn-warning btn-sm" title="Force Online"><i class="fas fa-globe"></i></button><?php endif; ?>
              <button name="action" value="delete" class="btn btn-danger btn-sm" title="Remove"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
</div>
</body>
</html>
