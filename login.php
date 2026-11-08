<?php
// login.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/lang.php';

if (isLoggedIn()) { header("Location: dashboard.php"); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['is_admin']   = $user['is_admin'] ?? 0;
        $_SESSION['is_verified']= $user['is_verified'] ?? 0;
        $_SESSION['avatar_path']= $user['avatar_path'] ?? null;
        $redir = $_GET['redirect'] ?? 'index.php';
        header("Location: " . $redir);
        exit();
    } else {
        $error = __('invalid_credentials');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo __('sign_in_btn'); ?> – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="logo" style="margin-bottom:28px;">
      <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
    </div>
    <h1><?php echo h(__('welcome_back')); ?></h1>
    <p class="subtitle"><?php echo h(__('sign_in_sub')); ?></p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label><?php echo h(__('email_address')); ?></label>
        <input class="form-control" type="email" name="email" required autofocus placeholder="you@example.com" value="<?php echo h($_POST['email'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label><?php echo h(__('password')); ?></label>
        <input class="form-control" type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg"><?php echo h(__('sign_in_btn')); ?></button>
      <div style="text-align:right;margin-top:10px;">
        <a href="forgot-password.php" style="font-size:.8125rem;color:var(--text-3);"><?php echo h(__('forgot_password')); ?></a>
      </div>
    </form>

    <p class="auth-footer">
      <?php echo h(__('no_account')); ?> <a href="register.php"><?php echo h(__('create_free')); ?></a>
    </p>
  </div>
</div>
</body>
</html>
