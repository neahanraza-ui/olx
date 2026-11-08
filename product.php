<?php
// product.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/settings.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT p.*, c.name AS cat_name,
         u.name AS seller_name, u.is_verified, u.trust_score, u.created_at AS member_since,
         (SELECT COUNT(*) FROM products WHERE seller_id = u.id AND status='active') AS ads_count
  FROM products p
  JOIN categories c ON p.category_id = c.id
  JOIN users u ON p.seller_id = u.id
  WHERE p.id = ? AND p.status != 'deleted'
");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) { header("Location: index.php"); exit(); }

// Images
$imgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
$imgs->execute([$id]);
$images = $imgs->fetchAll();

// Increment views
$pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

// Reviews
$stmt = $pdo->prepare("SELECT r.*, u.name AS reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// Check if current user can review
$can_review = false;
$user_id = getCurrentUserId();
if ($user_id && !$is_own) {
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE product_id = ? AND buyer_id = ? AND status = 'confirmed' LIMIT 1");
    $stmt->execute([$id, $user_id]);
    if ($stmt->fetch()) {
        // Also check if already reviewed
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND reviewer_id = ?");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) {
            $can_review = true;
        }
    }
}

$warning_text = getSetting('online_popup_text');
$is_logged    = isLoggedIn();
$user_id      = getCurrentUserId();
$is_own       = ($user_id == $p['seller_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($p['title']); ?> – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="container" style="padding-top:16px;padding-bottom:6px;">
  <div style="font-size:.8125rem;color:var(--text-3);display:flex;gap:6px;align-items:center;">
    <a href="index.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
    <a href="search.php?category=<?php echo $p['category_id']; ?>"><?php echo h($p['cat_name']); ?></a>
    <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
    <span style="color:var(--text-2);"><?php echo h($p['title']); ?></span>
  </div>
</div>

<div class="container">
  <div class="product-detail-layout">

    <!-- Main Content -->
    <div class="product-detail-main">

      <!-- Gallery -->
      <div class="product-gallery">
        <div class="gallery-main" id="mainImg">
          <img id="mainImgEl"
               src="<?php echo !empty($images) ? h($images[0]['image_path']) : 'assets/images/placeholder.webp'; ?>"
               alt="<?php echo h($p['title']); ?>">
        </div>
        <?php if (count($images) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach ($images as $i => $img): ?>
              <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>" onclick="switchImg('<?php echo h($img['image_path']); ?>', this)">
                <img src="<?php echo h($img['image_path']); ?>" alt="">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Online Warning -->
      <?php if ($p['is_online']): ?>
        <div class="online-warning-box">
          <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
          <p><?php echo h($warning_text ?: 'This product involves online delivery. The platform is NOT responsible for any scams or disputes. Proceed at your own risk.'); ?></p>
        </div>
      <?php endif; ?>

      <!-- Info -->
      <div class="detail-section">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:10px;">
            <h1 class="detail-title" id="adTitle" style="margin-bottom:0;"><?php echo h($p['title']); ?></h1>
            <button class="btn btn-ghost btn-sm" onclick="translateAd(<?php echo $id; ?>)" id="transBtn">
                <i class="fas fa-language"></i> <?php echo h(__('translate')); ?>
            </button>
        </div>
        <div class="detail-price" id="adPrice"><?php echo formatPrice($p['price']); ?></div>
        <div class="detail-meta">
          <span><i class="fas fa-tag"></i> <?php echo h($p['cat_name']); ?></span>
          <span>
            <i class="fas fa-map-marker-alt"></i> 
            <?php 
              if (!empty($p['manual_location'])) echo h($p['manual_location']);
              else echo h($p['location'] ?: 'Location not specified');
            ?>
          </span>
          <span><i class="fas fa-clock"></i> <?php echo date('d M Y', strtotime($p['created_at'])); ?></span>
          <span><i class="fas fa-eye"></i> <?php echo number_format($p['view_count']); ?> views</span>
          <span><i class="fas fa-heart"></i> <?php echo number_format($p['saves_count']); ?> saves</span>
        </div>
      </div>

      <!-- Description -->
      <div class="card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:14px;">Description</h3>
        <div id="descFull" style="color:var(--text-2);line-height:1.8;font-size:.9375rem;white-space:pre-line;">
          <?php echo h($p['description']); ?>
        </div>
      </div>

      <!-- Reviews Section -->
      <div class="card" style="margin-top:24px;">
        <h3 style="margin-bottom:20px;">Reviews</h3>
        
        <?php if ($can_review): ?>
          <div class="review-form" style="background:var(--bg-2);padding:20px;border-radius:12px;margin-bottom:24px;">
            <h4 style="margin-bottom:12px;">Leave a Review</h4>
            <div class="form-group">
              <label>Rating</label>
              <div class="star-rating" style="color:var(--warning);font-size:1.5rem;cursor:pointer;">
                <i class="far fa-star d-star" data-val="1"></i>
                <i class="far fa-star d-star" data-val="2"></i>
                <i class="far fa-star d-star" data-val="3"></i>
                <i class="far fa-star d-star" data-val="4"></i>
                <i class="far fa-star d-star" data-val="5"></i>
                <input type="hidden" id="revRating" value="0">
              </div>
            </div>
            <div class="form-group">
              <textarea class="form-control" id="revText" rows="3" placeholder="Write your experience..."></textarea>
            </div>
            <button class="btn btn-primary" onclick="submitReview()">Submit Review</button>
          </div>
        <?php endif; ?>

        <div class="reviews-list">
          <?php if (empty($reviews)): ?>
            <p style="color:var(--text-3);font-size:.875rem;">No reviews yet for this transaction.</p>
          <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
              <div class="review-item" style="border-bottom:1px solid var(--border);padding:16px 0;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                  <strong style="font-size:.9rem;"><?php echo h($rev['reviewer_name']); ?></strong>
                  <div style="color:var(--warning);font-size:.8rem;">
                    <?php for($i=1;$i<=5;$i++) echo '<i class="'.($i<=$rev['rating'] ? 'fas' : 'far').' fa-star"></i>'; ?>
                  </div>
                </div>
                <div style="font-size:.875rem;color:var(--text-2);"><?php echo h($rev['review_text']); ?></div>
                <div style="font-size:.7rem;color:var(--text-3);margin-top:8px;"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <aside class="product-detail-side">

      <!-- Seller Card -->
      <div class="card seller-card" style="margin-bottom:20px;">
        <div class="seller-row">
          <img class="seller-avatar" src="assets/images/avatar.webp" alt="<?php echo h($p['seller_name']); ?>">
          <div style="flex:1;">
            <div class="seller-name">
              <?php echo h($p['seller_name']); ?>
              <?php if ($p['is_verified']): ?><i class="fas fa-circle-check" style="color:var(--primary)"></i><?php endif; ?>
            </div>
            <div style="font-size:.78rem;color:var(--text-3);margin-top:2px;">
              Member since <?php echo date('M Y', strtotime($p['member_since'])); ?>
              · <?php echo $p['ads_count']; ?> ads
            </div>
          </div>
        </div>

        <!-- Trust Score -->
        <div class="seller-score">
          <div class="score-label">Trust Score</div>
          <div class="score-bar-wrap">
            <div class="score-bar">
              <div class="score-fill" style="width:<?php echo min(100, max(0, $p['trust_score'] ?? 50)); ?>%"></div>
            </div>
            <span class="score-num"><?php echo $p['trust_score'] ?? 50; ?>/100</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-btns">
          <?php if ($is_logged && !$is_own): ?>
            <a href="chat.php?product_id=<?php echo $p['id']; ?>" class="btn btn-primary">
              <i class="fas fa-comment-dots"></i> Chat with Seller
            </a>
            <?php if (!$p['is_online']): ?>
              <button class="btn btn-secondary" onclick="openDealModal()">
                <i class="fas fa-handshake"></i> Propose a Deal
              </button>
            <?php endif; ?>
          <?php elseif (!$is_logged): ?>
            <a href="login.php" class="btn btn-primary btn-block">Login to Contact Seller</a>
          <?php endif; ?>
          <div class="action-row">
            <?php
            $is_saved = false;
            if ($is_logged) {
                $check_saved = $pdo->prepare("SELECT id FROM favorites WHERE user_id=? AND product_id=?");
                $check_saved->execute([$user_id, $id]);
                $is_saved = (bool)$check_saved->fetch();
            }
            ?>
            <button class="btn <?php echo $is_saved ? 'btn-danger' : 'btn-ghost'; ?>" style="flex:1;" id="saveBtn" onclick="toggleSave(<?php echo $id; ?>)">
              <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $is_saved ? 'Saved' : 'Save'; ?>
            </button>
            <button class="btn btn-ghost" style="flex:1;" onclick="reportAd()">
              <i class="fas fa-flag"></i> Report
            </button>
          </div>
        </div>
      </div>

      <!-- Price Card -->
      <div class="card" style="text-align:center;">
        <div style="font-size:2rem;font-weight:800;color:var(--primary);"><?php echo formatPrice($p['price']); ?></div>
        <div style="font-size:.8125rem;color:var(--text-3);margin-top:6px;">Negotiable price</div>
      </div>

      <?php if ($p['is_online']): ?>
        <div class="alert alert-warning" style="margin-top:16px;">
          <i class="fas fa-globe"></i>
          <strong>Online listing</strong> — Deal confirmation disabled. Chat to arrange details.
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>

<!-- Deal Modal -->
<?php if ($is_logged && !$is_own && !$p['is_online']): ?>
<div class="modal-overlay" id="dealModal">
  <div class="modal-box">
    <h2><i class="fas fa-handshake" style="color:var(--success);margin-right:8px;"></i> Propose a Deal</h2>
    <p style="color:var(--text-3);margin-bottom:20px;font-size:.9rem;">Set your agreed price and meeting location. The seller will confirm.</p>
    <div class="form-group">
      <label>Agreed Price (Rs)</label>
      <input class="form-control" type="number" id="deal_price" placeholder="e.g. 5000" value="<?php echo $p['price']; ?>">
    </div>
    <div class="form-group">
      <label>Meeting Location</label>
      <input class="form-control" type="text" id="deal_location" placeholder="e.g. Gulberg Mall, Lahore">
    </div>
    <div class="modal-actions">
      <button class="btn btn-success" onclick="submitDeal()"><i class="fas fa-paper-plane"></i> Send Proposal</button>
      <button class="btn btn-ghost" onclick="document.getElementById('dealModal').classList.remove('open')">Cancel</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function switchImg(src, el) {
  document.getElementById('mainImgEl').src = src;
  document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}
function openDealModal() {
  document.getElementById('dealModal').classList.add('open');
}
async function submitDeal() {
  const price = document.getElementById('deal_price').value;
  const loc   = document.getElementById('deal_location').value;
  if (!price || !loc) { alert('Please fill in all fields.'); return; }

  const fd = new FormData();
  fd.append('product_id', '<?php echo $p['id']; ?>');
  fd.append('agreed_price', price);
  fd.append('meeting_location', loc);

  const res = await fetch('ajax/propose-deal.php', { method: 'POST', body: fd });
  const data = await res.json();

  if (data.success) {
    document.getElementById('dealModal').classList.remove('open');
    window.location = 'chat.php?product_id=<?php echo $p['id']; ?>';
  }
}
async function toggleSave(id) {
  if (!<?php echo $is_logged ? 'true' : 'false'; ?>) { window.location = 'login.php?redirect=' + encodeURIComponent(window.location.href); return; }
  
  const res = await fetch('ajax/toggle-favorite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `product_id=${id}`
  });
  const data = await res.json();
  
  if (data.saved !== undefined) {
    const btn = document.getElementById('saveBtn');
    if (data.saved) {
      btn.classList.add('btn-danger');
      btn.classList.remove('btn-ghost');
      btn.innerHTML = '<i class="fas fa-heart"></i> Saved';
    } else {
      btn.classList.remove('btn-danger');
      btn.classList.add('btn-ghost');
      btn.innerHTML = '<i class="far fa-heart"></i> Save';
    }
  }
}
// Review scripting
document.querySelectorAll('.d-star').forEach(s => {
    s.addEventListener('mouseover', function() {
        let v = this.dataset.val;
        document.querySelectorAll('.d-star').forEach(star => {
            star.className = star.dataset.val <= v ? 'fas fa-star d-star' : 'far fa-star d-star';
        });
    });
    s.addEventListener('click', function() {
        document.getElementById('revRating').value = this.dataset.val;
    });
});

async function submitReview() {
    const r = document.getElementById('revRating').value;
    const t = document.getElementById('revText').value;
    if (r == 0) { alert('Please select a rating.'); return; }
    
    const fd = new FormData();
    fd.append('product_id', '<?php echo $id; ?>');
    fd.append('rating', r);
    fd.append('review_text', t);
    
    const res = await fetch('ajax/submit-review.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        alert(data.message);
        window.location.reload();
    } else {
        alert(data.error);
    }
}
function reportAd() {
  if (confirm('Report this listing as suspicious?')) {
    alert('Thank you. Our team will review this listing.');
  }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
