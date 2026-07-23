<?php
/** @var array $rows */
/** @var array $sourceLabels */
/** @var string $from */
/** @var string $to */
$grandRevenue = array_sum(array_column($rows, 'revenue'));
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Offer Performance</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>
  <p class="text-white-50 small mb-0">Only accurate for orders placed after this report shipped — earlier orders are counted as "Regular Price" here regardless of what they actually paid, since which offer applied wasn't recorded before now.</p>
</div>

<div class="admin-card">
  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No order items in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Discount Source</th><th class="text-end">Line Items</th><th class="text-end">Qty Sold</th><th class="text-end">Revenue</th><th class="text-end">% of Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($sourceLabels[$r['source']] ?? ucfirst($r['source'])) ?></td>
          <td class="text-end"><?= (int) $r['item_count'] ?></td>
          <td class="text-end"><?= (int) $r['qty_sold'] ?></td>
          <td class="text-end"><?= money((float) $r['revenue']) ?></td>
          <td class="text-end"><?= $grandRevenue > 0 ? round(((float) $r['revenue'] / $grandRevenue) * 100) . '%' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
