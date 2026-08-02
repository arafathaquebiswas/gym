<?php /** @var array $orders */ ?>
<style>
  @media screen {
    .print-order-card {
      background: rgba(255, 255, 255, .03);
      border: 1px solid var(--ps-border);
      border-radius: 8px;
      padding: 1.25rem;
      margin-bottom: 1.25rem;
    }
  }

  @media print {
    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    html, body, .admin-body {
      background: #ffffff !important;
      color: #111827 !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .admin-sidebar, .admin-topbar, .admin-overlay, .admin-breadcrumb, .no-print, .btn {
      display: none !important;
    }
    .admin-shell, .admin-main, .admin-content {
      display: block !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      background: #ffffff !important;
    }
    .print-order-card {
      background: #ffffff !important;
      border: 1px solid #d1d5db !important;
      color: #111827 !important;
      padding: 1rem !important;
      margin-bottom: 1rem !important;
      page-break-inside: avoid !important;
    }
    .print-order-card * {
      color: #111827 !important;
    }
  }
</style>

<div class="no-print mb-3 d-flex justify-content-between">
  <a href="<?= url('/admin/orders') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
  <button type="button" class="btn btn-ps btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<?php foreach ($orders as $order): ?>
<div class="print-order-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0 fw-bold">Order #<?= e($order['order_no']) ?></h6>
    <span class="small"><?= format_date($order['created_at'], 'd M Y, h:i A') ?></span>
  </div>
  <div class="small mb-3 border-bottom pb-2">
    Customer: <strong><?= e($order['account_name'] ?? $order['guest_name']) ?></strong> &middot;
    Status: <strong><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></strong> &middot;
    Payment: <strong><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?> (<?= e(ucfirst($order['payment_status'])) ?>)</strong> &middot;
    <?= $order['fulfillment_method'] === 'pickup' ? 'Pickup at ' : 'Delivering to ' ?><?= e(order_delivery_label($order)) ?>
  </div>
  <div class="d-flex justify-content-between small"><span>Subtotal</span><span><?= money((float) $order['subtotal']) ?></span></div>
  <div class="d-flex justify-content-between small"><span>Discount</span><span><?= money((float) $order['discount']) ?></span></div>
  <div class="d-flex justify-content-between small"><span>Shipping</span><span><?= money((float) $order['shipping_charge']) ?></span></div>
  <div class="d-flex justify-content-between small"><span>Tax</span><span><?= money((float) $order['tax']) ?></span></div>
  <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
</div>
<?php endforeach; ?>
