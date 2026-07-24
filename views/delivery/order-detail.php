<?php
/** @var array $order */
/** @var array $items */
/** @var array $history */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'ready_for_pickup' => 'info', 'picked_up' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'delivery_failed' => 'danger', 'cancelled' => 'danger', 'returned' => 'dark'];
$statusLabels = DeliveryController::STATUS_LABELS;
$phone = $order['account_phone'] ?? $order['guest_phone'] ?? '';
?>
<div class="mb-3">
  <a href="<?= url('/delivery') ?>" class="text-white-50 text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to My Deliveries</a>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="admin-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">Order <?= e($order['order_no']) ?></h5>
        <span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?> fs-6"><?= e($statusLabels[$order['status']] ?? ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
      </div>

      <h6 class="text-white-50 small text-uppercase mb-2">Customer Information</h6>
      <p class="mb-1"><?= e($order['account_name'] ?? $order['guest_name']) ?></p>
      <?php if ($phone): ?>
        <p class="mb-1"><a href="tel:<?= e($phone) ?>"><i class="bi bi-telephone"></i> <?= e($phone) ?></a></p>
      <?php endif; ?>
      <p class="text-white-50 small mb-3"><?= $order['fulfillment_method'] === 'pickup' ? 'Pickup at' : 'Delivering to' ?>: <?= e(order_delivery_label($order)) ?></p>
      <?php if ($order['fulfillment_method'] === 'delivery'): ?>
      <p class="mb-3"><a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($order['delivery_address'] . ', ' . $order['delivery_city']) ?>" target="_blank" rel="noopener" class="btn btn-ps-outline btn-sm"><i class="bi bi-signpost-2"></i> Directions</a></p>
      <?php endif; ?>

      <hr>
      <h6 class="text-white-50 small text-uppercase mb-2">Items</h6>
      <table class="admin-table w-100 mb-3">
        <thead><tr><th>Item</th><th>Qty</th><th>Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr><td><?= e($item['product_name']) ?></td><td><?= (int) $item['qty'] ?></td><td><?= money((float) $item['subtotal']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="d-flex justify-content-between fw-bold text-orange"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
      <div class="d-flex justify-content-between small text-white-50 mt-2">
        <span>Payment Method</span><span><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></span>
      </div>
      <div class="d-flex justify-content-between small text-white-50">
        <span>Payment Status</span><span><?= e(ucfirst($order['payment_status'])) ?></span>
      </div>

      <?php if (!empty($order['pickup_pin']) && $order['fulfillment_method'] === 'pickup'): ?>
      <div class="alert alert-info mt-3 mb-0">Pickup PIN: <strong><?= e($order['pickup_pin']) ?></strong></div>
      <?php endif; ?>
      <?php if (!empty($order['order_notes'])): ?>
      <div class="alert alert-secondary mt-3 mb-0"><strong>Customer note:</strong> <?= nl2br(e($order['order_notes'])) ?></div>
      <?php endif; ?>
    </div>

    <?php if (!in_array($order['status'], ['delivered', 'delivery_failed', 'cancelled', 'returned'], true)): ?>
    <div class="admin-card">
      <h6 class="mb-3">Update Delivery Status</h6>
      <form method="post" action="<?= url('/delivery/' . $order['id'] . '/status') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-6">
          <select name="status" class="form-select">
            <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>On the Way</option>
            <option value="delivered">Delivered</option>
            <option value="delivery_failed">Delivery Failed</option>
            <option value="returned">Returned</option>
          </select>
        </div>
        <div class="col-md-6">
          <input type="text" name="note" class="form-control" placeholder="Delivery note (optional) — e.g. left at gate">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps btn-sm">Update Status</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="admin-card">
      <h6 class="mb-3">Delivery Timeline</h6>
      <?php if (empty($history)): ?>
        <p class="text-white-50 small mb-0">No status history yet.</p>
      <?php endif; ?>
      <?php foreach ($history as $h): ?>
      <div class="booking-list-item">
        <strong><?= e($statusLabels[$h['status']] ?? ucfirst(str_replace('_', ' ', $h['status']))) ?></strong>
        <div class="text-white-50 small"><?= format_date($h['created_at'], 'd M Y, h:i A') ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
