<?php
// search.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

$q              = sanitizeInput($_GET['q'] ?? '');
$cat_id         = (int)($_GET['category'] ?? 0);
$subcat_id      = (int)($_GET['subcategory'] ?? 0);
$subsubcat_id   = (int)($_GET['sub_subcategory'] ?? 0);
$location       = sanitizeInput($_GET['location'] ?? '');
$country        = sanitizeInput($_GET['country'] ?? '');
$city           = sanitizeInput($_GET['city'] ?? '');
$price_min      = (int)($_GET['price_min'] ?? 0);
$price_max      = (int)($_GET['price_max'] ?? 999999999);
$condition      = sanitizeInput($_GET['condition'] ?? '');
$online         = !empty($_GET['online']) ? 1 : null;
$verified       = !empty($_GET['verified']) ? 1 : null;
$sort           = sanitizeInput($_GET['sort'] ?? 'newest');

$main_cats = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY name ASC")->fetchAll();
$countries_list = $pdo->query("SELECT * FROM locations WHERE type = 'country' AND status = 'active' ORDER BY name ASC")->fetchAll();

$where = ["p.status = 'active'"];
$params = [];

if ($q)        { $where[] = "(p.title LIKE ? OR p.description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($cat_id)   { $where[] = "p.category_id = ?"; $params[] = $cat_id; }
if ($subcat_id) { $where[] = "p.subcategory_id = ?"; $params[] = $subcat_id; }
if ($subsubcat_id) { $where[] = "p.sub_subcategory_id = ?"; $params[] = $subsubcat_id; }
if ($location) { $where[] = "(p.location LIKE ? OR p.manual_location LIKE ?)"; $params[] = "%$location%"; $params[] = "%$location%"; }
if ($country)  { $where[] = "p.country = ?"; $params[] = $country; }
if ($city)     { $where[] = "p.city = ?"; $params[] = $city; }
if ($price_min){ $where[] = "p.price >= ?"; $params[] = $price_min; }
if ($price_max < 999999999){ $where[] = "p.price <= ?"; $params[] = $price_max; }
if ($condition){ $where[] = "p.`condition` = ?"; $params[] = $condition; }
if ($online !== null)  { $where[] = "p.online_product = 1"; }
if ($verified !== null){ $where[] = "u.is_verified = 1"; }

$orderBy = "p.created_at DESC";
if ($sort == 'price_asc')  $orderBy = "p.price ASC";
if ($sort == 'price_desc') $orderBy = "p.price DESC";

$sql = "SELECT p.*, u.name AS seller_name, u.is_verified, pi.image_path
        FROM products p
        JOIN users u ON p.seller_id = u.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$total = count($products);

// Related Products Logic (If results are 0)
$related_products = [];
if ($total === 0 && ($cat_id || $q)) {
    $r_where = ["p.status = 'active'", "p.id NOT IN (SELECT id FROM products WHERE ".implode(' AND ', $where).")"];
    $r_params = $params;
    
    // Broaden search: same category or similar title
    $sql_rel = "SELECT p.*, u.name AS seller_name, u.is_verified, pi.image_path
               FROM products p
               JOIN users u ON p.seller_id = u.id
               LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
               WHERE p.status = 'active' " . ($cat_id ? " AND p.category_id = $cat_id " : "") . "
               ORDER BY RAND() LIMIT 4";
    $related_products = $pdo->query($sql_rel)->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search <?php echo $q ? '"'.h($q).'"' : 'All Listings'; ?> – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
  <div class="search-layout">

    <!-- Sidebar Filters -->
    <aside class="search-sidebar">
      <form method="GET" action="search.php" id="filterForm">
        <?php if ($q): ?><input type="hidden" name="q" value="<?php echo h($q); ?>"><?php endif; ?>

        <div class="card" style="padding:20px;margin-bottom:20px;">
          <h3 style="margin-bottom:18px;"><?php echo h(__('filters')); ?></h3>

          <!-- Category Tree -->
          <div class="filter-section">
            <h4>Category</h4>
            <select name="category" class="form-control" onchange="this.form.submit()">
              <option value="">All Categories</option>
              <?php foreach ($main_cats as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $cat_id == $cat['id'] ? 'selected' : ''; ?>>
                  <?php echo h($cat['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Location -->
          <div class="filter-section">
            <h4><?php echo h(__('country_city')); ?></h4>
            <select name="country" id="searchCountry" class="form-control" style="margin-bottom:8px;" onchange="loadSearchCities(this.options[this.selectedIndex].dataset.id)">
              <option value=""><?php echo h(__('any_country')); ?></option>
              <?php foreach ($countries_list as $c): ?>
                <option value="<?php echo h($c['name']); ?>" data-id="<?php echo $c['id']; ?>" <?php echo $country === $c['name'] ? 'selected' : ''; ?>>
                  <?php echo h($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <select name="city" id="searchCity" class="form-control" onchange="this.form.submit()">
              <option value=""><?php echo h(__('any_city')); ?></option>
              <?php if ($country): ?>
                <?php
                  $sel_country = array_filter($countries_list, fn($c) => $c['name'] === $country);
                  if ($sel_country) {
                    $cid = reset($sel_country)['id'];
                    $cities_list = $pdo->prepare("SELECT name FROM locations WHERE parent_id = ? AND type = 'city' AND status = 'active' ORDER BY name ASC");
                    $cities_list->execute([$cid]);
                    foreach ($cities_list->fetchAll() as $ct) {
                        $sel = ($city === $ct['name']) ? 'selected' : '';
                        echo "<option value=\"".h($ct['name'])."\" $sel>".h($ct['name'])."</option>";
                    }
                  }
                ?>
              <?php endif; ?>
            </select>
          </div>

          <script>
          async function loadSearchCities(countryId) {
              const citySelect = document.getElementById('searchCity');
              citySelect.innerHTML = '<option value=""><?php echo h(__('any_city')); ?></option>';
              if (!countryId) return;

              const res = await fetch(`ajax/get_cities.php?country_id=${countryId}`);
              const data = await res.json();
              
              data.forEach(city => {
                  citySelect.innerHTML += `<option value="${city.name}">${city.name}</option>`;
              });
          }
          </script>

          <!-- Condition -->
          <div class="filter-section">
            <h4>Condition</h4>
            <label class="filter-option">
              <input type="radio" name="condition" value="" <?php echo !$condition ? 'checked' : ''; ?> onchange="this.form.submit()"> Any
            </label>
            <label class="filter-option">
                <input type="radio" name="condition" value="New" <?php echo $condition === 'New' ? 'checked' : ''; ?> onchange="this.form.submit()"> New
            </label>
            <label class="filter-option">
                <input type="radio" name="condition" value="Used" <?php echo $condition === 'Used' ? 'checked' : ''; ?> onchange="this.form.submit()"> Used
            </label>
          </div>

          <!-- Price Range -->
          <div class="filter-section">
            <h4><?php echo h(__('price_range')); ?></h4>
            <div class="price-range-inputs">
              <input class="form-control" type="number" name="price_min" placeholder="<?php echo h(__('price_min_ph')); ?>" value="<?php echo $price_min ?: ''; ?>">
              <input class="form-control" type="number" name="price_max" placeholder="<?php echo h(__('price_max_ph')); ?>" value="<?php echo ($price_max < 999999999) ? $price_max : ''; ?>">
            </div>
          </div>

          <!-- Seller Type -->
          <div class="filter-section">
            <h4><?php echo h(__('seller_label')); ?></h4>
            <label class="filter-option">
              <input type="checkbox" name="verified" value="1" <?php echo $verified ? 'checked' : ''; ?>>
              <?php echo h(__('verified_only')); ?>
            </label>
          </div>

          <!-- Delivery Type -->
          <div class="filter-section">
            <h4><?php echo h(__('delivery_type')); ?></h4>
            <label class="filter-option">
              <input type="checkbox" name="online" value="1" <?php echo $online !== null ? 'checked' : ''; ?>>
              <i class="fas fa-globe" style="color:var(--warning)"></i> <?php echo h(__('online_delivery')); ?>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-block"><?php echo h(__('apply_filters')); ?></button>
        </div>
      </form>
    </aside>

    <!-- Results -->
    <main class="search-results">
      <div class="search-topbar">
        <h2>
          <strong><?php echo number_format($total); ?></strong>
          <?php echo $total != 1 ? h(__('results_count')) : h(__('result_count')); ?>
          <?php if ($q): ?> <?php echo h(__('results_for')); ?> <strong>"<?php echo h($q); ?>"</strong><?php endif; ?>
          <?php if ($location): ?> <?php echo h(__('results_in')); ?> <strong><?php echo h($location); ?></strong><?php endif; ?>
        </h2>
        <select class="form-control" style="width:auto;" onchange="sortResults(this.value)">
          <option value="newest"><?php echo h(__('newest_first')); ?></option>
          <option value="price_asc"><?php echo h(__('price_low_high')); ?></option>
          <option value="price_desc"><?php echo h(__('price_high_low')); ?></option>
        </select>
      </div>

      <?php if ($online !== null): ?>
        <div class="alert alert-warning" style="margin-bottom:20px;">
          <i class="fas fa-exclamation-triangle"></i>
          <?php echo __('online_scam_warn'); ?>
        </div>
      <?php endif; ?>

      <?php if (empty($products)): ?>
        <div class="no-results">
          <i class="fas fa-search"></i>
          <p><?php echo h(__('no_listings_found')); ?></p>
          <a href="search.php" class="btn btn-ghost" style="margin-top:16px;"><?php echo h(__('clear_filters')); ?></a>
        </div>

        <?php if (!empty($related_products)): ?>
          <div style="margin-top:60px;">
            <h3 style="margin-bottom:20px;">You might also like</h3>
            <div class="products-grid">
              <?php foreach ($related_products as $p): ?>
                <a href="product.php?id=<?php echo $p['id']; ?>" class="product-card">
                  <div class="img-wrap">
                    <img src="<?php echo $p['image_path'] ? h($p['image_path']) : 'assets/images/placeholder.webp'; ?>" alt="">
                    <?php if (isset($p['online_product']) && $p['online_product']): ?>
                      <span class="badge badge-online"><i class="fas fa-globe"></i> <?php echo h(__('online')); ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="card-body">
                    <div class="card-title"><?php echo h($p['title']); ?></div>
                    <div class="card-price">Rs <?php echo number_format($p['price']); ?></div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="products-grid" id="resultsGrid">
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
    </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
