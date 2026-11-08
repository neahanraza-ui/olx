<?php
// admin/includes/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
function al($file, $icon, $label) {
    $active = basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
    echo "<a href='/olx1/admin/{$file}' class='sidebar-link {$active}'><i class='fas {$icon}'></i> {$label}</a>";
}
?>
<aside class="admin-side">
  <div class="logo" style="margin-bottom:24px;padding:0 8px;">
    <span style="color:#f1f5f9;font-weight:800;">OLX</span><span style="color:#3b82f6;">Plus</span>
    <span class="admin-badge">ADMIN</span>
  </div>
  <?php
    al('dashboard.php',  'fa-home',          'Overview');
    al('users.php',      'fa-users',         'Users');
    al('ads.php',        'fa-th-list',       'Ads');
    al('moderation.php', 'fa-shield-alt',    'Moderation');
    al('receipts.php',   'fa-file-contract', 'Receipts');
    al('settings.php',   'fa-sliders-h',     'Settings');
    al('logs.php',       'fa-history',       'Logs');
  ?>
  <div style="margin-top:auto;padding-top:24px;border-top:1px solid var(--border);">
    <a href="/olx1/index.php" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Site</a>
    <a href="/olx1/logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</aside>
