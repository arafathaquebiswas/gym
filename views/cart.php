<?php $pageTitle = 'Your Cart'; /** @var array $lines */ /** @var float $subtotal */ ?>

<section class="section">
  <div class="container">
    <h1 class="mb-4">Your <span class="text-orange">Cart</span></h1>

    <?php if (empty($lines)): ?>
      <div class="glass-card p-5 text-center">
        <p class="text-white-50 mb-3">Your cart is empty.</p>
        <a href="<?= url('/store') ?>" class="btn btn-ps">Continue Shopping</a>
      </div>
    <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="glass-card p-4">
          <?php foreach ($lines as $line): ?>
          <div class="d-flex align-items-center gap-3 py-3 border-bottom border-secondary-subtle">
            <div style="width:70px;height:70px;flex-shrink:0;border-radius:8px;overflow:hidden">
              <?= media_tile($line['image'], $line['name'], 'bi-box-seam') ?>
            </div>
            <div class="flex-grow-1">
              <a href="<?= url('/store/' . $line['slug']) ?>" class="text-white text-decoration-none fw-semibold"><?= e($line['name']) ?></a>
              <div class="text-orange small">৳<?= number_format((float) $line['display_price']) ?></div>
            </div>
            <form method="post" action="<?= url('/cart/update') ?>" class="d-flex align-items-center gap-1">
              <?= Security::csrfField() ?>
              <input type="hidden" name="product_id" value="<?= (int) $line['id'] ?>">
              <input type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="1" max="<?= max(1, (int) $line['stock_qty']) ?>" class="form-control form-control-sm" style="width:70px" onchange="this.form.submit()">
            </form>
            <div class="fw-semibold" style="min-width:90px;text-align:right">৳<?= number_format((float) $line['display_price'] * (int) $line['qty']) ?></div>
            <form method="post" action="<?= url('/cart/remove') ?>">
              <?= Security::csrfField() ?>
              <input type="hidden" name="product_id" value="<?= (int) $line['id'] ?>">
              <button type="submit" class="btn btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
            </form>
          </div>
          <?php endforeach; ?>
          <div class="pt-3">
            <a href="<?= url('/store') ?>" class="btn btn-ps-outline btn-sm">Continue Shopping</a>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="glass-card p-4">
          <h6 class="mb-3">Order Summary</h6>
          <div class="d-flex justify-content-between mb-2"><span class="text-white-50">Subtotal</span><span>৳<?= number_format($subtotal) ?></span></div>
          <p class="text-white-50 small">Coupon codes and shipping are applied at checkout.</p>
          <a href="<?= url('/checkout') ?>" class="btn btn-ps w-100">Proceed to Checkout</a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
