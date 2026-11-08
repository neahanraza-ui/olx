<?php
require_once 'includes/admin_auth.php';

// Stats
$stats = [
  'users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
  'ads'      => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
  'online'   => $pdo->query("SELECT COUNT(*) FROM products WHERE is_online=1")->fetchColumn(),
  'receipts' => $pdo->query("SELECT COUNT(*) FROM receipts")->fetchColumn(),
  'reports'  => $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
];

// Recent ads
$recent_ads = $pdo->query("SELECT p.*, u.name AS seller_name FROM products p JOIN users u ON p.seller_id=u.id ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

// Recent users
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>Overview</h1>
    <span style="color:var(--text-3);font-size:.875rem;"><?php echo date('l, d F Y'); ?></span>
  </div>

  <!-- Stats Cards -->
  <div class="admin-stats">
    <div class="card admin-stat">
      <div class="val" style="color:var(--primary);"><?php echo number_format($stats['users']); ?></div>
      <div class="lbl"><i class="fas fa-users"></i> Total Users</div>
    </div>
    <div class="card admin-stat">
      <div class="val" style="color:var(--success);"><?php echo number_format($stats['ads']); ?></div>
      <div class="lbl"><i class="fas fa-th-list"></i> Total Ads</div>
    </div>
    <div class="card admin-stat">
      <div class="val" style="color:var(--warning);"><?php echo number_format($stats['online']); ?></div>
      <div class="lbl"><i class="fas fa-globe"></i> Online Ads</div>
    </div>
    <div class="card admin-stat">
      <div class="val" style="color:var(--primary);"><?php echo number_format($stats['receipts']); ?></div>
      <div class="lbl"><i class="fas fa-file-contract"></i> Receipts</div>
    </div>
    <div class="card admin-stat">
      <div class="val" style="color:var(--danger);"><?php echo number_format($stats['reports']); ?></div>
      <div class="lbl"><i class="fas fa-flag"></i> Pending Reports</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">

    <!-- Recent Ads -->
    <div class="card" style="padding:0;overflow:hidden;">
      <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3>Recent Ads</h3>
        <a href="ads.php" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <table class="data-table">
        <thead><tr><th>ID</th><th>Title</th><th>Seller</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($recent_ads as $ad): ?>
          <tr>
            <td style="color:var(--text-3);font-size:.8rem;">#<?php echo $ad['id']; ?></td>
            <td style="font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo h($ad['title']); ?></td>
            <td><?php echo h($ad['seller_name']); ?></td>
            <td><?php echo $ad['is_online'] ? '<span class="badge badge-online"><i class="fas fa-globe"></i> Online</span>' : 'Offline'; ?></td>
            <td><span class="badge badge-<?php echo $ad['status']==='active'?'active':($ad['status']==='sold'?'sold':'new'); ?>"><?php echo ucfirst($ad['status']); ?></span></td>
            <td><div class="table-actions">
              <a href="/olx1/product.php?id=<?php echo $ad['id']; ?>" class="btn btn-ghost btn-sm" target="_blank"><i class="fas fa-eye"></i></a>
              <button class="btn btn-danger btn-sm" onclick="adminDeleteAd(<?php echo $ad['id']; ?>)"><i class="fas fa-ban"></i></button>
            </div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent Users -->
    <div class="card" style="padding:0;overflow:hidden;">
      <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3>New Users</h3>
        <a href="users.php" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <div>
        <?php foreach ($recent_users as $u): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--border);">
          <div style="width:38px;height:38px;border-radius:50%;background:var(--primary-dim);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;">
            <?php echo strtoupper(mb_substr($u['name'],0,1)); ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:.875rem;"><?php echo h($u['name']); ?></div>
            <div style="font-size:.75rem;color:var(--text-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo h($u['email']); ?></div>
          </div>
          <?php if ($u['is_verified']): ?><span class="badge badge-verified"><i class="fas fa-check"></i></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>
</div>
<script>
async function adminDeleteAd(id) {
  if (!confirm('Suspend/delete this ad?')) return;
  const fd = new FormData(); fd.append('id', id);
  await fetch('/olx1/ajax/delete-ad.php', { method:'POST', body:fd });
  location.reload();
}
</script>
</body>
</html>
