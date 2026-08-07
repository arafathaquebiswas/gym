<?php
/**
 * Customer Invoice (A4 Print & PDF)
 * ---------------------------------
 * @var array  $order
 * @var array  $items
 * @var bool|null $isPdf
 */

$isPdfMode = !empty($isPdf);
$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

$settingModel = new Setting();
$gymName      = $settingModel->get('gym_name', 'POWERSURGE GYM & NUTRITION');
$gymPhone     = $settingModel->get('gym_phone', '01904-485009');
$gymEmail     = $settingModel->get('gym_email', 'info@powersurgegym.com');
$gymAddress   = $settingModel->get('gym_address', '123 Fitness Ave, Suite 100, Dhaka');
$gymWebsite   = $settingModel->get('gym_website', 'www.powersurgegym.com');

// Base64 Logo Resolver
$resolveImageBase64 = function (?string $path, string $fallbackAsset) use ($basePath): string {
    if (!empty($path)) {
        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            $clean = ltrim(preg_replace('/^(uploads\/|assets\/)/', '', $path), '/');
            $uploadFile = $basePath . '/uploads/' . $clean;
            $assetFile  = $basePath . '/assets/' . $clean;

            if (file_exists($uploadFile) && is_file($uploadFile)) {
                $ext  = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
                $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($uploadFile));
            }
            if (file_exists($assetFile) && is_file($assetFile)) {
                $ext  = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
                $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($assetFile));
            }
        }
    }

    $fallbackFile = $basePath . '/assets/' . ltrim($fallbackAsset, '/');
    if (file_exists($fallbackFile) && is_file($fallbackFile)) {
        $ext  = strtolower(pathinfo($fallbackFile, PATHINFO_EXTENSION));
        $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fallbackFile));
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
};

$logoSetting = $settingModel->get('gym_logo');
$gymLogo     = $resolveImageBase64($logoSetting, 'images/logo/logo.png');

$customerName  = $order['account_name']  ?? $order['guest_name']  ?? 'Guest Customer';
$customerEmail = $order['account_email'] ?? $order['guest_email'] ?? '—';
$customerPhone = $order['account_phone'] ?? $order['guest_phone'] ?? '—';
$memberCode    = $order['member_code']   ?? null;

$subtotal   = (float) $order['subtotal'];
$discount   = (float) $order['discount'];
$shipping   = (float) ($order['shipping_charge'] ?? 0);
$tax        = (float) ($order['tax'] ?? 0);
$grandTotal = (float) $order['total'];
$isPaid     = ($order['payment_status'] === 'paid');
$amountPaid = $isPaid ? $grandTotal : 0.00;
$amountDue  = $isPaid ? 0.00 : $grandTotal;

