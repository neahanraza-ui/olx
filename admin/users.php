<?php
require_once 'includes/admin_auth.php';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'verify')  $pdo->prepare("UPDATE users SET is_verified=1 WHERE id=?")->execute([$id]);
    if ($action === 'suspend') $pdo->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$id]);
    if ($action === 'activate')$pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
    header("Location: users.php?saved=1"); exit();
}

$search = sanitizeInput($_GET['s'] ?? '');
$where  = $search ? "WHERE name LIKE ? OR email LIKE ?" : "";
$stmt   = $pdo->prepare("SELECT u.*,(SELECT COUNT(*) FROM products WHERE seller_id=u.id) AS ads_count FROM users u $where ORDER BY u.created_at DESC");
$search ? $stmt->execute(["%$search%","%$search%"]) : $stmt->execute();
$users  = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users – Admin – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>User Management</h1>
    <form method="GET" style="display:flex;gap:10px;">
      <input class="form-control" type="text" name="s" placeholder="Search name or email…" value="<?php echo h($search); ?>" style="width:260px;">
      <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
    </form>
  </div>

  <?php if (!empty($_GET['saved'])): ?><div class="alert alert-success"><i class="fas fa-check"></i> Action applied.</div><?php endif; ?>

  <div class="card" style="padding:0;overflow:hidden;">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Email</th><th>Ads</th>
          <th>Trust</th><th>Status</th><th>Joined</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:var(--text-3);font-size:.8rem;">#<?php echo $u['id']; ?></td>
          <td>
            <strong><?php echo h($u['name']); ?></strong>
            <?php if ($u['is_admin']): ?><span class="badge badge-online" style="margin-left:4px;">Admin</span><?php endif; ?>
            <?php if ($u['is_verified']): ?><i class="fas fa-circle-check" style="color:var(--primary);margin-left:4px;font-size:.8rem;"></i><?php endif; ?>
          </td>
          <td style="color:var(--text-3);font-size:.875rem;"><?php echo h($u['email']); ?></td>
          <td><?php echo $u['ads_count']; ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div class="score-bar" style="width:60px;">
                <div class="score-fill" style="width:<?php echo $u['trust_score'] ?? 50; ?>%"></div>
              </div>
              <span style="font-size:.8rem;"><?php echo $u['trust_score'] ?? 50; ?></span>
            </div>
          </td>
          <td>
            <span class="badge badge-<?php echo ($u['status'] ?? 'active')==='active' ? 'active' : 'sold'; ?>">
              <?php echo ucfirst($u['status'] ?? 'active'); ?>
            </span>
          </td>
          <td style="color:var(--text-3);font-size:.8rem;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
          <td>
            <div class="table-actions">
              <form method="POST" style="display:inline">
                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                <?php if (!$u['is_verified']): ?>
                  <button name="action" value="verify" class="btn btn-primary btn-sm" title="Verify"><i class="fas fa-check"></i></button>
                <?php endif; ?>
                <?php if (($u['status'] ?? 'active') === 'active'): ?>
                  <button name="action" value="suspend" class="btn btn-danger btn-sm" title="Suspend"><i class="fas fa-ban"></i></button>
                <?php else: ?>
                  <button name="action" value="activate" class="btn btn-success btn-sm" title="Activate"><i class="fas fa-play"></i></button>
                <?php endif; ?>
              </form>
            </div>
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
