<?php
/** @var array $purchases */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Purchases (<?= (int) $total ?>)</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/suppliers') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-people"></i> Suppliers</a>
      <a href="<?= url('/admin/purchases/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Record Purchase</a>
    </div>
  </div>

  <?php if (empty($purchases)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No purchases recorded yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Invoice #</th><th>Supplier</th><th>Date</th><th>Total</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($purchases as $purchase): ?>
        <tr>
          <td><?= e($purchase['invoice_no']) ?></td>
          <td><?= e($purchase['supplier_name'] ?? '—') ?></td>
          <td><?= format_date($purchase['purchase_date'], 'd M Y') ?></td>
          <td>৳<?= number_format((float) $purchase['total_amount']) ?></td>
          <td><a href="<?= url('/admin/purchases/' . $purchase['id']) ?>" class="btn btn-ps-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm mb-0">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= url('/admin/purchases?page=' . $i) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
