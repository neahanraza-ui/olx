<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
requireLogin();

$uid = getCurrentUserId();

// ─── Fetch user ───
$user = $pdo->prepare("SELECT * FROM users WHERE id=? AND deleted_at IS NULL");
$user->execute([$uid]);
$user = $user->fetch();
if (!$user) { session_destroy(); header("Location: login.php"); exit(); }

// ─── Profile update ───
$profile_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    $name  = sanitizeInput($_POST['name']  ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $bio   = sanitizeInput($_POST['bio']   ?? '');

    $avatar_path = $user['avatar_path'];

    // Avatar upload
    if (!empty($_FILES['avatar']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed) && $_FILES['avatar']['size'] < 2*1024*1024) {
            $dir = 'assets/uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $filename)) {
                $avatar_path = $dir . $filename;
            }
        }
    }

    $pdo->prepare("UPDATE users SET name=?, phone=?, bio=?, avatar_path=? WHERE id=?")
        ->execute([$name, $phone, $bio, $avatar_path, $uid]);
    $_SESSION['user_name']  = $name;
    $_SESSION['avatar_path'] = $avatar_path;
    $user['name'] = $name; $user['phone'] = $phone; $user['bio'] = $bio; $user['avatar_path'] = $avatar_path;
    $profile_msg = 'Profile updated!';
}

// ─── Stats ───
$total_ads   = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id=? AND status!='deleted'")->execute([$uid]) ? $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id=? AND status!='deleted'"): null;
// simpler approach:
$cnt = function($q,$p=[]) use ($pdo){ $s=$pdo->prepare($q);$s->execute($p);return $s->fetchColumn(); };
$stat_total    = $cnt("SELECT COUNT(*) FROM products WHERE seller_id=? AND status!='deleted'",[$uid]);
$stat_active   = $cnt("SELECT COUNT(*) FROM products WHERE seller_id=? AND status='active'",[$uid]);
$stat_sold     = $cnt("SELECT COUNT(*) FROM products WHERE seller_id=? AND status='sold'",[$uid]);
$stat_receipts = $cnt("SELECT COUNT(*) FROM receipts WHERE buyer_id=? OR seller_id=?",[$uid,$uid]);
$stat_saved    = $cnt("SELECT COUNT(*) FROM favorites WHERE user_id=?",[$uid]);

// ─── Ads ───
$active_ads = $pdo->prepare("SELECT p.*,c.name AS cat FROM products p JOIN categories c ON p.category_id=c.id WHERE p.seller_id=? AND p.status='active' ORDER BY p.created_at DESC");
$active_ads->execute([$uid]); $active_ads=$active_ads->fetchAll();

$all_ads = $pdo->prepare("SELECT p.*,c.name AS cat FROM products p JOIN categories c ON p.category_id=c.id WHERE p.seller_id=? AND p.status!='deleted' ORDER BY p.created_at DESC");
$all_ads->execute([$uid]); $all_ads=$all_ads->fetchAll();

// ─── Saved ads ───
$saved_ads = $pdo->prepare("SELECT p.*,u.name AS seller_name,u.is_verified,(SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id AND pi.is_main=1 LIMIT 1) AS main_image FROM favorites f JOIN products p ON f.product_id=p.id JOIN users u ON p.seller_id=u.id WHERE f.user_id=? AND p.status='active' ORDER BY f.created_at DESC");
$saved_ads->execute([$uid]); $saved_ads=$saved_ads->fetchAll();

// ─── Receipts ───
$receipts = $pdo->prepare("SELECT r.*,p.title AS product_title,ub.name AS buyer_name,us.name AS seller_name FROM receipts r JOIN products p ON r.product_id=p.id JOIN users ub ON r.buyer_id=ub.id JOIN users us ON r.seller_id=us.id WHERE r.buyer_id=? OR r.seller_id=? ORDER BY r.created_at DESC");
$receipts->execute([$uid,$uid]); $receipts=$receipts->fetchAll();

// ─── Verification status ───
$verif = $pdo->prepare("SELECT * FROM user_verifications WHERE user_id=? ORDER BY submitted_at DESC LIMIT 1");
$verif->execute([$uid]); $verif=$verif->fetch();

$active_tab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="dashboard-layout">

