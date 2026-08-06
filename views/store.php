<?php
$pageTitle = 'Store';
/** @var array $products */
/** @var array $categories */
/** @var array $brands */
/** @var array $filters */
/** @var array $priceRange */
/** @var array $facets */
/** @var bool $hasReviews */
/** @var array $bestSellerIds */
/** @var array $popularIds */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */

// slug => display name, so the active-filter chips read "Category: Protein", not "Category: protein-powder".
$slugNames = [];
foreach ($categories as $cat) {
    $slugNames[$cat['slug']] = $cat['name'];
    foreach ($cat['children'] ?? [] as $child) {
        $slugNames[$child['slug']] = $child['name'];
    }
}
foreach ($brands as $brandRow) {
    $slugNames[$brandRow['slug']] = $brandRow['name'];
}

// Every non-empty filter, so pagination links keep the current result set.
$pageQuery = array_filter([
    'category' => $filters['category'],
    'brand' => $filters['brand'],
    'q' => $filters['search'],
    'availability' => $filters['availability'],
    'min_price' => $filters['min_price'] !== null ? (int) $filters['min_price'] : null,
    'max_price' => $filters['max_price'] !== null ? (int) $filters['max_price'] : null,
    'on_sale' => $filters['on_sale'] ? '1' : null,
    'best_seller' => $filters['best_seller'] ? '1' : null,
    'min_rating' => $filters['min_rating'] !== null ? (int) $filters['min_rating'] : null,
    'sort' => $filters['sort'],
], fn ($v) => $v !== null && $v !== '');
?>

<section class="hero page-hero text-center">
  <div class="container">
    <div class="hero-copy">
      <span class="hero-badge">Store</span>
      <h1>Supplements &amp; <span class="text-orange">Gym Gear</span></h1>
      <p class="hero-subtitle">Everything you need to fuel training and recovery — available at the front desk.</p>
      <div class="hero-actions">
        <a href="<?= url('/bundles') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-gift"></i> View Bundle Deals</a>
      </div>
    </div>
  </div>
</section>

