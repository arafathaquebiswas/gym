<?php
/** @var array $sales */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Store Sales (<?= (int) $total ?>)</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Products</a>
      <a href="<?= url('/admin/pos') ?>" class="btn btn-ps btn-sm"><i class="bi bi-cart-plus"></i> New Sale</a>
    </div>
  </div>

  <form method="get" action="<?= url('/admin/products/sales') ?>" class="admin-toolbar admin-form">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search invoice # or member" value="<?= e($filters['search']) ?>">
    <select name="payment_method" class="form-select form-select-sm">
      <option value="">All Payment Methods</option>
      <?php foreach (['cash' => 'Cash', 'card' => 'Card', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'bank_transfer' => 'Bank Transfer'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= $filters['payment_method'] === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
  </form>

  <?php if (empty($sales)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No sales recorded yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Invoice #</th><th>Date</th><th>Member</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Payment</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($sales as $sale): ?>
        <tr>
          <td><?= e($sale['invoice_no']) ?></td>
          <td><?= format_date($sale['sale_date'], 'd M Y, h:i A') ?></td>
          <td><?= e($sale['member_name'] ?? '—') ?></td>
          <td><?= money((float) $sale['subtotal']) ?></td>
          <td><?= money((float) $sale['discount']) ?></td>
          <td class="fw-semibold"><?= money((float) $sale['total']) ?></td>
          <td><span class="badge text-bg-secondary"><?= e(strtoupper(str_replace('_', ' ', $sale['payment_method']))) ?></span></td>
          <td><a href="<?= url('/admin/pos/receipt/' . $sale['id']) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-receipt"></i></a></td>
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
          <a class="page-link" href="<?= url('/admin/products/sales?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