<!-- ─── Sidebar ─── -->
<aside class="dash-sidebar">
  <div class="dash-user-card" style="text-align:center;padding:20px 16px;border-bottom:1px solid var(--border);margin-bottom:14px;">
    <?php if ($user['avatar_path'] && file_exists($user['avatar_path'])): ?>
      <img src="<?php echo h($user['avatar_path']); ?>" alt="Avatar" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);margin-bottom:8px;">
    <?php else: ?>
      <div style="width:64px;height:64px;border-radius:50%;background:var(--primary-dim);display:flex;align-items:center;justify-content:center;margin:0 auto 8px;"><i class="fas fa-user" style="font-size:1.8rem;color:var(--primary);"></i></div>
    <?php endif; ?>
    <div style="font-weight:700;"><?php echo h($user['name']); ?></div>
    <div style="font-size:.75rem;color:var(--text-3);margin-top:2px;">
      Trust Score:
      <strong style="color:var(--primary);"><?php echo $user['trust_score']; ?></strong>
    </div>
    <?php if ($user['is_verified']): ?>
      <span class="badge badge-verified mt-1"><i class="fas fa-circle-check"></i> Verified</span>
    <?php else: ?>
      <a href="verify.php" style="display:block;margin-top:8px;font-size:.75rem;color:var(--warning);"><i class="fas fa-shield-alt"></i> Get Verified</a>
    <?php endif; ?>
  </div>

  <?php
  $tabs = [
    'overview' => ['fa-th-large','Overview'],
    'ads'      => ['fa-list','My Ads'],
    'saved'    => ['fa-heart','Saved Ads'],
    'chats'    => ['fa-comment-dots','Chats'],
    'receipts' => ['fa-file-alt','Receipts'],
    'profile'  => ['fa-user-edit','Profile'],
    'settings' => ['fa-cog','Settings'],
  ];
  foreach ($tabs as $key => [$icon, $label]):
  ?>
    <a href="?tab=<?php echo $key; ?>" class="sidebar-link <?php echo $active_tab===$key?'active':''; ?>">
      <i class="fas <?php echo $icon; ?>"></i> <?php echo $label; ?>
    </a>
  <?php endforeach; ?>

  <?php if ($user['is_admin']): ?>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
      <a href="/olx1/admin/dashboard.php" class="sidebar-link" style="color:var(--warning);">
        <i class="fas fa-user-shield"></i> Admin Panel
      </a>
    </div>
  <?php endif; ?>
</aside>

<!-- ─── Main ─── -->
<main class="dash-main">

