<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLX Plus</title>
</head>
<body>
<header class="main-header">
  <div class="container header-inner">
    <a href="/olx1/index.php" class="logo">
      <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
    </a>

    <form action="/olx1/search.php" method="GET" class="header-search">
      <input type="text" name="q" placeholder="<?php echo h(__('search_placeholder')); ?>" value="<?php echo h($_GET['q'] ?? ''); ?>">
      <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <nav class="header-nav">
      <!-- Language & Currency Switcher -->
      <div style="display:flex;gap:10px;margin-right:10px;align-items:center;">
          <div class="lang-switcher" style="display:flex;gap:5px;">
              <a href="?lang=en" class="btn btn-ghost btn-sm <?php echo $current_lang == 'en' ? 'active' : ''; ?>" style="padding:4px 8px;">EN</a>
              <a href="?lang=ur" class="btn btn-ghost btn-sm <?php echo $current_lang == 'ur' ? 'active' : ''; ?>" style="padding:4px 8px;">اردو</a>
          </div>
          <select id="currSelect" class="form-control" style="padding:4px 8px; font-size:12px; width:auto; border-radius:6px; background:var(--bg-3);" onchange="switchCurrency(this.value)">
              <option value="PKR" <?php echo getCurrentCurrency() == 'PKR' ? 'selected' : ''; ?>>PKR (Rs)</option>
              <option value="USD" <?php echo getCurrentCurrency() == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
              <option value="SAR" <?php echo getCurrentCurrency() == 'SAR' ? 'selected' : ''; ?>>SAR (SR)</option>
          </select>
          <script>
            async function switchCurrency(c) {
                await fetch('/olx1/ajax/set-currency.php?curr=' + c);
                window.location.reload();
            }
          </script>
      </div>

      <a href="/olx1/search.php?online=1" class="nav-online">
        <i class="fas fa-globe"></i> <?php echo h(__('online')); ?>
      </a>

      <?php if (isLoggedIn()): ?>
        <a href="/olx1/chat.php" title="<?php echo h(__('messages')); ?>" class="header-icon-btn">
          <i class="fas fa-comment-dots"></i>
        </a>

        <!-- Notification Bell -->
        <div class="notif-wrapper" id="notifWrapper">
          <button class="header-icon-btn" id="notifBtn" onclick="toggleNotifPanel(event)" title="<?php echo h(__('notifications')); ?>">
            <i class="fas fa-bell"></i>
            <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
          </button>
          <div class="notif-panel" id="notifPanel">
            <div class="notif-panel-head">
              <strong><?php echo h(__('notifications')); ?></strong>
              <button class="btn btn-ghost btn-sm" onclick="markAllRead()" style="font-size:.75rem;padding:4px 10px;">Mark all read</button>
            </div>
            <div class="notif-list" id="notifList">
              <div class="notif-empty"><i class="fas fa-bell-slash"></i> No notifications</div>
            </div>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
          <a href="/olx1/dashboard.php" class="header-user">
            <?php
              $avatar = $_SESSION['avatar_path'] ?? null;
              if ($avatar && file_exists(dirname(__DIR__) . '/' . $avatar)):
            ?>
              <img src="/olx1/<?php echo h($avatar); ?>" alt="avatar"
                   style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
            <?php else: ?>
              <i class="fas fa-user-circle" style="font-size:1.4rem;color:var(--text-3);"></i>
            <?php endif; ?>
            <span><?php echo h($_SESSION['user_name'] ?? __('my_account')); ?></span>
            <?php if (!empty($_SESSION['is_verified'])): ?>
              <i class="fas fa-circle-check" style="color:var(--primary);font-size:.8rem;" title="Verified"></i>
            <?php endif; ?>
          </a>
          <a href="/olx1/post-ad.php" class="btn btn-primary btn-sm">+ <?php echo h(__('post_ad')); ?></a>
          <a href="/olx1/logout.php" title="<?php echo h(__('logout')); ?>" style="color:var(--text-3);"><i class="fas fa-sign-out-alt"></i></a>
        </div>
      <?php else: ?>
        <a href="/olx1/login.php" class="btn btn-ghost btn-sm"><?php echo h(__('login')); ?></a>
        <a href="/olx1/register.php" class="btn btn-primary btn-sm"><?php echo h(__('join_free')); ?></a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<?php if (isLoggedIn()): ?>
<script>
// ─── Notification Bell ───
let notifOpen = false;

async function loadNotifications() {
  try {
    const r = await fetch('/olx1/ajax/get-notifications.php');
    const d = await r.json();
    const badge = document.getElementById('notifBadge');
    const list  = document.getElementById('notifList');
    
    if (d.unread_count > 0) {
      badge.textContent = d.unread_count > 9 ? '9+' : d.unread_count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
    
    if (d.notifications && d.notifications.length) {
      list.innerHTML = d.notifications.map(n => {
        const isRead = n.read_at !== null;
        let title = "Notification";
        let body = "";
        let link = "#";
        
        if (n.type === 'new_message') {
            title = `New message from ${n.data.sender_name}`;
            body = n.data.message_snippet;
            link = `/olx1/chat.php?conv_id=${n.data.conversation_id}`;
        } else if (n.type === 'deal_update') {
            title = "Deal Update";
            body = n.data.message;
            link = `/olx1/receipt.php?id=${n.data.receipt_id}`;
        }
        
        return `
        <a class="notif-item${isRead ? '' : ' unread'}" href="${link}">
          <span class="notif-dot" ${isRead ? 'style="opacity:0"' : ''}></span>
          <div>
            <div class="notif-title">${title}</div>
            <div class="notif-body">${body}</div>
            <div class="notif-time">${n.time_ago}</div>
          </div>
        </a>`;
      }).join('');
    } else {
      list.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i> No notifications</div>';
    }
  } catch(e) {}
}

function toggleNotifPanel(e) {
  e.stopPropagation();
  notifOpen = !notifOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifOpen);
  if (notifOpen) loadNotifications();
}

async function markAllRead() {
  await fetch('/olx1/ajax/mark-notifications-read.php', {method:'POST'});
  document.getElementById('notifBadge').style.display = 'none';
  document.querySelectorAll('.notif-item').forEach(el => {
    el.classList.remove('unread');
    if (el.querySelector('.notif-dot')) el.querySelector('.notif-dot').style.opacity = '0';
  });
}

document.addEventListener('click', (e) => {
  const panel = document.getElementById('notifPanel');
  const btn = document.getElementById('notifBtn');
  if (panel && !panel.contains(e.target) && !btn.contains(e.target)) {
    notifOpen = false;
    panel.classList.remove('open');
  }
});

// Poll every 30s
loadNotifications();
setInterval(loadNotifications, 30000);
</script>
<?php endif; ?>
