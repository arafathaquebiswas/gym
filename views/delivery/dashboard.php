<?php
/** @var array $orders */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>
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
        <tr>
          <td><?= e($order['order_no']) ?></td>
          <td>
            <?= e($order['account_name'] ?? $order['guest_name']) ?><br>
            <span class="text-white-50 small"><?= e($order['account_phone'] ?? $order['guest_phone'] ?? '') ?></span>
          </td>
          <td class="small"><?= e(order_delivery_label($order)) ?></td>
          <td class="small"><?= e($order['time_slot_label'] ?? '—') ?></td>
          <td><span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td>
          <td>
            <?php if (in_array($order['status'], ['delivered', 'cancelled', 'returned'], true)): ?>
              <span class="text-white-50 small">—</span>
            <?php else: ?>
            <form method="post" action="<?= url('/delivery/' . $order['id'] . '/status') ?>" class="d-flex gap-2">
              <?= Security::csrfField() ?>
              <select name="status" class="form-select form-select-sm">
                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Out for Delivery</option>
                <option value="delivered">Delivered</option>
              </select>
              <button type="submit" class="btn btn-ps btn-sm">Update</button>
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
