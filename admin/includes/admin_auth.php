<?php
// admin/includes/admin_auth.php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/security.php';

if (!isLoggedIn() || empty($_SESSION['is_admin'])) {
    header("Location: /olx1/admin/login.php");
    exit();
}

function requireAdmin() {
    if (!isLoggedIn() || empty($_SESSION['is_admin'])) {
        header("Location: /olx1/admin/login.php");
        exit();
    }
}
?>
