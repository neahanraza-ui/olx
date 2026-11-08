<?php
// index.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

$cats = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY id")->fetchAll();
$countries = $pdo->query("SELECT * FROM locations WHERE type = 'country' AND status = 'active' ORDER BY name ASC")->fetchAll();

$products = $pdo->query("
  SELECT p.*, u.name AS seller_name, u.is_verified,
         pi.image_path
  FROM products p
  JOIN users u ON p.seller_id = u.id
  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
  WHERE p.status = 'active'
  ORDER BY p.created_at DESC
  LIMIT 12
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="OLX Plus – Trust-focused marketplace for buying and selling locally and online.">
  <title>OLX Plus – Buy &amp; Sell with Trust</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- ─── Hero ─── -->
<section class="hero">
  <div class="container">
    <h1><?php echo __('hero_title'); ?></h1>
    <p><?php echo h(__('hero_sub')); ?></p>

      <form class="hero-search-bar" action="search.php" method="GET">
        <input type="text" name="q" class="hsb-keyword" placeholder="What are you looking for?">
        
        <div class="hsb-divider"></div>

        <select name="country" id="heroCountry" class="hsb-select" onchange="loadHeroCities(this.options[this.selectedIndex].dataset.id)">
            <option value=""><?php echo h(__('any_country')); ?></option>
            <?php foreach ($countries as $c): ?>
              <option value="<?php echo h($c['name']); ?>" data-id="<?php echo $c['id']; ?>"><?php echo h($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
        
        <div class="hsb-divider"></div>

        <select name="city" id="heroCity" class="hsb-select">
            <option value=""><?php echo h(__('any_city')); ?></option>
        </select>

        <button type="submit" class="hsb-btn"><i class="fas fa-search"></i> <?php echo h(__('hero_search_btn')); ?></button>
      </form>
    
    <script>
    async function loadHeroCities(countryId) {
        const citySelect = document.getElementById('heroCity');
        citySelect.innerHTML = '<option value=""><?php echo h(__('any_city')); ?></option>';
        if (!countryId) return;

        const res = await fetch(`ajax/get_cities.php?country_id=${countryId}`);
        const data = await res.json();
        
        data.forEach(city => {
            citySelect.innerHTML += `<option value="${city.name}">${city.name}</option>`;
        });
    }
    </script>
    <p class="hero-helper">
      <?php echo h(__('online_delivery_q')); ?>
      <a href="search.php?online=1"><i class="fas fa-exclamation-triangle"></i> <?php echo h(__('online_delivery_link')); ?></a>
    </p>

    <div class="hero-stats">
      <div class="stat"><div class="num">50K+</div><div class="lbl"><?php echo h(__('stat_active')); ?></div></div>
      <div class="stat"><div class="num">12K+</div><div class="lbl"><?php echo h(__('stat_verified')); ?></div></div>
      <div class="stat"><div class="num">8K+</div><div class="lbl"><?php echo h(__('stat_deals')); ?></div></div>
    </div>
  </div>
</section>

<!-- ─── Trust Banner ─── -->
<div class="trust-banner">
  <div class="container">
    <div class="trust-items">
      <div class="trust-item">
        <div class="icon green"><i class="fas fa-handshake"></i></div>
        <div>
          <h4><?php echo h(__('trust_meet')); ?></h4>
          <p><?php echo h(__('trust_meet_sub')); ?></p>
        </div>
      </div>
      <div class="trust-item">
        <div class="icon blue"><i class="fas fa-file-contract"></i></div>
        <div>
          <h4><?php echo h(__('trust_receipt')); ?></h4>
          <p><?php echo h(__('trust_receipt_sub')); ?></p>
        </div>
      </div>
      <div class="trust-item">
        <div class="icon yellow"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
          <h4><?php echo h(__('trust_risk')); ?></h4>
          <p><?php echo h(__('trust_risk_sub')); ?></p>
        </div>
      </div>
      <div class="trust-item">
        <div class="icon green"><i class="fas fa-shield-alt"></i></div>
        <div>
          <h4><?php echo h(__('trust_verified')); ?></h4>
          <p><?php echo h(__('trust_verified_sub')); ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ─── Categories ─── -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2><?php echo h(__('browse_categories')); ?></h2>
      <a href="search.php" class="view-all"><?php echo h(__('view_all')); ?> <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="categories-grid">
      <?php foreach ($cats as $cat): ?>
        <a href="search.php?category=<?php echo $cat['id']; ?>"
           class="cat-card <?php echo $cat['name'] === 'Online' ? 'online' : ''; ?>">
          <i class="fas <?php echo h($cat['icon']); ?>"></i>
          <span><?php echo h($cat['name']); ?></span>
          <?php if ($cat['name'] === 'Online'): ?>
            <div class="badge badge-online" style="margin-top:6px;font-size:.65rem;">
              <i class="fas fa-exclamation-triangle"></i> <?php echo h(__('higher_risk')); ?>
            </div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ─── Featured Listings ─── -->
<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="section-head">
      <h2><?php echo h(__('fresh_listings')); ?></h2>
      <a href="search.php" class="view-all"><?php echo h(__('view_all')); ?> <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if (empty($products)): ?>
      <div class="no-results">
        <i class="fas fa-box-open"></i>
        <p><?php echo h(__('no_listings_yet')); ?></p>
        <a href="post-ad.php" class="btn btn-primary" style="margin-top:16px;"><?php echo h(__('post_ad_first')); ?></a>
      </div>
    <?php else: ?>
      <div class="products-grid">
        <?php foreach ($products as $p): ?>
          <a href="product.php?id=<?php echo $p['id']; ?>" class="product-card">
            <div class="img-wrap">
              <img src="<?php echo $p['image_path'] ? h($p['image_path']) : 'assets/images/placeholder.webp'; ?>"
                   alt="<?php echo h($p['title']); ?>" loading="lazy">
              <button class="save-btn" title="Save" onclick="event.preventDefault()"><i class="far fa-heart"></i></button>
              <?php if ($p['is_online']): ?>
                <span class="badge badge-online"><i class="fas fa-globe"></i> <?php echo h(__('online')); ?></span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="card-title"><?php echo h($p['title']); ?></div>
              <div class="card-price">Rs <?php echo number_format($p['price']); ?></div>
              <div class="card-meta">
                <span class="seller-badge">
                  <i class="fas fa-user-circle" style="color:var(--text-3)"></i>
                  <?php echo h($p['seller_name']); ?>
                  <?php if ($p['is_verified']): ?><i class="fas fa-circle-check verified"></i><?php endif; ?>
                </span>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo h($p['location']); ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>
