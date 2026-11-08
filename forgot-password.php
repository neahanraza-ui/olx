<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/lang.php';

if (isLoggedIn()) { header("Location: dashboard.php"); exit(); }

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show success to prevent email enumeration
    if ($user) {
        $token     = bin2hex(random_bytes(32));
        $expires   = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)")
            ->execute([$email, $token, $expires]);

        // In production: send email. For dev, show link directly.
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $reset_link = $proto . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=$token";
        $msg = "dev_link:$reset_link"; // Dev mode — shown in UI
    }

    if (!$msg) $msg = __('reset_sent');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h(__('forgot_password_title')); ?> – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="logo" style="margin-bottom:28px;justify-content:center;">
      <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
    </div>
    <h1><?php echo h(__('forgot_password_title')); ?></h1>
    <p class="subtitle"><?php echo h(__('forgot_password_sub')); ?></p>

    <?php if ($msg): ?>
      <?php if (str_starts_with($msg, 'dev_link:')): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <div>
            <strong><?php echo h(__('dev_mode_link')); ?></strong><br>
            <a href="<?php echo substr($msg, 9); ?>" style="word-break:break-all;font-size:.8rem;">
              <?php echo h(substr($msg, 9)); ?>
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-success"><i class="fas fa-envelope"></i> <?php echo $msg; ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label><?php echo h(__('email_address')); ?></label>
        <input class="form-control" type="email" name="email" required autofocus
               placeholder="you@example.com" value="<?php echo h($_POST['email'] ?? ''); ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-paper-plane"></i> <?php echo h(__('send_reset_link')); ?>
      </button>
    </form>

    <p class="auth-footer"><a href="login.php"><i class="fas fa-arrow-left"></i> <?php echo h(__('back_to_login')); ?></a></p>
  </div>
</div>
</body>
</html>
