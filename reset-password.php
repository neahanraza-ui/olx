<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

if (isLoggedIn()) { header("Location: dashboard.php"); exit(); }

$token = sanitizeInput($_GET['token'] ?? '');
$error = $success = '';

// Validate token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$token || !$reset) {
    $error = "This reset link is invalid or has expired. <a href='forgot-password.php'>Request a new one</a>.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $reset['email']]);
        $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);
        $success = "Password updated! <a href='login.php'>Sign in now</a>.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="logo" style="margin-bottom:28px;justify-content:center;">
      <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
    </div>
    <h1>Reset Password</h1>
    <p class="subtitle">Enter your new password below.</p>

    <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>

    <?php if ($reset && !$success): ?>
    <form method="POST">
      <input type="hidden" name="token" value="<?php echo h($token); ?>">
      <div class="form-group">
        <label>New Password</label>
        <input class="form-control" type="password" name="password" required placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input class="form-control" type="password" name="confirm" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-lock"></i> Update Password
      </button>
    </form>
    <?php endif; ?>

    <p class="auth-footer"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
  </div>
</div>
</body>
</html>
