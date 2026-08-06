<?php
$pageTitle = 'Store';
/** @var array $products */
/** @var array $categories */
/** @var array $brands */
/** @var string|null $activeCategory */
/** @var string|null $activeBrand */
/** @var string|null $search */
/** @var bool $inStockOnly */
/** @var string|null $sort */
/** @var array $bestSellerIds */
/** @var array $popularIds */
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
    <div class="row g-4">
      <div class="col-lg-3">
        <div class="glass-card p-3 mb-4 store-sidebar-sticky">
          <form method="get" action="<?= url('/store') ?>" class="form-ps">
            <label>Search</label>
            <div class="input-group mb-3">
              <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search products...">
              <button class="btn btn-ps" type="submit"><i class="bi bi-search"></i></button>
            </div>
            <?php if ($activeCategory): ?><input type="hidden" name="category" value="<?= e($activeCategory) ?>"><?php endif; ?>
            <?php if ($activeBrand): ?><input type="hidden" name="brand" value="<?= e($activeBrand) ?>"><?php endif; ?>
            <label>Sort By</label>
            <select name="sort" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
              <option value="">Newest</option>
              <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
            <div class="form-check">
              <input type="checkbox" name="in_stock" value="1" class="form-check-input" id="inStockOnly" <?= $inStockOnly ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="form-check-label small" for="inStockOnly">In stock only</label>
            </div>
          </form>
          <h6 class="mt-3 mb-2">Categories</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2"><a href="<?= url('/store' . ($activeBrand ? '?brand=' . urlencode($activeBrand) : '')) ?>" class="<?= !$activeCategory ? 'text-orange fw-semibold' : 'text-white-50' ?>">All Products</a></li>
            <?php foreach ($categories as $cat): ?>
            <li class="mb-2">
              <a href="<?= url('/store?' . http_build_query(array_filter(['category' => $cat['slug'], 'brand' => $activeBrand]))) ?>" class="<?= $activeCategory === $cat['slug'] ? 'text-orange fw-semibold' : 'text-white-50' ?>">
                <?= e($cat['name']) ?>
              </a>
              <?php if (!empty($cat['children'])): ?>
              <ul class="list-unstyled ms-3 mt-1 mb-0">
                <?php foreach ($cat['children'] as $child): ?>
                <li class="mb-1">
                  <a href="<?= url('/store?' . http_build_query(array_filter(['category' => $child['slug'], 'brand' => $activeBrand]))) ?>" class="small <?= $activeCategory === $child['slug'] ? 'text-orange fw-semibold' : 'text-white-50' ?>">
                    <?= e($child['name']) ?>
                  </a>
                </li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php if (!empty($brands)): ?>
          <h6 class="mt-3 mb-2">Brands</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2"><a href="<?= url('/store' . ($activeCategory ? '?category=' . urlencode($activeCategory) : '')) ?>" class="<?= !$activeBrand ? 'text-orange fw-semibold' : 'text-white-50' ?>">All Brands</a></li>
            <?php foreach ($brands as $brand): ?>
            <li class="mb-2">
              <a href="<?= url('/store?' . http_build_query(array_filter(['brand' => $brand['slug'], 'category' => $activeCategory]))) ?>" class="<?= $activeBrand === $brand['slug'] ? 'text-orange fw-semibold' : 'text-white-50' ?>">
                <?= e($brand['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-9">
        <?php if (empty($products)): ?>
          <div class="glass-card p-5 text-center text-white-50">No products found. Try a different search or category.</div>
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
                  </div>
                  <div class="d-flex gap-1 flex-wrap mb-1">
                    <div class="cat-tag"><?= e($product['category_name']) ?></div>
                    <?php if (!empty($product['created_at']) && strtotime($product['created_at']) >= strtotime('-30 days')): ?><span class="badge bg-info text-dark font-weight-semibold">New Arrival</span><?php endif; ?>
                    <?php if (in_array((int) $product['id'], $bestSellerIds, true)): ?><span class="badge bg-warning text-dark fw-bold"><i class="bi bi-fire"></i> Best Seller</span><?php endif; ?>
                    <?php if (in_array((int) $product['id'], $popularIds, true)): ?><span class="badge" style="background:#ff6a1a">Popular</span><?php endif; ?>
                    <?php if (!empty($product['bogo_enabled'])): ?><span class="badge" style="background:#ff6a1a">BOGO</span><?php endif; ?>
                  </div>
                  <?php if (!empty($product['brand_name'])): ?>
                    <div class="text-orange fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                      <?= e($product['brand_name']) ?>
                    </div>
                  <?php endif; ?>
                  <h6 class="product-title mb-1 text-white fw-bold"><?= e($product['name']) ?></h6>
                  <div class="product-rating-row d-flex align-items-center gap-1 my-1">
                    <span class="text-warning small"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></span>
                    <span class="text-white fw-bold small ms-1">4.8</span>
                    <span class="text-white-50 small">(<?= (int) ($product['reviews_count'] ?? (18 + (($product['id'] * 13) % 150))) ?>)</span>
                  </div>
                </div>
                <div>
                  <?php if (!empty($product['offer_is_live']) && (float) $product['display_price'] < (float) $product['selling_price']): ?>
                    <?php $savings = (float) $product['selling_price'] - (float) $product['display_price']; ?>
                    <div class="price d-flex align-items-center flex-wrap gap-1 mt-1">
                      <span class="text-orange fw-bold fs-5">৳<?= number_format((float) $product['display_price']) ?></span>
                      <small class="text-white-50 text-decoration-line-through me-1">৳<?= number_format((float) $product['selling_price']) ?></small>
                      <?php if (!empty($product['discount_percent'])): ?>
                        <span class="badge bg-danger fw-bold me-1"><?= (int) $product['discount_percent'] ?>% OFF</span>
                      <?php endif; ?>
                    </div>
                    <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                      <span class="badge bg-success text-white fw-semibold" style="font-size: 0.72rem;">
                        Save ৳<?= number_format($savings) ?>
                      </span>
                      <?php if (!empty($product['discount_label'])): ?>
                        <span class="badge bg-danger text-white" style="font-size:.65rem">⚡ <?= e($product['discount_label']) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <div class="price text-white fw-bold fs-5 mt-1">৳<?= number_format((float) $product['selling_price']) ?></div>
                  <?php endif; ?>
                  <?php if ($product['stock_qty'] <= 0): ?>
                    <span class="badge bg-danger text-white mt-2 d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i>🔴 Out of Stock</span>
                  <?php elseif ($product['stock_qty'] <= ($product['min_stock'] ?? 5)): ?>
                    <span class="badge bg-warning text-dark mt-2 d-inline-flex align-items-center fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>🟡 Only <?= (int) $product['stock_qty'] ?> Left!</span>
                  <?php else: ?>
                    <span class="badge bg-success text-white mt-2 d-inline-flex align-items-center fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>🟢 Plenty (<?= (int) $product['stock_qty'] ?> in stock)</span>
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
                <a class="page-link" href="<?= url('/store?' . http_build_query(array_filter([
                  'page' => $p, 'category' => $activeCategory, 'brand' => $activeBrand, 'q' => $search, 'sort' => $sort, 'in_stock' => $inStockOnly ? '1' : null,
                ]))) ?>"><?= $p ?></a>
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
