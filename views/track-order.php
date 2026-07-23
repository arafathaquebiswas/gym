<?php
$pageTitle = 'Track Your Order';
/** @var array|null $order */
/** @var array $items */
/** @var array $history */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
$deliveryEstimate = (new Setting())->get('delivery_estimate_text', '3–5 business days');
?>

<section class="section">
  <div class="container">
    <h1 class="mb-4">Track Your <span class="text-orange">Order</span></h1>

    <div class="glass-card p-4 mb-4" style="max-width:600px;">
      <form method="post" action="<?= url('/track-order/find') ?>" class="form-ps row g-3">
        <?= Security::csrfField() ?>
        <div class="col-12">
          <label>Order Number</label>
          <input type="text" name="order_no" class="form-control" placeholder="e.g. ORD-20260723-0001" value="<?= e($order['order_no'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label>Email Address or Phone Number</label>
          <input type="text" name="identity" class="form-control" placeholder="The email or phone used at checkout" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps w-100">Track Order</button>
        </div>
      </form>
    </div>

    <?php if ($order): ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="admin-card mb-4" style="background:rgba(255,255,255,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0">Order <?= e($order['order_no']) ?></h5>
            <span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?> fs-6"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
          </div>

          <table class="admin-table w-100 mb-3">
            <thead><tr><th>Item</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr><td><?= e($item['product_name']) ?></td><td><?= (int) $item['qty'] ?></td><td><?= money((float) $item['subtotal']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= money((float) $order['subtotal']) ?></span></div>
          <div class="d-flex justify-content-between"><span>Discount</span><span><?= money((float) $order['discount']) ?></span></div>
          <div class="d-flex justify-content-between"><span>Shipping</span><span><?= (float) $order['shipping_charge'] > 0 ? money((float) $order['shipping_charge']) : 'Free' ?></span></div>
          <div class="d-flex justify-content-between"><span>Tax</span><span><?= money((float) $order['tax']) ?></span></div>
          <div class="d-flex justify-content-between fw-bold text-orange"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
          <div class="d-flex justify-content-between small text-white-50 mt-2">
            <span>Payment Method</span><span><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></span>
          </div>
          <div class="d-flex justify-content-between small text-white-50">
            <span>Payment Status</span><span><?= e(ucfirst($order['payment_status'])) ?></span>
          </div>

          <?php if (!in_array($order['status'], ['delivered', 'cancelled', 'returned'], true)): ?>
          <div class="d-flex justify-content-between small text-white-50 mt-2">
            <span>Estimated Delivery</span><span><?= e($deliveryEstimate) ?></span>
          </div>
          <?php endif; ?>

          <?php if (!empty($order['admin_notes'])): ?>
          <div class="alert alert-info mt-3 mb-0">
            <strong>Note from the gym:</strong> <?= nl2br(e($order['admin_notes'])) ?>
          </div>
          <?php endif; ?>

          <form method="post" action="<?= url('/track-order/invoice') ?>" class="mt-3">
            <?= Security::csrfField() ?>
            <input type="hidden" name="order_no" value="<?= e($order['order_no']) ?>">
            <input type="hidden" name="identity" value="<?= e($order['account_email'] ?? $order['guest_email'] ?? $order['account_phone'] ?? $order['guest_phone'] ?? '') ?>">
            <button type="submit" class="btn btn-ps-outline btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download Invoice</button>
          </form>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="admin-card" style="background:rgba(255,255,255,.03)">
          <h6 class="mb-3">Order Timeline</h6>
          <?php foreach ($history as $h): ?>
          <div class="booking-list-item">
            <strong><?= e(ucfirst(str_replace('_', ' ', $h['status']))) ?></strong>
            <div class="text-white-50 small"><?= format_date($h['created_at'], 'd M Y, h:i A') ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
