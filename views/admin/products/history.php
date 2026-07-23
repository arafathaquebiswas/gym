<?php
/** @var array $product */
/** @var array $movements */
/** @var int $pendingNotifications */
$typeColors = ['order' => 'primary', 'sale' => 'info', 'purchase' => 'success', 'adjustment' => 'secondary', 'return' => 'dark'];
$typeLabels = ['order' => 'Order', 'sale' => 'POS Sale', 'purchase' => 'Purchase', 'adjustment' => 'Manual Adjustment', 'return' => 'Return'];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="mb-0">Inventory History — <?= e($product['name']) ?></h5>
    <span class="text-white-50 small">Current stock: <?= (int) $product['stock_qty'] ?> · SKU: <?= e($product['sku']) ?></span>
  </div>
  <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline btn-sm">Back to Products</a>
</div>

<?php if ($pendingNotifications > 0): ?>
<div class="alert alert-info"><?= (int) $pendingNotifications ?> customer(s) are waiting to be notified when this product is back in stock.</div>
<?php endif; ?>

<div class="admin-card">
  <h6 class="mb-3">Stock Movements</h6>
  <?php if (empty($movements)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No stock movements recorded yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Type</th><th>Change</th><th>Note</th><th>By</th></tr></thead>
      <tbody>
        <?php foreach ($movements as $m): ?>
        <tr>
          <td><?= format_date($m['created_at'], 'd M Y, h:i A') ?></td>
          <td><span class="badge text-bg-<?= $typeColors[$m['type']] ?? 'secondary' ?>"><?= e($typeLabels[$m['type']] ?? ucfirst($m['type'])) ?></span></td>
          <td class="<?= $m['change_qty'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?></td>
          <td><?= e($m['note'] ?? '—') ?></td>
          <td><?= e($m['created_by_name'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
