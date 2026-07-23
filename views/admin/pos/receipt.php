<?php
/** @var array $sale */
/** @var array $items */
?>
<style>
  @media print {
    .admin-sidebar, .admin-topbar, .no-print { display: none !important; }
    .admin-main { margin: 0 !important; }
  }
</style>

<div class="admin-card mx-auto" style="max-width:640px;">
  <div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <a href="<?= url('/admin/pos') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> New Sale</a>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-ps-outline btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print Receipt</button>
      <a href="<?= url('/admin/pos/receipt/' . $sale['id'] . '/pdf') ?>" class="btn btn-ps btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
    </div>
  </div>

  <div class="text-center mb-3">
    <h5 class="mb-0">PowerSurge Gym</h5>
    <div class="text-white-50 small">Phone: 01904-485009</div>
  </div>

  <div class="d-flex justify-content-between small mb-2">
    <span>Invoice #<?= e($sale['invoice_no']) ?></span>
    <span><?= format_date($sale['sale_date'], 'd M Y, h:i A') ?></span>
  </div>
  <?php if ($sale['member_name']): ?>
    <div class="small mb-1">Member: <?= e($sale['member_name']) ?></div>
  <?php endif; ?>
  <div class="small mb-3">Served by: <?= e($sale['sold_by_name'] ?? '—') ?></div>

  <hr>

  <table class="admin-table w-100">
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= e($item['product_name']) ?><div class="text-white-50 small"><?= e($item['sku']) ?></div></td>
        <td><?= (int) $item['qty'] ?></td>
        <td><?= money((float) $item['unit_price']) ?></td>
        <td><?= money((float) $item['subtotal']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <hr>

  <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= money((float) $sale['subtotal']) ?></span></div>
  <div class="d-flex justify-content-between"><span>Discount</span><span><?= money((float) $sale['discount']) ?></span></div>
  <div class="d-flex justify-content-between fs-5 fw-bold text-orange"><span>Total</span><span><?= money((float) $sale['total']) ?></span></div>
  <div class="d-flex justify-content-between small text-white-50 mt-2">
    <span>Payment Method</span><span><?= e(strtoupper(str_replace('_', ' ', $sale['payment_method']))) ?></span>
  </div>

  <p class="text-center text-white-50 small mt-4 mb-0">Thank you for shopping at PowerSurge Gym!</p>
</div>
