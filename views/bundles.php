<?php
$pageTitle = 'Bundle Deals';
/** @var array $bundles */
?>

<section class="section">
  <div class="container">
    <h1 class="mb-2">Bundle <span class="text-orange">Deals</span></h1>
    <p class="text-white-50 mb-4">Buy these products together and save.</p>

    <?php if (empty($bundles)): ?>
      <div class="glass-card p-5 text-center text-white-50">No bundle deals available right now — check back soon.</div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($bundles as $entry): $bundle = $entry['bundle']; $items = $entry['items']; ?>
      <?php $regularTotal = array_sum(array_map(fn ($i) => (float) $i['selling_price'] * (int) $i['qty'], $items)); ?>
      <?php $savings = round($regularTotal - (float) $bundle['bundle_price'], 2); ?>
      <div class="col-lg-6">
        <div class="glass-card p-4">
          <h5 class="mb-2"><?= e($bundle['name']) ?></h5>
          <ul class="list-unstyled text-white-50 small mb-3">
            <?php foreach ($items as $item): ?>
              <li><i class="bi bi-check-circle text-orange"></i> <?= (int) $item['qty'] ?> × <?= e($item['product_name']) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="d-flex align-items-baseline gap-2 mb-1">
            <div class="pkg-price">৳<?= number_format((float) $bundle['bundle_price']) ?></div>
            <div class="text-white-50 text-decoration-line-through">৳<?= number_format($regularTotal) ?></div>
          </div>
          <?php if ($savings > 0): ?>
            <span class="pkg-savings small">Save ৳<?= number_format($savings) ?></span>
          <?php endif; ?>
          <?php if ($bundle['ends_at']): ?>
            <p class="text-white-50 small mt-2 mb-0">Available until <?= format_date($bundle['ends_at'], 'd M Y') ?></p>
          <?php endif; ?>
          <form method="post" action="<?= url('/cart/add-bundle/' . $bundle['id']) ?>" class="mt-3">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-ps w-100"><i class="bi bi-cart-plus"></i> Add Bundle to Cart</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
