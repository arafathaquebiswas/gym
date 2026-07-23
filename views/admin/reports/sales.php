<?php
/** @var array $rows */
/** @var string $group */
/** @var string $from */
/** @var string $to */
/** @var float $grandTotal */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Sales Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>
  <div class="mb-3">
    <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&group=daily" class="btn btn-ps-outline btn-sm <?= $group === 'daily' ? 'active' : '' ?>">Daily</a>
    <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&group=monthly" class="btn btn-ps-outline btn-sm <?= $group === 'monthly' ? 'active' : '' ?>">Monthly</a>
  </div>

  <div class="fs-4 fw-bold text-orange mb-3"><?= money($grandTotal) ?> <span class="fs-6 text-white-50 fw-normal">total in range</span></div>

  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No sales in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th><?= $group === 'monthly' ? 'Month' : 'Date' ?></th><th>Sales</th><th>Subtotal</th><th>Discount</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['period']) ?></td>
          <td><?= (int) $row['sale_count'] ?></td>
          <td><?= money((float) $row['subtotal']) ?></td>
          <td><?= money((float) $row['discount']) ?></td>
          <td class="fw-semibold"><?= money((float) $row['total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
