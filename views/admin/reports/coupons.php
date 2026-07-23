<?php
/** @var array $rows */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Coupon Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>
  <p class="text-white-50 small mb-0">"Approx. Discount Given" is the total discount recorded on orders/sales where this coupon was used — it may include other stacked discounts (BOGO, bundle, manual) on the same transaction, not only the coupon's share.</p>
</div>

<div class="admin-card">
  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No coupons created yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Title</th><th class="text-end">Uses in Range</th><th class="text-end">Lifetime Uses</th><th class="text-end">Approx. Discount Given</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><code><?= e($r['code']) ?></code></td>
          <td><?= e($r['title']) ?></td>
          <td class="text-end"><?= (int) $r['uses_in_range'] ?></td>
          <td class="text-end"><?= (int) $r['lifetime_uses'] ?></td>
          <td class="text-end"><?= money((float) $r['approx_discount_given']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
