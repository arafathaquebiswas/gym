<?php
/** @var array $order */
/** @var array $items */
$customerName = $order['account_name'] ?? $order['guest_name'];
?>
<style>
  @media print {
    .admin-sidebar, .admin-topbar, .no-print { display: none !important; }
    .admin-main { margin: 0 !important; }
  }
</style>

<div class="admin-card mx-auto" style="max-width:640px;">
  <div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <a href="<?= url('/admin/orders/' . $order['id']) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-ps-outline btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      <a href="<?= url('/admin/orders/' . $order['id'] . '/pdf') ?>" class="btn btn-ps btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
    </div>
  </div>

  <div class="text-center mb-3">
    <h5 class="mb-0">PowerSurge Gym</h5>
    <div class="text-white-50 small">Phone: 01904-485009</div>
  </div>

  <div class="d-flex justify-content-between small mb-2">
    <span>Order #<?= e($order['order_no']) ?></span>
    <span><?= format_date($order['created_at'], 'd M Y, h:i A') ?></span>
  </div>
  <div class="small mb-1">Customer: <?= e($customerName) ?></div>
  <div class="small mb-3"><?= $order['fulfillment_method'] === 'pickup' ? 'Pickup at' : 'Delivering to' ?>: <?= e(order_delivery_label($order)) ?></div>

  <hr>

  <table class="admin-table w-100">
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= e($item['product_name']) ?><div class="text-white-50 small"><?= e($item['sku']) ?></div></td>
        <td><?= (int) $item['qty'] ?></td>
        <td><?= money((float) $item['unit_price']) ?></td>
        <td><?= money((float) $item['subtotal']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <hr>

  <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= money((float) $order['subtotal']) ?></span></div>
  <div class="d-flex justify-content-between"><span>Discount</span><span><?= money((float) $order['discount']) ?></span></div>
  <div class="d-flex justify-content-between"><span>Shipping</span><span><?= (float) $order['shipping_charge'] > 0 ? money((float) $order['shipping_charge']) : 'Free' ?></span></div>
  <div class="d-flex justify-content-between"><span>Tax</span><span><?= money((float) $order['tax']) ?></span></div>
  <div class="d-flex justify-content-between fs-5 fw-bold text-orange"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
  <div class="d-flex justify-content-between small text-white-50 mt-2">
    <span>Payment Method</span><span><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></span>
  </div>

  <p class="text-center text-white-50 small mt-4 mb-0">Thank you for shopping at PowerSurge Gym!</p>
</div>
