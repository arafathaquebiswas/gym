<?php
/** @var array $order */
/** @var array $items */
/** @var array $history */
/** @var array $transactions */
/** @var array $statuses */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
$customerName = $order['account_name'] ?? $order['guest_name'];
$customerEmail = $order['account_email'] ?? $order['guest_email'];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="mb-0">Order <?= e($order['order_no']) ?></h5>
    <span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/admin/orders') ?>" class="btn btn-ps-outline btn-sm">All Orders</a>
    <a href="<?= url('/admin/orders/' . $order['id'] . '/receipt') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-printer"></i> Print</a>
    <a href="<?= url('/admin/orders/' . $order['id'] . '/pdf') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php if ($customerEmail): ?><a href="mailto:<?= e($customerEmail) ?>" class="btn btn-ps btn-sm"><i class="bi bi-envelope"></i> Contact Customer</a><?php endif; ?>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="admin-card mb-4">
      <h6 class="mb-3">Items</h6>
      <table class="admin-table w-100">
        <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr><td><?= e($item['product_name']) ?></td><td><?= e($item['sku']) ?></td><td><?= (int) $item['qty'] ?></td><td>৳<?= number_format((float) $item['unit_price']) ?></td><td>৳<?= number_format((float) $item['subtotal']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="d-flex justify-content-between mt-3"><span>Subtotal</span><span>৳<?= number_format((float) $order['subtotal']) ?></span></div>
      <div class="d-flex justify-content-between"><span>Discount</span><span>৳<?= number_format((float) $order['discount']) ?></span></div>
      <div class="d-flex justify-content-between"><span>Shipping</span><span><?= (float) $order['shipping_charge'] > 0 ? '৳' . number_format((float) $order['shipping_charge']) : 'Free' ?></span></div>
      <div class="d-flex justify-content-between"><span>Tax</span><span>৳<?= number_format((float) $order['tax']) ?></span></div>
      <div class="d-flex justify-content-between fw-bold text-orange"><span>Total</span><span>৳<?= number_format((float) $order['total']) ?></span></div>
    </div>

    <div class="admin-card mb-4">
      <h6 class="mb-3">Update Status</h6>
      <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/status') ?>" class="row g-2 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-5">
          <select name="status" class="form-select">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <input type="text" name="note" class="form-control" placeholder="Optional note">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-ps w-100">Update</button>
        </div>
      </form>
      <div class="d-flex gap-2 mt-2">
        <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/status') ?>">
          <?= Security::csrfField() ?><input type="hidden" name="status" value="confirmed">
          <button type="submit" class="btn btn-outline-success btn-sm">Approve</button>
        </form>
        <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/status') ?>">
          <?= Security::csrfField() ?><input type="hidden" name="status" value="cancelled">
          <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this order? Stock will be restored.');">Reject / Cancel</button>
        </form>
      </div>
    </div>

    <div class="admin-card">
      <h6 class="mb-3">Order Timeline</h6>
      <?php foreach ($history as $h): ?>
      <div class="booking-list-item">
        <strong><?= e(ucfirst(str_replace('_', ' ', $h['status']))) ?></strong>
        <div class="text-white-50 small">
          <?= format_date($h['created_at'], 'd M Y, h:i A') ?>
          <?= $h['changed_by_name'] ? ' by ' . e($h['changed_by_name']) : '' ?>
          <?= $h['note'] ? ' — ' . e($h['note']) : '' ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card mb-4">
      <h6 class="mb-2">Customer</h6>
      <p class="mb-1"><?= e($customerName) ?></p>
      <?php if ($customerEmail): ?><p class="text-white-50 small mb-1"><?= e($customerEmail) ?></p><?php endif; ?>
      <p class="text-white-50 small mb-0"><?= e($order['guest_phone'] ?? '') ?></p>
      <hr>
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
      <?php if ($order['order_notes']): ?>
      <hr><h6 class="mb-2">Order Notes</h6>
      <p class="text-white-50 small mb-0"><?= nl2br(e($order['order_notes'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="admin-card mb-4">
      <h6 class="mb-2">Note to Customer <small class="text-white-50">(shown on their order tracking page)</small></h6>
      <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/notes') ?>">
        <?= Security::csrfField() ?>
        <textarea name="admin_notes" class="form-control mb-2" rows="2"><?= e($order['admin_notes'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-ps-outline btn-sm">Save Note</button>
      </form>
    </div>

    <div class="admin-card mb-4">
      <h6 class="mb-3">Payment</h6>
      <p class="mb-2">Method: <strong><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></strong></p>
      <p class="mb-2">Status: <span class="badge text-bg-<?= $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'refunded' ? 'dark' : 'secondary') ?>"><?= e(ucfirst($order['payment_status'])) ?></span></p>
      <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/payment-status') ?>" class="d-flex gap-2 mb-3">
        <?= Security::csrfField() ?>
        <select name="payment_status" class="form-select form-select-sm">
          <?php foreach (['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $order['payment_status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ps-outline btn-sm">Save</button>
      </form>

      <?php if (empty($transactions)): ?>
        <p class="text-white-50 small mb-0">No payment reference submitted.</p>
      <?php else: ?>
        <?php foreach ($transactions as $tx): ?>
        <div class="small text-white-50 mb-2">
          Ref: <?= e($tx['reference_no'] ?? '—') ?> — <span class="badge text-bg-secondary"><?= e(ucfirst($tx['status'])) ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <hr>
      <?php if (!empty($refunds)): ?>
        <h6 class="small text-white-50 mb-2">Refund History</h6>
        <?php foreach ($refunds as $refund): ?>
        <div class="small text-white-50 mb-2">
          <?= money((float) $refund['amount']) ?> — <?= e($refund['reason']) ?>
          <div>By <?= e($refund['refunded_by_name'] ?? 'System') ?> on <?= format_date($refund['created_at'], 'd M Y, h:i A') ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal"><i class="bi bi-arrow-counterclockwise"></i> Refund</button>
    </div>
  </div>
</div>

<div class="modal fade" id="refundModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/refund') ?>" onsubmit="return confirm('Record a refund of the entered amount for this order? This cannot be undone.');">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Refund Order <?= e($order['order_no']) ?></h6></div>
        <div class="modal-body">
          <label>Refund Amount (৳)</label>
          <input type="number" step="0.01" min="0.01" max="<?= (float) $order['total'] ?>" name="amount" class="form-control mb-2" value="<?= (float) $order['total'] ?>" required>
          <label>Refund Reason</label>
          <textarea name="reason" class="form-control" rows="3" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-outline-danger btn-sm">Confirm Refund</button>
        </div>
      </form>
    </div>
  </div>
</div>
