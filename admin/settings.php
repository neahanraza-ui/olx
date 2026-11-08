<?php
require_once 'includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['online_keywords', 'online_popup_text', 'receipt_disclaimer'];
    foreach ($keys as $k) {
        $val = sanitizeInput($_POST[$k] ?? '');
        $chk = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $chk->execute([$k]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([$val, $k]);
        } else {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)")->execute([$k, $val]);
        }
    }
    header("Location: settings.php?saved=1"); exit();
}

$settings = [];
$rows = $pdo->query("SELECT * FROM settings")->fetchAll();
foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings – Admin – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar"><h1>Platform Settings</h1></div>
  <?php if (!empty($_GET['saved'])): ?><div class="alert alert-success"><i class="fas fa-check"></i> Settings saved successfully.</div><?php endif; ?>

  <form method="POST">
    <div class="settings-grid">

      <div class="card">
        <h3 style="margin-bottom:6px;"><i class="fas fa-globe" style="color:var(--warning);margin-right:6px;"></i> Online Keywords</h3>
        <p style="font-size:.8125rem;color:var(--text-3);margin-bottom:16px;">Comma-separated keywords that auto-detect "online delivery" listings and trigger the safety popup.</p>
        <div class="form-group">
          <label>Keyword List</label>
          <textarea class="form-control" name="online_keywords" rows="4" placeholder="e.g. online, delivery, ship, courier, send, transfer, paytm"><?php echo h($settings['online_keywords'] ?? ''); ?></textarea>
        </div>
      </div>

      <div class="card">
        <h3 style="margin-bottom:6px;"><i class="fas fa-exclamation-triangle" style="color:var(--warning);margin-right:6px;"></i> Online Warning Popup</h3>
        <p style="font-size:.8125rem;color:var(--text-3);margin-bottom:16px;">Text shown in the mandatory popup before a user can post or view an Online listing.</p>
        <div class="form-group">
          <label>Popup Text</label>
          <textarea class="form-control" name="online_popup_text" rows="6" placeholder="Warning message…"><?php echo h($settings['online_popup_text'] ?? ''); ?></textarea>
        </div>
      </div>

      <div class="card" style="grid-column:1/-1;">
        <h3 style="margin-bottom:6px;"><i class="fas fa-file-contract" style="color:var(--primary);margin-right:6px;"></i> Receipt Disclaimer</h3>
        <p style="font-size:.8125rem;color:var(--text-3);margin-bottom:16px;">Shown at the bottom of every generated receipt. Include your platform's liability disclaimer here.</p>
        <div class="form-group">
          <textarea class="form-control" name="receipt_disclaimer" rows="4"><?php echo h($settings['receipt_disclaimer'] ?? ''); ?></textarea>
        </div>
      </div>

    </div>
    <div style="margin-top:20px;">
      <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Settings</button>
    </div>
  </form>
</main>
</div>
</body>
</html>
