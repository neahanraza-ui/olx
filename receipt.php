<?php
// receipt.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/settings.php';

requireLogin();
validateSession($pdo);

$uid        = $_GET['uid'] ?? '';
$product_id = $_GET['product_id'] ?? 0;
$user_id    = getCurrentUserId();

if ($product_id) {
    // Fetch from new transactions table (Online Win System)
    $stmt = $pdo->prepare("
      SELECT t.*, p.title AS product_title, p.description AS product_desc,
             ub.name AS buyer_name, us.name AS seller_name,
             t.id AS receipt_uid, t.created_at, '' as meeting_location
      FROM transactions t
      JOIN products p ON t.product_id = p.id
      JOIN users ub ON t.buyer_id = ub.id
      JOIN users us ON t.seller_id = us.id
      WHERE t.product_id = ? AND (t.buyer_id = ? OR t.seller_id = ?) AND t.status = 'confirmed'
      LIMIT 1
    ");
    $stmt->execute([$product_id, $user_id, $user_id]);
    $r = $stmt->fetch();
} else {
    // Fetch from old receipts table (Deals System)
    $stmt = $pdo->prepare("
      SELECT r.*, p.title AS product_title, p.description AS product_desc,
             ub.name AS buyer_name, us.name AS seller_name,
             r.receipt_uid, r.created_at
      FROM receipts r
      JOIN products p ON r.product_id = p.id
      JOIN users ub ON r.buyer_id = ub.id
      JOIN users us ON r.seller_id = us.id
      WHERE r.receipt_uid = ? AND (r.buyer_id = ? OR r.seller_id = ?)
    ");
    $stmt->execute([$uid, $user_id, $user_id]);
    $r = $stmt->fetch();
}

if (!$r) {
    header("Location: dashboard.php?tab=receipts");
    exit();
}
$uid = $r['receipt_uid'];

$disclaimer = getSetting('receipt_disclaimer') ?: 'This receipt is a mutual agreement record between buyer and seller. OLX Plus is not responsible for any disputes arising from this transaction. Always meet in safe public locations.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt <?php echo h($uid); ?> – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
      .main-header { display: none !important; }
    }
  </style>
</head>
<body>
<div class="no-print"><?php include 'includes/header.php'; ?></div>

<div class="container" style="padding-top:40px;padding-bottom:80px;">
  <div class="receipt-wrap">

    <!-- Actions (no-print) -->
    <div class="receipt-print-actions no-print">
      <a href="dashboard.php?tab=receipts" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
      <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print Receipt</button>
    </div>

    <!-- Receipt Card -->
    <div class="receipt-card" style="position:relative;margin-top:20px;">
      <div class="receipt-watermark">CONFIRMED</div>

      <!-- Header -->
      <div class="receipt-top">
        <div>
          <div class="logo">OLX<span>Plus</span></div>
          <div style="font-size:.8125rem;color:var(--text-3);margin-top:6px;">Mutual Transaction Receipt</div>
        </div>
        <div class="receipt-id">
          <div style="font-size:.75rem;color:var(--text-3);margin-bottom:4px;">RECEIPT ID</div>
          <div class="id-val"><?php echo h($uid); ?></div>
          <div class="date"><?php echo date('d M Y, H:i', strtotime($r['created_at'])); ?></div>
        </div>
      </div>

      <!-- Body -->
      <div class="receipt-body">

        <div class="receipt-status">
          <span class="status-badge"><i class="fas fa-check-circle"></i> Deal Confirmed</span>
        </div>

        <table class="receipt-table">
          <tr>
            <td>Product</td>
            <td><?php echo h($r['product_title']); ?></td>
          </tr>
          <tr>
            <td>Description</td>
            <td style="color:#64748b;font-size:.875rem;"><?php echo h(mb_strimwidth($r['product_desc'], 0, 120, '…')); ?></td>
          </tr>
          <tr><td colspan="2" style="padding:8px 0;border:none;"></td></tr>
          <tr>
            <td>Buyer</td>
            <td><?php echo h($r['buyer_name']); ?></td>
          </tr>
          <tr>
            <td>Seller</td>
            <td><?php echo h($r['seller_name']); ?></td>
          </tr>
          <tr><td colspan="2" style="padding:8px 0;border:none;"></td></tr>
          <?php if (!empty($r['meeting_location'])): ?>
          <tr>
            <td>Meeting Location</td>
            <td><?php echo h($r['meeting_location']); ?></td>
          </tr>
          <?php endif; ?>
          <tr class="receipt-price-row">
            <td>Agreed Price</td>
            <td>Rs <?php echo number_format($r['agreed_price'], 2); ?></td>
          </tr>
        </table>

        <div class="receipt-footer-disclaimer">
          <strong style="color:#92400e;"><i class="fas fa-shield-alt"></i> Platform Disclaimer:</strong><br>
          <?php echo h($disclaimer); ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
