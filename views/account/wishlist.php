<?php
$pageTitle = 'My Wishlist';
/** @var array $items */
?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">My <span class="text-orange">Wishlist</span></h1>
      <a href="<?= url('/account') ?>" class="btn btn-ps-outline btn-sm">Back to Account</a>
    </div>

    <?php if (empty($items)): ?>
      <div class="glass-card p-5 text-center">
        <p class="text-white-50 mb-3">Your wishlist is empty.</p>
        <a href="<?= url('/store') ?>" class="btn btn-ps">Browse the Store</a>
      </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($items as $product): ?>
      <div class="col-6 col-lg-3">
        <div class="glass-card product-card">
          <a href="<?= url('/store/' . $product['slug']) ?>" class="text-decoration-none">
            <div class="product-thumb"><?= media_tile($product['image'], $product['name'], 'bi-box-seam') ?></div>
            <h6 class="mb-1 text-white"><?= e($product['name']) ?></h6>
            <div class="price">৳<?= number_format((float) $product['selling_price']) ?></div>
          </a>
          <form method="post" action="<?= url('/account/wishlist/remove') ?>" class="mt-2">
            <?= Security::csrfField() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i> Remove</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