<?php if ($active_tab === 'overview'): ?>
  <h1>Welcome back, <?php echo h(explode(' ',$user['name'])[0]); ?>!</h1>

  <!-- Verification CTA -->
  <?php if (!$user['is_verified'] && !$verif): ?>
  <div class="alert alert-warning" style="margin-bottom:24px;">
    <i class="fas fa-shield-alt"></i>
    <div>
      <strong>Get Verified</strong> — Unlock a trust badge, higher search ranking, and more ad perks.
      <a href="verify.php" class="btn btn-warning btn-sm" style="margin-left:12px;">Get Verified →</a>
    </div>
  </div>
  <?php elseif ($verif && $verif['status']==='pending'): ?>
  <div class="alert" style="background:var(--primary-dim);border:1px solid rgba(59,130,246,.3);margin-bottom:24px;">
    <i class="fas fa-clock" style="color:var(--primary);"></i>
    <div>Your verification is <strong>under review</strong>. We'll notify you shortly.</div>
  </div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="card stat-card">
      <div class="stat-icon" style="background:var(--primary-dim);color:var(--primary);"><i class="fas fa-list"></i></div>
      <div><div class="stat-val"><?php echo $stat_total; ?></div><div class="stat-lbl">Total Ads</div></div>
    </div>
    <div class="card stat-card">
      <div class="stat-icon" style="background:var(--success-dim);color:var(--success);"><i class="fas fa-check-circle"></i></div>
      <div><div class="stat-val"><?php echo $stat_active; ?></div><div class="stat-lbl">Active</div></div>
    </div>
    <div class="card stat-card">
      <div class="stat-icon" style="background:var(--danger-dim);color:var(--danger);"><i class="fas fa-tag"></i></div>
      <div><div class="stat-val"><?php echo $stat_sold; ?></div><div class="stat-lbl">Sold</div></div>
    </div>
    <div class="card stat-card">
      <div class="stat-icon" style="background:var(--warning-dim);color:var(--warning);"><i class="fas fa-file-alt"></i></div>
      <div><div class="stat-val"><?php echo $stat_receipts; ?></div><div class="stat-lbl">Receipts</div></div>
    </div>
    <div class="card stat-card">
      <div class="stat-icon" style="background:rgba(236,72,153,.12);color:#ec4899;"><i class="fas fa-heart"></i></div>
      <div><div class="stat-val"><?php echo $stat_saved; ?></div><div class="stat-lbl">Saved</div></div>
    </div>
  </div>

  <!-- Recent Active Ads -->
  <h2 style="font-size:1.1rem;margin:24px 0 12px;">Your Active Listings</h2>
  <?php if ($active_ads): ?>
  <div class="card" style="padding:0;overflow:hidden;">
    <table class="ads-table">
      <thead><tr><th></th><th>Title</th><th>Category</th><th>Price</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach (array_slice($active_ads,0,5) as $ad): ?>
        <tr>
          <td style="padding-left:16px;">
            <?php $img = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=? AND is_main=1 LIMIT 1"); $img->execute([$ad['id']]); $img=$img->fetchColumn(); ?>
            <div class="thumb"><?php if($img): ?><img src="<?php echo h($img); ?>" alt=""><?php endif; ?></div>
          </td>
          <td><span class="ad-title"><?php echo h($ad['title']); ?></span></td>
          <td style="color:var(--text-3);font-size:.8rem;"><?php echo h($ad['cat']); ?></td>
          <td style="font-weight:700;">Rs <?php echo number_format($ad['price']); ?></td>
          <td style="color:var(--text-3);"><?php echo $ad['view_count']; ?></td>
          <td><span class="badge badge-active">Active</span></td>
          <td class="ad-actions">
            <a href="product.php?id=<?php echo $ad['id']; ?>" class="btn btn-ghost btn-sm" target="_blank"><i class="fas fa-eye"></i></a>
            <button class="btn btn-warning btn-sm" onclick="pauseAd(<?php echo $ad['id']; ?>,'pause',this)"><i class="fas fa-pause"></i></button>
            <button class="btn btn-danger btn-sm" onclick="deleteAd(<?php echo $ad['id']; ?>,this)"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <a href="?tab=ads" class="btn btn-ghost btn-sm" style="margin-top:12px;">View all ads →</a>
  <?php else: ?>
    <div class="no-results"><i class="fas fa-box-open"></i>No active listings yet. <a href="post-ad.php">Post your first ad</a></div>
  <?php endif; ?>

<?php elseif ($active_tab === 'ads'): ?>
  <h1>My Ads</h1>
  <?php if ($all_ads): ?>
  <div class="card" style="padding:0;overflow:hidden;">
    <table class="ads-table">
      <thead><tr><th></th><th>Title</th><th>Price</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($all_ads as $ad): ?>
        <tr id="ad-row-<?php echo $ad['id']; ?>">
          <td style="padding-left:16px;">
            <?php $img=$pdo->prepare("SELECT image_path FROM product_images WHERE product_id=? AND is_main=1 LIMIT 1");$img->execute([$ad['id']]);$img=$img->fetchColumn(); ?>
            <div class="thumb"><?php if($img):?><img src="<?php echo h($img);?>" alt=""><?php endif;?></div>
          </td>
          <td><span class="ad-title"><?php echo h($ad['title']); ?></span></td>
          <td style="font-weight:700;">Rs <?php echo number_format($ad['price']); ?></td>
          <td><?php echo $ad['view_count']; ?></td>
          <td>
            <span id="status-<?php echo $ad['id']; ?>" class="badge badge-<?php echo $ad['status']==='active'?'active':($ad['status']==='sold'?'sold':'new'); ?>">
              <?php echo ucfirst($ad['status']); ?>
            </span>
          </td>
          <td class="ad-actions">
            <a href="product.php?id=<?php echo $ad['id'];?>" class="btn btn-ghost btn-sm" target="_blank"><i class="fas fa-eye"></i></a>
            <?php if ($ad['status']==='active'): ?>
              <button class="btn btn-warning btn-sm" onclick="pauseAd(<?php echo $ad['id']; ?>,'pause',this)"><i class="fas fa-pause"></i> Pause</button>
              <button class="btn btn-success btn-sm" onclick="markSold(<?php echo $ad['id']; ?>,this)"><i class="fas fa-check"></i> Sold</button>
            <?php elseif ($ad['status']==='paused'): ?>
              <button class="btn btn-primary btn-sm" onclick="pauseAd(<?php echo $ad['id']; ?>,'resume',this)"><i class="fas fa-play"></i> Resume</button>
            <?php endif; ?>
            <?php if ($ad['status']!=='sold'): ?>
              <button class="btn btn-danger btn-sm" onclick="deleteAd(<?php echo $ad['id']; ?>,this)"><i class="fas fa-trash"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="no-results"><i class="fas fa-box-open"></i>No ads yet. <a href="post-ad.php">Post your first ad</a></div>
  <?php endif; ?>

