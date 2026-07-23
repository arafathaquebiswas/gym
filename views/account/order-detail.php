<?php
/** @var array $order */
/** @var array $items */
/** @var array $history */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h1 class="mb-0">Order <span class="text-orange">#<?= e($order['order_no']) ?></span></h1>
      <div class="d-flex gap-2">
        <a href="<?= url('/account/orders') ?>" class="btn btn-ps-outline btn-sm">All Orders</a>
        <a href="<?= url('/account/orders/' . $order['id'] . '/invoice') ?>" class="btn btn-ps btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download Invoice</a>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="glass-card p-4 mb-4">
          <h6 class="mb-3">Items</h6>
          <table class="admin-table w-100">
            <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr><td><?= e($item['product_name']) ?></td><td><?= (int) $item['qty'] ?></td><td>৳<?= number_format((float) $item['unit_price']) ?></td><td>৳<?= number_format((float) $item['subtotal']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="d-flex justify-content-between mt-3"><span>Subtotal</span><span>৳<?= number_format((float) $order['subtotal']) ?></span></div>
          <div class="d-flex justify-content-between"><span>Discount</span><span>৳<?= number_format((float) $order['discount']) ?></span></div>
          <div class="d-flex justify-content-between"><span>Shipping</span><span><?= (float) $order['shipping_charge'] > 0 ? '৳' . number_format((float) $order['shipping_charge']) : 'Free' ?></span></div>
          <div class="d-flex justify-content-between"><span>Tax</span><span>৳<?= number_format((float) $order['tax']) ?></span></div>
          <div class="d-flex justify-content-between fw-bold text-orange"><span>Total</span><span>৳<?= number_format((float) $order['total']) ?></span></div>
        </div>

        <div class="glass-card p-4">
          <h6 class="mb-3">Order Timeline</h6>
          <?php foreach ($history as $h): ?>
          <div class="booking-list-item">
            <strong><?= e(ucfirst(str_replace('_', ' ', $h['status']))) ?></strong>
            <div class="text-white-50 small"><?= format_date($h['created_at'], 'd M Y, h:i A') ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="glass-card p-4 mb-4">
          <h6 class="mb-2">Status</h6>
          <span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?> fs-6"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
          <div class="text-white-50 small mt-2">Payment: <?= e(ucfirst($order['payment_status'])) ?> (<?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?>)</div>
        </div>
        <div class="glass-card p-4">
          <h6 class="mb-2"><?= $order['fulfillment_method'] === 'pickup' ? 'Store Pickup' : 'Delivery Address' ?></h6>
          <?php if ($order['fulfillment_method'] === 'pickup'): ?>
          <p class="text-white-50 small mb-0"><?= e(order_delivery_label($order)) ?></p>
          <?php else: ?>
          <p class="text-white-50 small mb-0">
            <?= e($order['delivery_address']) ?><br>
            <?= e($order['delivery_city']) ?><?= $order['delivery_area'] ? ', ' . e($order['delivery_area']) : '' ?><br>
            <?= $order['delivery_postal_code'] ? e($order['delivery_postal_code']) : '' ?>
          </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
