<?php
/** @var array $rows */
/** @var array $topSelling */
/** @var array $leastSelling */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Product Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="admin-card h-100">
      <h6 class="mb-3">Top Selling Products</h6>
      <?php if (empty($topSelling)): ?>
        <p class="text-white-50 small mb-0">No sales in this range.</p>
      <?php else: ?>
      <table class="admin-table mb-0">
        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead>
        <tbody>
          <?php foreach ($topSelling as $r): ?>
          <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= (int) $r['qty_sold'] ?></td><td class="text-end"><?= money((float) $r['revenue']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="admin-card h-100">
      <h6 class="mb-3">Least Selling Products</h6>
      <?php if (empty($leastSelling)): ?>
        <p class="text-white-50 small mb-0">No products found.</p>
      <?php else: ?>
      <table class="admin-table mb-0">
        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead>
        <tbody>
          <?php foreach ($leastSelling as $r): ?>
          <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= (int) $r['qty_sold'] ?></td><td class="text-end"><?= money((float) $r['revenue']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="admin-card">
  <h6 class="mb-3">All Products <span class="text-white-50 small fw-normal">(<?= e($from) ?> to <?= e($to) ?>)</span></h6>
  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No products found.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th class="text-end">Qty Sold</th><th class="text-end">Revenue</th><th class="text-end">Current Stock</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['sku']) ?></td>
          <td><?= e($r['category_name']) ?></td>
          <td class="text-end"><?= (int) $r['qty_sold'] ?></td>
          <td class="text-end"><?= money((float) $r['revenue']) ?></td>
          <td class="text-end"><?= (int) $r['stock_qty'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