<?php elseif ($active_tab === 'saved'): ?>
  <h1>Saved Ads <span style="font-size:.9rem;color:var(--text-3);font-weight:400;">(<?php echo count($saved_ads); ?>)</span></h1>
  <?php if ($saved_ads): ?>
  <div class="products-grid">
    <?php foreach ($saved_ads as $ad): ?>
    <a href="product.php?id=<?php echo $ad['id'];?>" class="product-card">
      <div class="img-wrap">
        <?php if ($ad['main_image']):?><img src="<?php echo h($ad['main_image']);?>" alt=""><?php else:?><div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-3);"><i class="fas fa-image fa-2x"></i></div><?php endif;?>
        <?php if ($ad['is_online']):?><span class="badge badge-online"><i class="fas fa-globe"></i> Online</span><?php endif;?>
      </div>
      <div class="card-body">
        <div class="card-title"><?php echo h($ad['title']);?></div>
        <div class="card-price">Rs <?php echo number_format($ad['price']);?></div>
        <div class="card-meta">
          <span class="seller-badge"><?php echo h($ad['seller_name']);?><?php if($ad['is_verified']):?><i class="fas fa-circle-check verified"></i><?php endif;?></span>
          <button class="btn btn-danger btn-sm" style="font-size:.7rem;padding:3px 8px;" onclick="event.preventDefault();unsaveAd(<?php echo $ad['id'];?>,this)"><i class="fas fa-heart-broken"></i></button>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="no-results"><i class="fas fa-heart"></i>No saved ads yet. Tap ♥ on any listing to save it.</div>
  <?php endif; ?>

<?php elseif ($active_tab === 'chats'): ?>
  <h1>Chats</h1>
  <a href="chat.php" class="btn btn-primary"><i class="fas fa-comment-dots"></i> Open Chat</a>

<?php elseif ($active_tab === 'receipts'): ?>
  <h1>Receipts</h1>
  <?php if ($receipts): ?>
  <div class="card" style="padding:0;overflow:hidden;">
    <table class="ads-table">
      <thead><tr><th>Receipt ID</th><th>Product</th><th>With</th><th>Price</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($receipts as $r): ?>
        <tr>
          <td style="font-family:monospace;color:var(--primary);font-size:.8rem;"><?php echo h($r['receipt_uid']);?></td>
          <td><?php echo h($r['product_title']);?></td>
          <td><?php echo h($r['buyer_id']==$uid ? $r['seller_name'] : $r['buyer_name']);?></td>
          <td style="font-weight:700;">Rs <?php echo number_format($r['agreed_price']);?></td>
          <td style="color:var(--text-3);font-size:.8rem;"><?php echo date('d M Y',strtotime($r['created_at']));?></td>
          <td><a href="receipt.php?uid=<?php echo urlencode($r['receipt_uid']);?>" class="btn btn-ghost btn-sm" target="_blank"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="no-results"><i class="fas fa-file-alt"></i>No receipts yet.</div>
  <?php endif; ?>

