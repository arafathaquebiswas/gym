<?php $pageTitle = $product['name']; /** @var array $product */ ?>

<section class="section">
  <div class="container">
    <nav class="mb-4 small text-white-50">
      <a href="<?= url('/store') ?>" class="text-white-50">Store</a> /
      <a href="<?= url('/store?category=' . urlencode($product['category_slug'])) ?>" class="text-white-50"><?= e($product['category_name']) ?></a> /
      <span class="text-orange"><?= e($product['name']) ?></span>
    </nav>
    <div class="row g-5">
      <div class="col-lg-6">
        <div style="min-height:400px;border-radius:var(--ps-radius);overflow:hidden">
          <?= media_tile($product['image'], $product['name'], 'bi-box-seam', '') ?>
        </div>
      </div>
      <div class="col-lg-6">
        <span class="cat-tag"><?= e($product['category_name']) ?></span>
        <h1 class="mt-2"><?= e($product['name']) ?></h1>
        <p class="text-white-50"><?= e($product['brand']) ?></p>
        <div class="pkg-price mb-3">৳<?= number_format($product['selling_price']) ?></div>
        <p class="text-white-50"><?= nl2br(e($product['description'])) ?></p>
        <?php if ($product['stock_qty'] > 0): ?>
          <span class="badge bg-success">In Stock (<?= (int) $product['stock_qty'] ?>)</span>
        <?php else: ?>
          <span class="badge bg-danger">Out of Stock</span>
        <?php endif; ?>
        <div class="mt-4">
          <a href="<?= url('/contact') ?>" class="btn btn-ps">Inquire at Front Desk</a>
        </div>
        <p class="text-white-50 small mt-3"><i class="bi bi-info-circle"></i> Online checkout coming soon — purchases are currently completed in-store.</p>
      </div>
    </div>
  </div>
</section>
