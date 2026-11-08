<?php
require_once 'includes/admin_auth.php';
$receipts = $pdo->query("SELECT r.*, p.title AS product_title, ub.name AS buyer_name, us.name AS seller_name FROM receipts r JOIN products p ON r.product_id=p.id JOIN users ub ON r.buyer_id=ub.id JOIN users us ON r.seller_id=us.id ORDER BY r.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipts – Admin – OLX Plus</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar"><h1>All Receipts</h1><span style="color:var(--text-3);"><?php echo count($receipts); ?> total confirmed deals</span></div>

  <div class="card" style="padding:0;overflow:hidden;">
    <table class="data-table">
      <thead><tr><th>Receipt ID</th><th>Product</th><th>Buyer</th><th>Seller</th><th>Price</th><th>Location</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($receipts as $r): ?>
        <tr>
          <td style="font-family:monospace;color:var(--primary);font-size:.8rem;"><?php echo h($r['receipt_uid']); ?></td>
          <td style="font-weight:600;"><?php echo h($r['product_title']); ?></td>
          <td><?php echo h($r['buyer_name']); ?></td>
          <td><?php echo h($r['seller_name']); ?></td>
          <td style="font-weight:700;">Rs <?php echo number_format($r['agreed_price']); ?></td>
          <td style="color:var(--text-3);font-size:.875rem;"><?php echo h($r['meeting_location']); ?></td>
          <td style="color:var(--text-3);font-size:.8rem;"><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></td>
          <td><a href="/olx1/receipt.php?uid=<?php echo urlencode($r['receipt_uid']); ?>" class="btn btn-ghost btn-sm" target="_blank"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
</div>
</body>
</html>
