<?php
/** @var array $products */
/** @var float $totalValue */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Stock Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <div class="fs-4 fw-bold text-orange"><?= money($totalValue) ?> <span class="fs-6 text-white-50 fw-normal">total inventory value (at buying price)</span></div>
</div>

<div class="admin-card">
  <?php if (empty($products)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No products found.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Min. Stock</th><th>Buying Price</th><th>Value</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($products as $product): $low = $product['stock_qty'] <= $product['min_stock']; ?>
        <tr class="<?= $low ? 'table-danger bg-opacity-25' : '' ?>">
          <td><?= e($product['name']) ?></td>
          <td><?= e($product['category_name']) ?></td>
          <td><?= (int) $product['stock_qty'] ?></td>
          <td><?= (int) $product['min_stock'] ?></td>
          <td><?= money((float) $product['buying_price']) ?></td>
          <td><?= money((float) $product['stock_qty'] * (float) $product['buying_price']) ?></td>
          <td><?php if ($low): ?><span class="badge text-bg-danger">Low Stock</span><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
