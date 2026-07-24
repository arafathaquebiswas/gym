<?php
/** @var array $orders */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
$statusColors = ['delivered' => 'success', 'delivery_failed' => 'danger', 'returned' => 'dark'];
$statusLabels = DeliveryController::STATUS_LABELS;
?>
<div class="admin-card">
  <h6 class="mb-3">Delivery History (<?= (int) $total ?>)</h6>

  <?php if (empty($orders)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No completed deliveries yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Order</th><th>Customer</th><th>Address</th><th>Status</th><th>Completed</th><th>Note</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td><a href="<?= url('/delivery/orders/' . $order['id']) ?>"><?= e($order['order_no']) ?></a></td>
          <td><?= e($order['account_name'] ?? $order['guest_name']) ?></td>
          <td class="small"><?= e(order_delivery_label($order)) ?></td>
          <td><span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e($statusLabels[$order['status']] ?? ucfirst($order['status'])) ?></span></td>
          <td class="small"><?= format_date($order['updated_at'], 'd M Y, h:i A') ?></td>
          <td class="small text-white-50"><?= e($order['delivery_note'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= url('/delivery/history?page=' . $i) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
