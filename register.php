<?php
// register.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/lang.php';

if (isLoggedIn()) { header("Location: dashboard.php"); exit(); }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitizeInput($_POST['name'] ?? '');
    $email    = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password) {
        $error = __('err_fields_required');
    } elseif ($password !== $confirm) {
        $error = __('err_passwords_match');
    } elseif (strlen($password) < 8) {
        $error = __('err_password_min');
    } else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = __('err_email_exists');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");
            $ins->execute([$name, $email, $hash]);
            $_SESSION['user_id']   = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin']  = 0;
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h(__('create_account')); ?> – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="logo" style="margin-bottom:28px;">
      <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
    </div>
    <h1><?php echo h(__('create_account')); ?></h1>
    <p class="subtitle"><?php echo h(__('register_sub')); ?></p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label><?php echo h(__('full_name')); ?></label>
        <input class="form-control" type="text" name="name" required autofocus placeholder="Ahmad Ali" value="<?php echo h($_POST['name'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label><?php echo h(__('email_address')); ?></label>
        <input class="form-control" type="email" name="email" required placeholder="you@example.com" value="<?php echo h($_POST['email'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label><?php echo h(__('password')); ?></label>
        <input class="form-control" type="password" name="password" required placeholder="<?php echo h(__('password_min_ph')); ?>">
      </div>
      <div class="form-group">
        <label><?php echo h(__('confirm_password')); ?></label>
        <input class="form-control" type="password" name="confirm" required placeholder="<?php echo h(__('repeat_password_ph')); ?>">
      </div>
      <p style="font-size:.8rem;color:var(--text-3);margin-bottom:20px;">
        <?php echo h(__('by_creating')); ?> <a href="#"><?php echo h(__('terms')); ?></a> <?php echo h(__('and')); ?> <a href="#"><?php echo h(__('privacy')); ?></a><?php echo ($current_lang === 'ur') ? ' سے اتفاق کرتے ہیں۔' : '.'; ?>
      </p>
      <button type="submit" class="btn btn-primary btn-block btn-lg"><?php echo h(__('create_account_btn')); ?></button>
    </form>

    <p class="auth-footer">
      <?php echo h(__('already_account')); ?> <a href="login.php"><?php echo h(__('sign_in_link')); ?></a>
    </p>
  </div>
</div>
</body>
</html>
