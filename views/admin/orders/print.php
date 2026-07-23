<?php /** @var array $orders */ ?>
<style>
  @media print {
    .admin-sidebar, .admin-topbar, .no-print { display: none !important; }
    .admin-main { margin: 0 !important; }
  }
</style>

<div class="no-print mb-3 d-flex justify-content-between">
  <a href="<?= url('/admin/orders') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
  <button type="button" class="btn btn-ps btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<?php foreach ($orders as $order): ?>
<div class="admin-card mb-4" style="background:rgba(255,255,255,.03)">
  <div class="d-flex justify-content-between">
    <h6 class="mb-1">Order <?= e($order['order_no']) ?></h6>
    <span><?= format_date($order['created_at'], 'd M Y, h:i A') ?></span>
  </div>
  <div class="small text-white-50 mb-2">
    Customer: <?= e($order['account_name'] ?? $order['guest_name']) ?> &middot;
    Status: <?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?> &middot;
    Payment: <?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?> (<?= e(ucfirst($order['payment_status'])) ?>)
  </div>
  <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= money((float) $order['subtotal']) ?></span></div>
  <div class="d-flex justify-content-between"><span>Discount</span><span><?= money((float) $order['discount']) ?></span></div>
  <div class="d-flex justify-content-between"><span>Shipping</span><span><?= money((float) $order['shipping_charge']) ?></span></div>
  <div class="d-flex justify-content-between"><span>Tax</span><span><?= money((float) $order['tax']) ?></span></div>
  <div class="d-flex justify-content-between fw-bold text-orange"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
</div>
<?php endforeach; ?>