<?php elseif ($active_tab === 'profile'): ?>
  <h1>Edit Profile</h1>
  <?php if ($profile_msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?php echo $profile_msg; ?></div><?php endif; ?>
  <div class="card" style="max-width:560px;">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="profile">

      <!-- Avatar -->
      <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;">
        <?php if ($user['avatar_path'] && file_exists($user['avatar_path'])): ?>
          <img src="<?php echo h($user['avatar_path']); ?>" id="avatarPreview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);">
        <?php else: ?>
          <div id="avatarPreview" style="width:80px;height:80px;border-radius:50%;background:var(--primary-dim);display:flex;align-items:center;justify-content:center;"><i class="fas fa-user" style="font-size:2rem;color:var(--primary);"></i></div>
        <?php endif; ?>
        <div>
          <label class="btn btn-ghost btn-sm" style="cursor:pointer;"><i class="fas fa-camera"></i> Change Photo <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)"></label>
          <p style="font-size:.75rem;color:var(--text-3);margin-top:6px;">JPG/PNG/WEBP · Max 2MB</p>
        </div>
      </div>

      <div class="form-group">
        <label>Full Name</label>
        <input class="form-control" type="text" name="name" required value="<?php echo h($user['name']);?>">
      </div>
      <div class="form-group">
        <label>Email <span style="color:var(--text-3);font-size:.8rem;">(cannot change)</span></label>
        <input class="form-control" type="email" disabled value="<?php echo h($user['email']);?>">
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input class="form-control" type="text" name="phone" placeholder="+92 300 1234567" value="<?php echo h($user['phone']??'');?>">
      </div>
      <div class="form-group">
        <label>Bio <span style="color:var(--text-3);font-size:.8rem;">(shown on your profile)</span></label>
        <textarea class="form-control" name="bio" rows="3" maxlength="300" placeholder="Tell buyers a bit about yourself…"><?php echo h($user['bio']??'');?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </form>
  </div>

<?php elseif ($active_tab === 'settings'): ?>
  <h1>Settings</h1>
  <div class="card" style="max-width:560px;">
    <h3 style="margin-bottom:16px;"><i class="fas fa-bell" style="color:var(--primary);margin-right:8px;"></i> Notifications</h3>
    <p style="color:var(--text-3);font-size:.875rem;">Notification preferences will be available soon.</p>

    <div style="border-top:1px solid var(--border);margin:28px 0 24px;"></div>

    <h3 style="margin-bottom:16px;color:var(--danger);"><i class="fas fa-user-slash" style="margin-right:8px;"></i> Delete Account</h3>
    <div class="alert alert-danger" style="margin-bottom:16px;">
      <i class="fas fa-exclamation-triangle"></i>
      <div>This will permanently delete your account and all data. This action cannot be undone.</div>
    </div>
    <button class="btn btn-danger" onclick="confirmDeleteAccount()">
      <i class="fas fa-trash-alt"></i> Delete My Account
    </button>
  </div>

<?php endif; ?>
</main>
</div>

<?php include 'includes/cookie-banner.php'; ?>
<?php include 'includes/footer.php'; ?>

<script>
// ─── Avatar preview ───
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const p = document.getElementById('avatarPreview');
      if (p.tagName === 'IMG') p.src = e.target.result;
      else { const img = document.createElement('img'); img.src=e.target.result; img.id='avatarPreview'; img.style.cssText='width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);'; p.replaceWith(img); }
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ─── Pause / Resume ad ───
async function pauseAd(id, action, btn) {
  const r    = await fetch('/olx1/ajax/pause-ad.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}&action=${action}`});
  const data = await r.json();
  if (data.success) {
    const badge = document.getElementById('status-' + id);
    if (badge) { badge.textContent = data.status === 'active' ? 'Active' : 'Paused'; badge.className = 'badge badge-' + (data.status === 'active' ? 'active' : 'new'); }
    location.reload();
  }
}

// ─── Mark sold ───
async function markSold(id, btn) {
  if (!confirm('Mark this ad as sold?')) return;
  await fetch('/olx1/ajax/mark-sold.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}`});
  location.reload();
}

// ─── Delete ad ───
async function deleteAd(id, btn) {
  if (!confirm('Delete this ad?')) return;
  await fetch('/olx1/ajax/delete-ad.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}`});
  const row = document.getElementById('ad-row-' + id);
  if (row) row.remove(); else location.reload();
}

// ─── Unsave ad ───
async function unsaveAd(id, btn) {
  await fetch('/olx1/ajax/toggle-favorite.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`product_id=${id}`});
  btn.closest('.product-card')?.remove();
}

// ─── Delete account ───
function confirmDeleteAccount() {
  if (!confirm('⚠️ Are you absolutely sure? All your data will be permanently deleted.')) return;
  if (!confirm('Last chance — this cannot be undone. Continue?')) return;
  fetch('/olx1/ajax/delete-account.php', {method:'POST'}).then(() => { window.location = '/olx1/'; });
}
</script>
</body>
</html>
