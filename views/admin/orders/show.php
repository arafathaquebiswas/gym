<?php
/** @var array $order */
/** @var array $items */
/** @var array $history */
/** @var array $transactions */
/** @var array $statuses */
/** @var array $customerHistory */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
$customerName = $order['account_name'] ?? $order['guest_name'];
$customerEmail = $order['account_email'] ?? $order['guest_email'];
$customerPhone = $order['account_phone'] ?? $order['guest_phone'] ?? '';
$isTerminal = in_array($order['status'], ['delivered', 'cancelled', 'returned'], true);
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
    <?php if ($customerPhone): ?><a href="tel:<?= e($customerPhone) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-telephone"></i> Call Customer</a><?php endif; ?>
    <?php if ($customerEmail): ?><a href="mailto:<?= e($customerEmail) ?>" class="btn btn-ps btn-sm"><i class="bi bi-envelope"></i> Email Customer</a><?php endif; ?>
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
      <div class="d-flex gap-2 mt-2 flex-wrap">
        <?php
        $quickAction = function (string $status, string $label, string $class, ?string $note = null, ?string $confirmMsg = null) use ($order) {
            $confirm = $confirmMsg ? " onclick=\"return confirm('" . addslashes($confirmMsg) . "');\"" : '';
            echo '<form method="post" action="' . url('/admin/orders/' . $order['id'] . '/status') . '">'
                . Security::csrfField()
                . '<input type="hidden" name="status" value="' . e($status) . '">'
                . ($note ? '<input type="hidden" name="note" value="' . e($note) . '">' : '')
                . '<button type="submit" class="btn ' . $class . ' btn-sm"' . $confirm . '>' . e($label) . '</button>'
                . '</form>';
        };
        ?>

        <?php if ($order['status'] === 'pending'): ?>
          <?php $quickAction('confirmed', 'Approve', 'btn-outline-success'); ?>
          <?php $quickAction('cancelled', 'Reject', 'btn-outline-danger', 'Rejected', 'Reject this order? Stock will be restored.'); ?>
        <?php endif; ?>

        <?php if ($order['status'] === 'confirmed'): ?>
          <?php $quickAction('preparing', 'Prepare', 'btn-outline-info'); ?>
        <?php endif; ?>

        <?php if ($order['status'] === 'preparing'): ?>
          <?php $quickAction('packed', 'Packed', 'btn-outline-info'); ?>
        <?php endif; ?>

        <?php if ($order['status'] === 'packed' && $order['fulfillment_method'] === 'delivery'): ?>
          <?php $quickAction('shipped', 'Mark Shipped', 'btn-outline-primary'); ?>
        <?php endif; ?>

        <?php if ($order['status'] === 'shipped'): ?>
          <?php $quickAction('delivered', 'Mark Delivered', 'btn-outline-success'); ?>
        <?php endif; ?>

        <?php if ($order['status'] === 'delivered'): ?>
          <?php $quickAction('returned', 'Mark Returned', 'btn-outline-dark', null, 'Mark this order as returned?'); ?>
        <?php endif; ?>

        <?php if (!$isTerminal && !in_array($order['status'], ['pending'], true)): ?>
          <?php $quickAction('cancelled', 'Cancel Order', 'btn-outline-danger', 'Cancelled by admin', 'Cancel this order? Stock will be restored.'); ?>
        <?php endif; ?>
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
      <?php if ($order['time_slot_label']): ?>
      <p class="text-white-50 small mb-0 mt-2">Preferred Time: <?= e($order['time_slot_label']) ?></p>
      <?php endif; ?>
      <?php if ($order['order_notes']): ?>
      <hr><h6 class="mb-2">Order Notes</h6>
      <p class="text-white-50 small mb-0"><?= nl2br(e($order['order_notes'])) ?></p>
      <?php endif; ?>
    </div>

    <?php if ($order['fulfillment_method'] === 'delivery'): ?>
    <div class="admin-card mb-4">
      <h6 class="mb-2">Delivery Assignment</h6>
      <?php if ($order['delivery_person_name']): ?>
        <p class="mb-2 small">Assigned to: <strong><?= e($order['delivery_person_name']) ?></strong><?= $order['delivery_person_phone'] ? ' (' . e($order['delivery_person_phone']) . ')' : '' ?></p>
      <?php else: ?>
        <p class="text-white-50 small mb-2">No delivery person assigned yet.</p>
      <?php endif; ?>
      <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/assign-delivery-person') ?>" class="d-flex gap-2">
        <?= Security::csrfField() ?>
        <select name="delivery_person_id" class="form-select form-select-sm">
          <option value="">— Unassigned —</option>
          <?php foreach ($deliveryStaff as $person): ?>
            <option value="<?= (int) $person['id'] ?>" <?= (int) ($order['delivery_person_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>><?= e($person['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ps-outline btn-sm">Save</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($order['fulfillment_method'] === 'pickup'): ?>
    <div class="admin-card mb-4">
      <h6 class="mb-2">Store Pickup</h6>
      <?php if ($order['status'] === 'delivered'): ?>
        <p class="text-success small mb-0"><i class="bi bi-check-circle"></i> Picked up by customer.</p>
      <?php else: ?>
        <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/status') ?>" class="mb-2">
          <?= Security::csrfField() ?><input type="hidden" name="status" value="ready_for_pickup">
          <button type="submit" class="btn btn-outline-info btn-sm w-100">Mark Ready for Pickup</button>
        </form>
        <form method="post" action="<?= url('/admin/orders/' . $order['id'] . '/confirm-pickup') ?>" class="d-flex gap-2">
          <?= Security::csrfField() ?>
          <input type="text" name="pin" class="form-control form-control-sm" placeholder="Enter customer's PIN" maxlength="6" required>
          <button type="submit" class="btn btn-ps btn-sm text-nowrap">Confirm Pickup</button>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

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

    <div class="admin-card mb-4">
      <h6 class="mb-2">Customer's Order History</h6>
      <?php if (empty($customerHistory)): ?>
        <p class="text-white-50 small mb-0">No other orders from this customer.</p>
      <?php else: ?>
        <?php foreach ($customerHistory as $past): ?>
        <div class="d-flex justify-content-between small mb-2">
          <a href="<?= url('/admin/orders/' . $past['id']) ?>"><?= e($past['order_no']) ?></a>
          <span class="badge text-bg-<?= $statusColors[$past['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $past['status']))) ?></span>
        </div>
        <div class="d-flex justify-content-between small text-white-50 mb-2">
          <span><?= format_date($past['created_at'], 'd M Y') ?></span>
          <span>৳<?= number_format((float) $past['total']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
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
