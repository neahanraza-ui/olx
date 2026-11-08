<?php
// post-ad.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/settings.php';

requireLogin();
validateSession($pdo);

// Fetch main categories
$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY name ASC");
$stmt->execute();
$main_cats = $stmt->fetchAll();

// Fetch countries
$countries = $pdo->query("SELECT * FROM locations WHERE type = 'country' AND status = 'active' ORDER BY name ASC")->fetchAll();

$online_popup_text = getSetting('online_popup_text') ?: "⚠️ WARNING: This listing appears to be an online delivery item.\n\nOnline transactions may involve significant risk. The platform does NOT take responsibility for scams or failed deliveries.\n\nYou are proceeding at your own risk.\n\nNever pay in advance without verifying the seller.";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title           = sanitizeInput($_POST['title'] ?? '');
    $description     = sanitizeInput($_POST['description'] ?? '');
    $price           = (float)($_POST['price'] ?? 0);
    $category_id     = (int)($_POST['category_id'] ?? 0);
    $subcategory_id  = (int)($_POST['subcategory_id'] ?? 0);
    $sub_subcategory_id = (int)($_POST['sub_subcategory_id'] ?? 0);
    $location_type   = sanitizeInput($_POST['location_type'] ?? 'map');
    $location        = sanitizeInput($_POST['location'] ?? '');
    $manual_location = sanitizeInput($_POST['manual_location'] ?? '');
    $country         = sanitizeInput($_POST['country'] ?? '');
    $city            = sanitizeInput($_POST['city'] ?? '');
    $lat             = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $lng             = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $condition       = sanitizeInput($_POST['condition'] ?? 'Used');
    $online_product  = (isset($_POST['is_online']) && $_POST['is_online'] == '1') ? 1 : 0;
    $phone_show      = isset($_POST['phone_show']) ? 1 : 0;

    if (!$title)       $errors[] = "Title is required.";
    if (!$description) $errors[] = "Description is required.";
    if (!$price)       $errors[] = "Price is required.";
    if (!$category_id) $errors[] = "Category is required.";
    
    // Force Online category if online_product is tagged (backend safety)
    if ($online_product) {
        $online_cat = $pdo->query("SELECT id FROM categories WHERE name = 'Online' LIMIT 1")->fetch();
        if ($online_cat) $category_id = $online_cat['id'];
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (seller_id, category_id, subcategory_id, sub_subcategory_id, title, description, price, location, manual_location, country, city, latitude, longitude, `condition`, online_product, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'active')");
        $stmt->execute([
            getCurrentUserId(), $category_id, $subcategory_id, $sub_subcategory_id, 
            $title, $description, $price, $location, $manual_location, 
            $country, $city, $lat, $lng, $condition, $online_product
        ]);
        $product_id = $pdo->lastInsertId();

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = 'assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            $first = true;
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if (!$tmp) continue;
                $ext  = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                $fname = uniqid('img_') . '.' . $ext;
                if (move_uploaded_file($tmp, $upload_dir . $fname)) {
                    $is_main = $first ? 1 : 0;
                    $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?,?,?)")
                        ->execute([$product_id, $upload_dir . $fname, $is_main]);
                    $first = false;
                }
            }
        }

        header("Location: product.php?id=$product_id&posted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post an Ad – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .location-toggle { display: flex; gap: 10px; margin-bottom: 20px; }
    .location-toggle .btn { flex: 1; }
    .location-toggle .btn.active { background: var(--primary); color: #fff; }
    .nested-cats { margin-top: 15px; display: none; }
    .warning-text { text-align: left; }
  </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
  <div class="wizard-layout">
    <h1>Post Your Ad</h1>
    <p style="color:var(--text-3);margin-bottom:28px;">Sell fast by reaching thousands of buyers across Pakistan and Afghanistan.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>

    <div class="wizard-steps" id="stepsIndicator">
      <div class="wiz-step active" id="ind1"><div class="num">1</div><span class="lbl">Category</span></div>
      <div class="wiz-connector" id="con1"></div>
      <div class="wiz-step" id="ind2"><div class="num">2</div><span class="lbl">Photos</span></div>
      <div class="wiz-connector" id="con2"></div>
      <div class="wiz-step" id="ind3"><div class="num">3</div><span class="lbl">Details</span></div>
      <div class="wiz-connector" id="con3"></div>
      <div class="wiz-step" id="ind4"><div class="num">4</div><span class="lbl">Pricing</span></div>
      <div class="wiz-connector" id="con4"></div>
      <div class="wiz-step" id="ind5"><div class="num">5</div><span class="lbl">Contact</span></div>
      <div class="wiz-connector" id="con5"></div>
      <div class="wiz-step" id="ind6"><div class="num">6</div><span class="lbl">Publish</span></div>
    </div>

    <form id="postAdForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="is_online" id="is_online_input" value="0">
      <input type="hidden" name="category_id" id="category_id_hidden">
      <input type="hidden" name="subcategory_id" id="subcategory_id_hidden">
      <input type="hidden" name="sub_subcategory_id" id="sub_subcategory_id_hidden">
      
      <!-- Location Hidden Data -->
      <input type="hidden" name="latitude" id="lat_input">
      <input type="hidden" name="longitude" id="lng_input">
      <input type="hidden" name="country" id="country_input">
      <input type="hidden" name="city" id="city_input">

      <div class="wizard-body">

        <!-- Step 1: Category Hierarchy -->
        <div class="wizard-step-panel active" id="step1">
          <h2 style="margin-bottom:20px;">Choose a Category</h2>
          
          <div class="form-group">
            <label>Main Category</label>
            <div class="cat-picker">
              <?php foreach ($main_cats as $cat): ?>
                <div class="cat-option" data-id="<?php echo $cat['id']; ?>" data-name="<?php echo h($cat['name']); ?>" onclick="selectMainCat(this)">
                  <i class="fas <?php echo h($cat['icon']); ?>"></i>
                  <span><?php echo h($cat['name']); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div id="subCatSection" class="nested-cats">
            <div class="form-group">
              <label>Subcategory</label>
              <select id="subcategory_select" class="form-control" onchange="selectSubCat(this.value)">
                <option value="">Select Subcategory</option>
              </select>
            </div>
          </div>

          <div id="subSubCatSection" class="nested-cats">
            <div class="form-group">
              <label>Sub-subcategory (Optional)</label>
              <select id="sub_subcategory_select" class="form-control" onchange="selectSubSubCat(this.value)">
                <option value="">Select Sub-subcategory</option>
              </select>
            </div>
          </div>

          <div class="wizard-footer">
            <span></span>
            <button type="button" class="btn btn-primary" onclick="validateStep1()" id="step1Next" disabled>
              Next <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- Step 2: Photos -->
        <div class="wizard-step-panel" id="step2">
          <h2 style="margin-bottom:6px;">Upload Photos</h2>
          <p style="color:var(--text-3);margin-bottom:20px;">First photo will be the main cover photo.</p>
          <div class="drop-zone" id="dropZone" onclick="document.getElementById('imgInput').click()">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Drag & drop photos here, or <span class="link">browse</span></p>
          </div>
          <input type="file" id="imgInput" name="images[]" multiple accept="image/*" style="display:none" onchange="previewImgs(this)">
          <div class="preview-grid" id="previewGrid"></div>
          <div class="wizard-footer">
            <button type="button" class="btn btn-ghost" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-primary" onclick="goStep(3)">Next <i class="fas fa-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 3: Details -->
        <div class="wizard-step-panel" id="step3">
          <h2 style="margin-bottom:20px;">Ad Details</h2>
          <div class="form-group">
            <label>Ad Title</label>
            <input class="form-control" type="text" name="title" id="ad_title" required maxlength="100" placeholder="e.g. Samsung Galaxy S24 Ultra">
            <div class="char-count"><span id="titleCount">0</span>/100</div>
          </div>
          
          <div class="form-group">
            <label>Condition</label>
            <div style="display:flex;gap:15px;">
              <label class="filter-option"><input type="radio" name="condition" value="Used" checked> Used</label>
              <label class="filter-option"><input type="radio" name="condition" value="New"> New</label>
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="description" id="ad_desc" rows="6" maxlength="1000" required placeholder="Describe what you are selling..."></textarea>
            <div class="char-count"><span id="descCount">0</span>/1000</div>
          </div>

          <div class="wizard-footer">
            <button type="button" class="btn btn-ghost" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-primary" onclick="checkKeywordBot()">Next <i class="fas fa-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 4: Price & Location -->
        <div class="wizard-step-panel" id="step4">
          <h2 style="margin-bottom:20px;">Price & Location</h2>
          
          <div class="form-group">
            <label>Price</label>
            <div style="display:flex;align-items:center;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:0 14px;">
              <span style="color:var(--text-3);font-weight:700;margin-right:10px;">Rs</span>
              <input class="form-control" type="number" name="price" style="border:none;box-shadow:none;padding-left:0;" required placeholder="0">
            </div>
          </div>

            <div class="form-group">
              <label>Country</label>
              <select name="country" id="country_select" class="form-control" onchange="loadCities(this.options[this.selectedIndex].dataset.id)">
                <option value="">Select Country</option>
                <?php foreach ($countries as $c): ?>
                  <option value="<?php echo h($c['name']); ?>" data-id="<?php echo $c['id']; ?>"><?php echo h($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>City</label>
              <select name="city" id="city_select" class="form-control">
                <option value="">Select City</option>
              </select>
            </div>

            <div class="form-group">
              <label>Full Location Details (Optional)</label>
              <input class="form-control" type="text" name="manual_location" placeholder="e.g. Near Gulshan Block 10, Karachi">
            </div>

          <div id="onlineLocationNote" style="display:none;" class="alert alert-warning">
            <i class="fas fa-globe"></i> <strong>Online Product Detected:</strong> Location is optional as items are delivered digitally or remotely.
          </div>

          <div class="wizard-footer">
            <button type="button" class="btn btn-ghost" onclick="goStep(3)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-primary" onclick="goStep(5)">Next <i class="fas fa-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 5: Contact -->
        <div class="wizard-step-panel" id="step5">
          <h2 style="margin-bottom:20px;">Contact Options</h2>
          <div class="card" style="margin-bottom:16px;">
            <label class="filter-option" style="gap:12px;">
              <input type="checkbox" checked disabled>
              <div>
                <div style="font-weight:600;">Chat via OLX Plus</div>
                <div style="font-size:.8rem;color:var(--text-3);">Recommended for security and review eligibility.</div>
              </div>
            </label>
          </div>
          <div class="card">
            <label class="filter-option" style="gap:12px;">
              <input type="checkbox" name="phone_show">
              <div>
                <div style="font-weight:600;">Show my phone number</div>
                <div style="font-size:.8rem;color:var(--text-3);">Buyers will be able to call you directly.</div>
              </div>
            </label>
          </div>

          <div class="wizard-footer">
            <button type="button" class="btn btn-ghost" onclick="goStep(4)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-primary" onclick="goStep(6)">Review Ad <i class="fas fa-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 6: Publish -->
        <div class="wizard-step-panel" id="step6">
          <div style="text-align:center;padding:40px 0;">
            <i class="fas fa-rocket" style="font-size:4rem;color:var(--primary);margin-bottom:20px;display:block;"></i>
            <h2>Almost there!</h2>
            <p style="color:var(--text-3);margin-bottom:30px;">Your ad will be live as soon as you click publish.</p>
            <button type="submit" class="btn btn-primary btn-lg btn-block">
              <i class="fas fa-check-circle"></i> Publish Ad Now
            </button>
          </div>
          <div class="wizard-footer" style="justify-content:flex-start;">
            <button type="button" class="btn btn-ghost" onclick="goStep(5)"><i class="fas fa-arrow-left"></i> Back</button>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<!-- Category Force Modal (Online Detection) -->
<div class="modal-overlay" id="onlineModal">
  <div class="modal-box" style="text-align:center;">
    <div class="modal-icon" style="color:var(--warning);">⚠️</div>
    <h2 style="color:var(--warning);">Security Warning</h2>
    <div class="warning-text"><?php echo nl2br(h($online_popup_text)); ?></div>
    <label class="checkbox-row" style="justify-content:center;">
      <input type="checkbox" id="riskCheckbox" onchange="toggleAccept()">
      I understand the risk and wish to continue
    </label>
    <div class="modal-actions">
      <button class="btn btn-warning" id="acceptBtn" onclick="confirmOnlineForce()" disabled style="flex:1;">
        <i class="fas fa-check"></i> Accept & Continue
      </button>
      <button class="btn btn-ghost" onclick="closeOnlineModal()" style="flex:1;">Cancel</button>
    </div>
  </div>
</div>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script>

<script>
let currentStep = 1;
let selectedMainCat = null;
let ONLINE_CAT_ID = null;

// Initialize Google Autocomplete
function initAutocomplete() {
  const input = document.getElementById('ad_location');
  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['(cities)'],
    componentRestrictions: { country: ['pk', 'af'] }
  });

  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;
    
    document.getElementById('lat_input').value = place.geometry.location.lat();
    document.getElementById('lng_input').value = place.geometry.location.lng();
    
    // Extract city/country from address components
    place.address_components.forEach(c => {
      if (c.types.includes('country')) document.getElementById('country_input').value = c.long_name;
      if (c.types.includes('locality')) document.getElementById('city_input').value = c.long_name;
    });
  });
}
google.maps.event.addDomListener(window, 'load', initAutocomplete);

