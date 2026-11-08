<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

if (isLoggedIn() && !empty($_SESSION['is_admin'])) { header("Location: dashboard.php"); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stmt  = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
    $stmt->execute([$email]);
    $user  = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = 1;
        header("Location: dashboard.php"); exit();
    } else { $error = "Invalid admin credentials."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div style="text-align:center;margin-bottom:28px;">
      <div class="logo" style="justify-content:center;">
        <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
      </div>
      <span class="admin-badge" style="margin-top:6px;display:inline-block;">ADMIN PANEL</span>
    </div>
    <h1>Admin Login</h1>
    <p class="subtitle">Restricted area — authorized personnel only</p>
    <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-lock"></i> <?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Admin Email</label>
        <input class="form-control" type="email" name="email" required autofocus placeholder="admin@olxplus.com">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-sign-in-alt"></i> Access Admin Panel</button>
    </form>
    <p class="auth-footer"><a href="/olx1/login.php">← Return to main site</a></p>
  </div>
</div>
</body>
</html>
