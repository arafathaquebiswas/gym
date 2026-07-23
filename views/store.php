<?php
$pageTitle = 'Store';
/** @var array $products */
/** @var array $categories */
/** @var string|null $activeCategory */
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
            <li class="mb-2"><a href="<?= url('/store') ?>" class="<?= !$activeCategory ? 'text-orange fw-semibold' : '' ?>">All Products</a></li>
            <?php foreach ($categories as $cat): ?>
            <li class="mb-2">
              <a href="<?= url('/store?category=' . urlencode($cat['slug'])) ?>" class="<?= $activeCategory === $cat['slug'] ? 'text-orange fw-semibold' : '' ?>">
                <?= e($cat['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
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
                  <?php if (strtotime($product['created_at']) >= strtotime('-14 days')): ?><span class="badge bg-info text-dark">New Arrival</span><?php endif; ?>
                  <?php if (in_array((int) $product['id'], $bestSellerIds, true)): ?><span class="badge bg-primary">Best Seller</span><?php endif; ?>
                  <?php if (in_array((int) $product['id'], $popularIds, true)): ?><span class="badge" style="background:#ff6a1a">Popular</span><?php endif; ?>
                </div>
                <h6 class="mb-1 text-white"><?= e($product['name']) ?></h6>
                <?php if (!empty($product['offer_is_live'])): ?>
                  <div class="price">৳<?= number_format((float) $product['offer_price']) ?> <small class="text-white-50 text-decoration-line-through">৳<?= number_format((float) $product['selling_price']) ?></small></div>
                <?php else: ?>
                  <div class="price">৳<?= number_format((float) $product['selling_price']) ?></div>
                <?php endif; ?>
                <?php if ($product['stock_qty'] <= 0): ?>
                  <span class="badge bg-danger mt-2"><?= $product['allow_preorder'] ? 'Pre-Order' : 'Out of Stock' ?></span>
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
                  'page' => $p, 'category' => $activeCategory, 'q' => $search, 'sort' => $sort, 'in_stock' => $inStockOnly ? '1' : null,
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
