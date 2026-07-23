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

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">Store</span>
    <h1>Supplements &amp; <span class="text-orange">Gym Gear</span></h1>
    <p class="lead mx-auto" style="max-width:600px">Everything you need to fuel training and recovery — available at the front desk.</p>
    <a href="<?= url('/bundles') ?>" class="btn btn-ps-outline btn-sm mt-2"><i class="bi bi-gift"></i> View Bundle Deals</a>
  </div>
</section>

<section class="section pt-0">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-3">
        <div class="glass-card p-3 mb-4">
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
          <div class="col-6 col-lg-4">
            <a href="<?= url('/store/' . $product['slug']) ?>" class="text-decoration-none">
              <div class="glass-card product-card">
                <div class="product-thumb"><?= media_tile($product['image'], $product['name'], 'bi-box-seam') ?></div>
                <div class="d-flex gap-1 flex-wrap mb-1">
                  <div class="cat-tag"><?= e($product['category_name']) ?></div>
                  <?php if (!empty($product['brand_name'])): ?><div class="cat-tag"><?= e($product['brand_name']) ?></div><?php endif; ?>
                  <?php if (strtotime($product['created_at']) >= strtotime('-14 days')): ?><span class="badge bg-info text-dark">New Arrival</span><?php endif; ?>
                  <?php if (in_array((int) $product['id'], $bestSellerIds, true)): ?><span class="badge bg-primary">Best Seller</span><?php endif; ?>
                  <?php if (in_array((int) $product['id'], $popularIds, true)): ?><span class="badge" style="background:#ff6a1a">Popular</span><?php endif; ?>
                  <?php if (!empty($product['bogo_enabled'])): ?><span class="badge" style="background:#ff6a1a">BOGO</span><?php endif; ?>
                </div>
                <h6 class="mb-1 text-white"><?= e($product['name']) ?></h6>
                <?php if (!empty($product['offer_is_live'])): ?>
                  <div class="price">৳<?= number_format((float) $product['display_price']) ?> <small class="text-white-50 text-decoration-line-through">৳<?= number_format((float) $product['selling_price']) ?></small></div>
                  <?php if ($product['discount_label']): ?><span class="badge" style="background:#ff6a1a;font-size:.65rem">⚡ <?= e($product['discount_label']) ?></span><?php endif; ?>
                <?php else: ?>
                  <div class="price">৳<?= number_format((float) $product['selling_price']) ?></div>
                <?php endif; ?>
                <?php if ($product['stock_qty'] <= 0): ?>
                  <span class="badge bg-danger mt-2"><?= ($product['allow_preorder'] && Feature::on('preorder')) ? 'Pre-Order' : 'Out of Stock' ?></span>
                <?php elseif ($product['stock_qty'] <= $product['min_stock']): ?>
                  <span class="badge bg-warning text-dark mt-2">Low Stock</span>
                <?php endif; ?>
              </div>
            </a>
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