async function selectMainCat(el) {
    document.querySelectorAll('.cat-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    const catId = el.dataset.id;
    const catName = el.dataset.name;
    selectedMainCat = catName;
    document.getElementById('category_id_hidden').value = catId;
    
    // Check if Online
    if (catName === 'Online') {
        ONLINE_CAT_ID = catId;
        document.getElementById('is_online_input').value = '1';
        document.getElementById('onlineLocationNote').style.display = 'block';
    } else {
        document.getElementById('is_online_input').value = '0';
        document.getElementById('onlineLocationNote').style.display = 'none';
    }

    // Load Subcategories
    const res = await fetch(`ajax/get_subcategories.php?parent_id=${catId}`);
    const data = await res.json();
    
    const subSelect = document.getElementById('subcategory_select');
    subSelect.innerHTML = '<option value="">Select Subcategory</option>';
    
    if (data.length > 0) {
        data.forEach(s => {
            subSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });
        document.getElementById('subCatSection').style.display = 'block';
        document.getElementById('step1Next').disabled = true; // Still need subcat
    } else {
        document.getElementById('subCatSection').style.display = 'none';
        document.getElementById('subSubCatSection').style.display = 'none';
        document.getElementById('step1Next').disabled = false;
    }
}

async function selectSubCat(val) {
    document.getElementById('subcategory_id_hidden').value = val;
    if (!val) {
        document.getElementById('subSubCatSection').style.display = 'none';
        document.getElementById('step1Next').disabled = true;
        return;
    }

    // Load Sub-subcategories
    const res = await fetch(`ajax/get_subcategories.php?parent_id=${val}`);
    const data = await res.json();
    
    const subSubSelect = document.getElementById('sub_subcategory_select');
    subSubSelect.innerHTML = '<option value="">Select Sub-subcategory (Optional)</option>';
    
    if (data.length > 0) {
        data.forEach(s => {
            subSubSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });
        document.getElementById('subSubCatSection').style.display = 'block';
    } else {
        document.getElementById('subSubCatSection').style.display = 'none';
    }
    document.getElementById('step1Next').disabled = false;
}

function selectSubSubCat(val) {
    document.getElementById('sub_subcategory_id_hidden').value = val;
}

function validateStep1() { goStep(2); }

async function loadCities(countryId) {
    const citySelect = document.getElementById('city_select');
    citySelect.innerHTML = '<option value="">Select City</option>';
    if (!countryId) return;

    const res = await fetch(`ajax/get_cities.php?country_id=${countryId}`);
    const data = await res.json();
    
    data.forEach(city => {
        citySelect.innerHTML += `<option value="${city.name}">${city.name}</option>`;
    });
}

function goStep(n) {
  document.getElementById('step' + currentStep).classList.remove('active');
  document.getElementById('ind' + currentStep).classList.remove('active');
  document.getElementById('ind' + currentStep).classList.add('done');
  document.getElementById('step' + n).classList.add('active');
  document.getElementById('ind' + n).classList.add('active');
  document.getElementById('ind' + n).classList.remove('done');
  if (n > currentStep && currentStep < 6) {
    document.getElementById('con' + currentStep)?.classList.add('done');
  }
  currentStep = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function checkKeywordBot() {
    const title = document.getElementById('ad_title').value;
    const desc = document.getElementById('ad_desc').value;
    
    const res = await fetch(`ajax/check-keywords.php?text=${encodeURIComponent(title + ' ' + desc)}`);
    const data = await res.json();
    
    if (data.detected && selectedMainCat !== 'Online') {
        document.getElementById('onlineModal').classList.add('open');
    } else {
        goStep(4);
    }
}

function toggleAccept() {
  document.getElementById('acceptBtn').disabled = !document.getElementById('riskCheckbox').checked;
}

function confirmOnlineForce() {
    document.getElementById('is_online_input').value = '1';
    // If we have ONLINE_CAT_ID cached from elsewhere, use it, else we'll handle in PHP
    closeOnlineModal();
    goStep(4);
}

function closeOnlineModal() {
  document.getElementById('onlineModal').classList.remove('open');
}

function previewImgs(input) {
  const grid = document.getElementById('previewGrid');
  grid.innerHTML = '';
  Array.from(input.files).slice(0, 8).forEach((file) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-thumb';
      div.innerHTML = `<img src="${e.target.result}"><button class="rm" type="button" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>`;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

// Char counters
['title','desc'].forEach(id => {
  const el = document.getElementById('ad_' + id);
  const ct = document.getElementById(id === 'title' ? 'titleCount' : 'descCount');
  if (el && ct) { el.addEventListener('input', () => { ct.textContent = el.value.length; }); }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