$paymentMethodLabel = strtoupper(str_replace('_', ' ', (string) $order['payment_method']));
$paymentStatusLabel = strtoupper((string) $order['payment_status']);
$orderStatusLabel   = strtoupper(str_replace('_', ' ', (string) $order['status']));
$invoiceNo          = 'INV-' . ($order['order_no'] ?? $order['id']);
$orderDate          = format_date($order['created_at'], 'd M Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invoice — <?= e($order['order_no']) ?></title>
  <style>
    /* ── Reset & Core Presentation ──────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      background: #f1f5f9;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #0f172a;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Luxury Top Action Bar ───────────────────────────────── */
    .print-control-bar {
      background: #0f172a;
      color: #ffffff;
      padding: 14px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .bar-left, .bar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .btn-nav {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: rgba(255, 255, 255, 0.1);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.25);
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .btn-nav:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.4);
      color: #ffffff;
    }
    .btn-print-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 20px;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #ffffff;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
      transition: all 0.2s ease;
    }
    .btn-print-primary:hover {
      background: linear-gradient(135deg, #d97706, #b45309);
      box-shadow: 0 6px 18px rgba(245, 158, 11, 0.45);
      transform: translateY(-1px);
    }
    .inv-badge-pill {
      background: #334155;
      color: #f8fafc;
      font-family: monospace;
      font-size: 12px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      border: 1px solid #475569;
    }

    /* ── Document Sheet Canvas ──────────────────────────────── */
    .invoice-wrapper {
      max-width: 860px;
      width: 92%;
      margin: 32px auto;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.12), 0 0 1px rgba(0, 0, 0, 0.08);
      padding: 40px 48px;
    }

    .invoice-header-table {
      width: 100%;
      border-collapse: collapse;
      border-bottom: 2px solid #e2e8f0;
      padding-bottom: 20px;
      margin-bottom: 24px;
    }
    .invoice-brand-logo {
      max-height: 52px;
      width: auto;
      display: inline-block;
    }
    .invoice-title {
      font-size: 28px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
      text-transform: uppercase;
      margin-bottom: 2px;
    }
    .invoice-no-subtitle {
      font-size: 14px;
      font-weight: 700;
      color: #475569;
    }
    .status-pill {
      display: inline-block;
      padding: 4px 10px;
      font-size: 10px;
      font-weight: 800;
      text-transform: uppercase;
      border-radius: 4px;
      letter-spacing: 0.5px;
    }
    .status-pill-paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-pill-pending { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
    .status-pill-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

    /* Info Cards Grid */
    .info-grid {
      display: flex;
      gap: 20px;
      margin-bottom: 24px;
    }
    .info-card {
      flex: 1;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 16px 20px;
    }
    .info-card-title {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      color: #64748b;
      letter-spacing: 0.8px;
      margin-bottom: 8px;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* Product Table */
    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
    }
    .invoice-table th {
      background: #f1f5f9;
      color: #334155;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      padding: 12px 14px;
      border-top: 1px solid #cbd5e1;
      border-bottom: 2px solid #cbd5e1;
      text-align: left;
    }
    .invoice-table td {
      padding: 12px 14px;
      border-bottom: 1px solid #e2e8f0;
      font-size: 13px;
      color: #0f172a;
      vertical-align: middle;
    }
    .product-thumb {
      width: 42px;
      height: 42px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      background: #fff;
    }

    /* Totals & Signatures */
    .summary-grid {
      display: flex;
      gap: 24px;
      margin-bottom: 28px;
    }
    .summary-left { flex: 1; }
    .summary-right { width: 340px; }
    .totals-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 16px 20px;
    }
    .totals-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      font-size: 13px;
      color: #334155;
    }
    .totals-row-grand {
      border-top: 2px solid #0f172a;
      border-bottom: 2px solid #0f172a;
      padding: 10px 0;
      margin-top: 6px;
      font-size: 16px;
      font-weight: 900;
      color: #0f172a;
    }

    .signatures-row {
      display: flex;
      justify-content: space-between;
      margin-top: 40px;
      padding-top: 20px;
    }
    .signature-col {
      width: 220px;
      text-align: center;
    }
    .signature-line {
      border-top: 1px solid #94a3b8;
      margin-bottom: 6px;
    }
    .signature-label {
      font-size: 11px;
      font-weight: 800;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .invoice-footer-note {
      border-top: 1px solid #e2e8f0;
      margin-top: 24px;
      padding-top: 12px;
      font-size: 11px;
      color: #64748b;
      text-align: center;
    }

    /* ── Print Override Engine ─────────────────────────────── */
    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    @media print {
      html, body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      .print-control-bar, .no-print {
        display: none !important;
      }
      .invoice-wrapper {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: #ffffff !important;
      }
    }
  </style>
</head>
<body>

<?php if (!$isPdfMode): ?>
<!-- ── Top Control Bar (Screen Only) ──────────────────────────────────── -->
<div class="print-control-bar no-print">
  <div class="bar-left">
    <a href="<?= url('/admin/orders/' . $order['id']) ?>" class="btn-nav">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Order
    </a>
    <span class="inv-badge-pill"><?= e($invoiceNo) ?></span>
  </div>
  <div class="bar-right">
    <button type="button" onclick="window.print()" class="btn-print-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Invoice (A4)
    </button>
    <a href="<?= url('/admin/orders/' . $order['id'] . '/pdf') ?>" class="btn-nav">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Download PDF
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ── Printable Sheet Canvas ──────────────────────────────────────────── -->
<div class="invoice-wrapper">

  <!-- Header Branding & Invoice Reference -->
  <table class="invoice-header-table">
    <tr>
      <td style="width: 55%; vertical-align: top;">
        <img src="<?= e($gymLogo) ?>" alt="<?= e($gymName) ?>" class="invoice-brand-logo" />
        <div style="font-size: 16px; font-weight: 900; color: #0f172a; margin-top: 6px; text-transform: uppercase;"><?= e($gymName) ?></div>
        <div style="font-size: 12px; color: #475569; line-height: 1.5; margin-top: 2px;">
          <?= e($gymAddress) ?><br>
          Phone: <?= e($gymPhone) ?> &nbsp;|&nbsp; Email: <?= e($gymEmail) ?><br>
          Web: <?= e($gymWebsite) ?>
        </div>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="invoice-title">INVOICE</div>
        <div class="invoice-no-subtitle"><?= e($invoiceNo) ?></div>
        <div style="font-size: 12px; color: #475569; margin-top: 4px;">
          <strong>Order Ref:</strong> #<?= e($order['order_no']) ?><br>
          <strong>Date:</strong> <?= e($orderDate) ?>
        </div>
        <div style="margin-top: 8px;">
          <span class="status-pill <?= $isPaid ? 'status-pill-paid' : 'status-pill-pending' ?>">
            Payment: <?= e($paymentStatusLabel) ?>
          </span>
          <span class="status-pill status-pill-info" style="margin-left:4px;">
            Status: <?= e($orderStatusLabel) ?>
          </span>
        </div>
      </td>
    </tr>
  </table>

  <!-- Customer & Fulfillment Information -->
  <div class="info-grid">
    <div class="info-card">
      <div class="info-card-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Customer Information
      </div>
      <div style="font-size: 14px; font-weight: 800; color: #0f172a;"><?= e($customerName) ?></div>
      <div style="font-size: 12px; color: #334155; margin-top: 4px; line-height: 1.5;">
        <strong>Phone:</strong> <?= e($customerPhone) ?><br>
        <strong>Email:</strong> <?= e($customerEmail) ?>
        <?php if ($memberCode): ?><br><strong>Member ID:</strong> <?= e($memberCode) ?><?php endif; ?>
        <?php if (!empty($order['delivery_address'])): ?>
          <br><strong>Billing Address:</strong> <?= e($order['delivery_address']) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="info-card">
      <div class="info-card-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Fulfillment &amp; Payment
      </div>
      <div style="font-size: 12px; color: #334155; line-height: 1.5;">
        <strong>Fulfillment:</strong> <?= e(ucwords(str_replace('_', ' ', $order['fulfillment_method'] ?? 'delivery'))) ?><br>
        <?php if (!empty($order['delivery_address'])): ?>
          <strong>Shipping Address:</strong> <?= e($order['delivery_address']) ?>, <?= e($order['delivery_city'] ?? '') ?><br>
        <?php endif; ?>
        <strong>Payment Method:</strong> <?= e($paymentMethodLabel) ?><br>
        <?php if (!empty($order['zone_name'])): ?>
          <strong>Delivery Zone:</strong> <?= e($order['zone_name']) ?><br>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Items Table -->
  <table class="invoice-table">
    <thead>
      <tr>
        <th style="width: 15%;">SKU</th>
        <th style="width: 10%; text-align: center;">Image</th>
        <th>Product Name</th>
        <th style="width: 8%; text-align: center;">Qty</th>
        <th style="width: 15%; text-align: right;">Unit Price</th>
        <th style="width: 12%; text-align: right;">Discount</th>
        <th style="width: 15%; text-align: right;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): 
        $itemUnitPrice = (float) ($item['unit_price'] ?? 0);
        $itemQty       = (int) ($item['qty'] ?? 1);
        $itemTotal     = (float) ($item['subtotal'] ?? ($itemUnitPrice * $itemQty));
        $itemDiscount  = max(0, ($itemUnitPrice * $itemQty) - $itemTotal);
        $itemImage     = $resolveImageBase64($item['image'] ?? null, 'images/logo/logo.png');
      ?>
      <tr>
        <td style="font-family: monospace; font-weight: 700; font-size: 11px; color: #475569;"><?= e($item['sku'] ?? '—') ?></td>
        <td style="text-align: center;">
          <img src="<?= e($itemImage) ?>" alt="" class="product-thumb">
        </td>
        <td style="font-weight: 700; color: #0f172a;"><?= e($item['product_name']) ?><?= !empty($item['variant_label']) ? ' (' . e($item['variant_label']) . ')' : '' ?></td>
        <td style="text-align: center; font-weight: 800;"><?= $itemQty ?></td>
        <td style="text-align: right;">BDT <?= number_format($itemUnitPrice, 2) ?></td>
        <td style="text-align: right; color: #64748b;"><?= $itemDiscount > 0 ? 'BDT ' . number_format($itemDiscount, 2) : '—' ?></td>
        <td style="text-align: right; font-weight: 800;">BDT <?= number_format($itemTotal, 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Summary & Terms Grid -->
  <div class="summary-grid">
    <div class="summary-left">
      <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px;">Terms &amp; Return Policy</div>
      <div style="font-size: 11px; color: #64748b; line-height: 1.5;">
        Thank you for choosing POWERSURGE GYM & NUTRITION! We appreciate your business. Please inspect your items upon delivery or pickup.<br>
        Returns or exchanges are accepted within 7 days of purchase with original receipt. Items must be unopened and in sellable condition.
      </div>
    </div>
    <div class="summary-right">
      <div class="totals-box">
        <div class="totals-row">
          <span>Subtotal</span>
          <span style="font-weight: 700;">BDT <?= number_format($subtotal, 2) ?></span>
        </div>
        <?php if ($discount > 0): ?>
        <div class="totals-row" style="color: #059669;">
          <span>Discount</span>
          <span style="font-weight: 700;">- BDT <?= number_format($discount, 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="totals-row">
          <span>Shipping Fee</span>
          <span style="font-weight: 700;"><?= $shipping > 0 ? 'BDT ' . number_format($shipping, 2) : '<span style="color:#059669;">FREE</span>' ?></span>
        </div>
        <?php if ($tax > 0): ?>
        <div class="totals-row">
          <span>Tax</span>
          <span style="font-weight: 700;">BDT <?= number_format($tax, 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="totals-row totals-row-grand">
          <span>Grand Total</span>
          <span>BDT <?= number_format($grandTotal, 2) ?></span>
        </div>
        <div class="totals-row" style="margin-top: 4px;">
          <span>Amount Paid</span>
          <span style="font-weight: 800; color: #059669;">BDT <?= number_format($amountPaid, 2) ?></span>
        </div>
        <div class="totals-row">
          <span>Amount Due</span>
          <span style="font-weight: 800; color: <?= $amountDue > 0 ? '#dc2626' : '#64748b' ?>;">BDT <?= number_format($amountDue, 2) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Signatures -->
  <div class="signatures-row">
    <div class="signature-col">
      <div class="signature-line"></div>
      <div class="signature-label">Customer Signature</div>
    </div>
    <div class="signature-col">
      <div class="signature-line"></div>
      <div class="signature-label">Authorized Signature</div>
    </div>
  </div>

  <!-- Footer Notice -->
  <div class="invoice-footer-note">
    <?= e($gymName) ?> &nbsp;|&nbsp; Phone: <?= e($gymPhone) ?> &nbsp;|&nbsp; Website: <?= e($gymWebsite) ?> &nbsp;|&nbsp; Page 1 of 1
  </div>

</div><!-- /invoice-wrapper -->

<script>
  if (window.location.search.indexOf('autoprint=1') !== -1 || window.location.search.indexOf('print=1') !== -1) {
    window.onload = function() {
      setTimeout(function() { window.print(); }, 250);
    };
  }
</script>
</body>
</html>
