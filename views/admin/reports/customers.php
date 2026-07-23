<?php
/** @var array $rows */
/** @var int $repeatCustomers */
/** @var int $oneTimeCustomers */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Customer Report <span class="text-white-50 small fw-normal">(top 50 by spend)</span></h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <div class="d-flex gap-4">
    <div><span class="text-white-50 small">Repeat Customers</span><div class="fs-4 fw-bold text-orange"><?= (int) $repeatCustomers ?></div></div>
    <div><span class="text-white-50 small">One-Time Customers</span><div class="fs-4 fw-bold text-orange"><?= (int) $oneTimeCustomers ?></div></div>
  </div>
</div>

<div class="admin-card">
  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No orders in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Customer</th><th>Email</th><th class="text-end">Orders</th><th class="text-end">Total Spent</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['customer_name'] ?? 'Guest') ?><?php if ((int) $r['order_count'] > 1): ?> <span class="badge text-bg-success">Repeat</span><?php endif; ?></td>
          <td><?= e($r['customer_email'] ?? '—') ?></td>
          <td class="text-end"><?= (int) $r['order_count'] ?></td>
          <td class="text-end"><?= money((float) $r['total_spent']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
