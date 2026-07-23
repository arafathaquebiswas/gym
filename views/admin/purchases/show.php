<?php
/** @var array $purchase */
/** @var array $items */
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h5 class="mb-0">Purchase <?= e($purchase['invoice_no']) ?></h5>
  <a href="<?= url('/admin/purchases') ?>" class="btn btn-ps-outline btn-sm">All Purchases</a>
</div>

<div class="admin-card mb-4">
  <p class="mb-1">Supplier: <strong><?= e($purchase['supplier_name'] ?? '—') ?></strong></p>
  <p class="mb-1">Date: <?= format_date($purchase['purchase_date'], 'd M Y') ?></p>
  <p class="mb-0">Recorded by: <?= e($purchase['created_by_name'] ?? '—') ?></p>
</div>

<div class="admin-card">
  <h6 class="mb-3">Items</h6>
  <table class="admin-table w-100">
    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Cost</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= e($item['product_name']) ?></td>
        <td><?= e($item['sku']) ?></td>
        <td><?= (int) $item['qty'] ?></td>
        <td>৳<?= number_format((float) $item['unit_cost']) ?></td>
        <td>৳<?= number_format((float) $item['subtotal']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="d-flex justify-content-between fw-bold text-orange mt-3"><span>Total</span><span>৳<?= number_format((float) $purchase['total_amount']) ?></span></div>
</div>