<section class="section pt-0">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div class="shop-results-bar d-flex flex-wrap align-items-center gap-2">
        <span class="text-white-50 small">
          <?php if ($total > 0): ?>
            Showing <strong class="text-white"><?= count($products) ?></strong> of <strong class="text-white"><?= (int) $total ?></strong> products
          <?php else: ?>
            No matching products
          <?php endif; ?>
        </span>
        <?php
        // Removable chip per active filter — each link is the current query minus that one key.
        $chipLabels = [
          'q' => fn ($v) => 'Search: "' . $v . '"',
          'category' => fn ($v) => 'Category: ' . ($slugNames[$v] ?? $v),
          'brand' => fn ($v) => 'Brand: ' . ($slugNames[$v] ?? $v),
          'availability' => fn ($v) => ['in' => 'In stock', 'low' => 'Only a few left', 'out' => 'Out of stock'][$v] ?? $v,
          'min_price' => fn ($v) => 'From ৳' . number_format((float) $v),
          'max_price' => fn ($v) => 'Up to ৳' . number_format((float) $v),
          'on_sale' => fn () => 'On sale',
          'best_seller' => fn () => 'Best sellers',
          'min_rating' => fn ($v) => $v . '★ & up',
        ];
        foreach ($chipLabels as $key => $label):
          if (!isset($pageQuery[$key])) {
              continue;
          }
          $rest = $pageQuery;
          unset($rest[$key]);
        ?>
        <a class="filter-chip" href="<?= url('/store' . ($rest ? '?' . http_build_query($rest) : '')) ?>">
          <?= e($label($pageQuery[$key])) ?> <i class="bi bi-x-lg"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-ps-outline btn-sm d-lg-none" id="mobileFilterToggle">
        <i class="bi bi-funnel me-1"></i> Filter Products
      </button>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 sidebar-col">
        <?php $this->partial('partials/store-filters', [
          'categories' => $categories,
          'brands' => $brands,
          'filters' => $filters,
          'priceRange' => $priceRange,
          'facets' => $facets,
          'hasReviews' => $hasReviews,
        ]); ?>
      </div>
      <div class="col-lg-9">
        <?php if (empty($products)): ?>
          <div class="glass-card p-5 text-center text-white-50">
            <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
            No products match these filters.
            <div class="mt-3"><a href="<?= url('/store') ?>" class="btn btn-ps btn-sm">Clear all filters</a></div>
          </div>
        <?php else: ?>
        <div class="row g-4">
          <?php foreach ($products as $product): ?>
          <div class="col-6 col-lg-4 d-flex align-items-stretch">
            <div class="glass-card product-card w-100 d-flex flex-column justify-content-between position-relative">
              <a href="<?= url('/store/' . $product['slug']) ?>" class="text-decoration-none text-white d-flex flex-column justify-content-between flex-grow-1">
                <div>
                  <div class="product-thumb position-relative">
                    <?= media_tile($product['image'], $product['name'], 'bi-box-seam') ?>
                    <button type="button" class="product-wishlist-btn" title="Add to Wishlist" onclick="event.preventDefault(); event.stopPropagation(); this.classList.toggle('active');">
                      <i class="bi bi-heart-fill"></i>
                    </button>
                    <?php if (!empty($product['offer_is_live']) && !empty($product['discount_percent'])): ?>
                      <span class="product-discount-tag"><?= (int) $product['discount_percent'] ?>% OFF</span>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex gap-1 flex-wrap mb-1">
                    <span class="cat-tag"><?= e($product['category_name']) ?></span>
                    <?php if (!empty($product['created_at']) && strtotime($product['created_at']) >= strtotime('-30 days')): ?>
                      <span class="badge bg-info text-dark font-weight-semibold">New Arrival</span>
                    <?php endif; ?>
                    <?php if (in_array((int) $product['id'], $bestSellerIds, true)): ?>
                      <span class="badge bg-warning text-dark fw-bold"><i class="bi bi-fire"></i> Best Seller</span>
                    <?php endif; ?>
                    <?php if (!empty($product['is_featured'])): ?>
                      <span class="badge bg-primary text-white font-weight-semibold">Featured</span>
                    <?php endif; ?>
                    <?php if (in_array((int) $product['id'], $popularIds, true)): ?>
                      <span class="badge" style="background:#ff6a1a">Trending</span>
                    <?php endif; ?>
                    <?php if (!empty($product['bogo_enabled'])): ?>
                      <span class="badge" style="background:#ff6a1a">BOGO</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($product['brand_name'])): ?>
                    <div class="brand-name-tag text-orange fw-bold text-uppercase mb-1">
                      <?= e($product['brand_name']) ?>
                    </div>
                  <?php endif; ?>
                  <h6 class="product-title text-white fw-bold mb-1"><?= e($product['name']) ?></h6>
                  <div class="product-rating-row d-flex align-items-center gap-1 my-1">
                    <?php $reviewCount = (int) ($product['review_count'] ?? 0); ?>
                    <?php if ($reviewCount > 0): ?>
                      <?php $avg = round((float) $product['avg_rating'], 1); ?>
                      <span class="text-warning small">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                          <i class="bi bi-star<?= $avg >= $s ? '-fill' : ($avg >= $s - 0.5 ? '-half' : '') ?>"></i>
                        <?php endfor; ?>
                      </span>
                      <span class="text-white fw-bold small ms-1"><?= number_format($avg, 1) ?></span>
                      <span class="text-white-50 small">(<?= $reviewCount ?>)</span>
                    <?php else: ?>
                      <span class="text-white-50 small"><i class="bi bi-star me-1"></i>No reviews yet</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div>
                  <?php if (!empty($product['offer_is_live']) && (float) $product['display_price'] < (float) $product['selling_price']): ?>
                    <?php $savings = (float) $product['selling_price'] - (float) $product['display_price']; ?>
                    <div class="price-box my-1">
                      <div class="d-flex align-items-baseline gap-2 flex-wrap">
                        <span class="text-orange fw-bold fs-5">৳<?= number_format((float) $product['display_price']) ?></span>
                        <small class="text-white-50 text-decoration-line-through">৳<?= number_format((float) $product['selling_price']) ?></small>
                      </div>
                      <div class="d-flex align-items-center gap-1 mt-1">
                        <span class="badge bg-success text-white fw-semibold" style="font-size: 0.72rem;">
                          Save ৳<?= number_format($savings) ?>
                        </span>
                        <?php if (!empty($product['discount_label'])): ?>
                          <span class="badge bg-danger text-white" style="font-size:.65rem">⚡ <?= e($product['discount_label']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="price-box my-1">
                      <div class="text-white fw-bold fs-5">৳<?= number_format((float) $product['selling_price']) ?></div>
                    </div>
                  <?php endif; ?>
                  <?php if ($product['stock_qty'] <= 0): ?>
                    <span class="stock-status-badge badge-out"><i class="bi bi-x-circle-fill me-1"></i>🔴 Out of Stock</span>
                  <?php elseif ($product['stock_qty'] <= 5): ?>
                    <span class="stock-status-badge badge-low"><i class="bi bi-exclamation-triangle-fill me-1"></i>🟡 Only <?= (int) $product['stock_qty'] ?> Left</span>
                  <?php elseif ($product['stock_qty'] <= 20): ?>
                    <span class="stock-status-badge badge-in"><i class="bi bi-check-circle-fill me-1"></i>🟢 In Stock</span>
                  <?php else: ?>
                    <span class="stock-status-badge badge-plenty"><i class="bi bi-check-circle-fill me-1"></i>🟢 Plenty</span>
                  <?php endif; ?>
                </div>
              </a>
              <?php if ((int) $product['stock_qty'] > 0 || ($product['allow_preorder'] && Feature::on('preorder'))): ?>
              <form method="post" action="<?= url('/cart/add') ?>" class="mt-3">
                <?= Security::csrfField() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn btn-ps btn-sm w-100 font-weight-bold d-flex align-items-center justify-content-center gap-1">
                  <i class="bi bi-cart-plus fs-6"></i> Add to Cart
                </button>
              </form>
              <?php else: ?>
              <button type="button" class="btn btn-secondary btn-sm w-100 mt-3" disabled>
                <i class="bi bi-slash-circle me-1"></i> Out of Stock
              </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
          <ul class="pagination pagination-ps justify-content-center">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('/store?' . http_build_query(['page' => $p] + $pageQuery)) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
// Sidebar behaviour: filters apply on change (the Apply button is the no-JS fallback), only one
// section is open at a time, and the panel is pinned to the exact height of one product card.
(function () {
  var form = document.getElementById('shopFilters');
  if (!form) return;

  var scroller = form.querySelector('.sidebar-content, .shop-filters-scroll');
  var groups = Array.prototype.slice.call(form.querySelectorAll('.filter-group'));
  var store = window.sessionStorage;

  form.addEventListener('change', function (event) {
    if (event.target.matches('input[type="radio"], input[type="checkbox"], select')) {
      form.submit();
    }
  });

  form.querySelectorAll('.price-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      form.querySelector('[name="min_price"]').value = chip.dataset.min || '';
      form.querySelector('[name="max_price"]').value = chip.dataset.max || '';
      form.submit();
    });
  });

  // Accordion — opening one section collapses the rest, so the panel never outgrows its box.
  var remembered = store.getItem('psFilterGroup');
  if (remembered) {
    groups.forEach(function (group) {
      group.open = group.dataset.group === remembered;
    });
  }
  groups.forEach(function (group) {
    group.addEventListener('toggle', function () {
      if (!group.open) return;
      store.setItem('psFilterGroup', group.dataset.group);
      groups.forEach(function (other) {
        if (other !== group) other.open = false;
      });
      if (scroller) scroller.scrollTop = 0;
    });
  });

  if (scroller) {
    var savedScroll = store.getItem('psFilterScroll');
    if (savedScroll) scroller.scrollTop = parseInt(savedScroll, 10) || 0;
    form.addEventListener('submit', function () {
      store.setItem('psFilterScroll', String(scroller.scrollTop));
    });
  }

  // Match the sidebar to a real product card height so top and bottom edges align exactly.
  var card = document.querySelector('.product-card');
  if (!card) return;

  function syncHeight() {
    if (window.innerWidth < 992) {
      form.style.removeProperty('--ps-shop-sidebar-h');
      form.style.removeProperty('height');
      return;
    }
    var height = card.offsetHeight;
    if (height > 0) {
      form.style.setProperty('--ps-shop-sidebar-h', height + 'px');
      form.style.height = height + 'px';
    }
  }

  syncHeight();
  window.addEventListener('resize', syncHeight);
  window.addEventListener('load', syncHeight);
  var img = card.querySelector('img');
  if (img && !img.complete) {
    img.addEventListener('load', syncHeight);
  }
})();
</script>
