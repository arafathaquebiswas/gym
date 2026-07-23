<?php
/** @var array $orders */
/** @var array $todayStats */
/** @var float $feePerOrder */
/** @var float|null $monthlyEarnings */
/** @var int|null $deliveredThisMonth */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Assigned Today</div><div class="fs-3 fw-bold text-orange"><?= (int) $todayStats['assignedToday'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Delivered Today</div><div class="fs-3 fw-bold text-orange"><?= (int) $todayStats['deliveredToday'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Active Deliveries</div><div class="fs-3 fw-bold text-orange"><?= count($orders) ?></div></div>
  </div>
  <?php if ($feePerOrder > 0): ?>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Earnings This Month <span class="text-white-50">(<?= (int) $deliveredThisMonth ?> delivered)</span></div><div class="fs-3 fw-bold text-orange">৳<?= number_format($monthlyEarnings) ?></div></div>
  </div>
  <?php endif; ?>
</div>

<div class="admin-card">
  <h6 class="mb-3">My Assigned Deliveries (<?= count($orders) ?>)</h6>

  <?php if (empty($orders)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No deliveries assigned to you right now.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Order</th><th>Customer</th><th>Address</th><th>Time Slot</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <?php $phone = $order['account_phone'] ?? $order['guest_phone'] ?? ''; ?>
        <tr>
          <td><?= e($order['order_no']) ?></td>
          <td>
            <?= e($order['account_name'] ?? $order['guest_name']) ?><br>
            <?php if ($phone): ?>
              <a href="tel:<?= e($phone) ?>" class="text-white-50 small text-decoration-none"><i class="bi bi-telephone"></i> <?= e($phone) ?></a>
            <?php endif; ?>
          </td>
          <td class="small">
            <?= e(order_delivery_label($order)) ?>
            <?php if ($order['fulfillment_method'] === 'delivery'): ?>
            <br><a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($order['delivery_address'] . ', ' . $order['delivery_city']) ?>" target="_blank" rel="noopener" class="small"><i class="bi bi-signpost-2"></i> Directions</a>
            <?php endif; ?>
          </td>
          <td class="small"><?= e($order['time_slot_label'] ?? '—') ?></td>
          <td><span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td>
          <td style="min-width:220px">
            <?php if (in_array($order['status'], ['delivered', 'cancelled', 'returned'], true)): ?>
              <span class="text-white-50 small">—</span>
            <?php else: ?>
            <form method="post" action="<?= url('/delivery/' . $order['id'] . '/status') ?>" class="d-flex flex-column gap-1">
              <?= Security::csrfField() ?>
              <div class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm">
                  <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Out for Delivery</option>
                  <option value="delivered">Delivered</option>
                </select>
                <button type="submit" class="btn btn-ps btn-sm text-nowrap">Update</button>
              </div>
              <input type="text" name="note" class="form-control form-control-sm" placeholder="Delivery note (optional) — e.g. left at gate">
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
