<?php
/** @var array $sale */
/** @var array $items */
/** @var bool|null $isPdf */

$isPdfMode = !empty($isPdf);
$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

$settingModel = new Setting();
$gymName = $settingModel->get('gym_name', 'POWERSURGE GYM & NUTRITION');
$gymPhone = $settingModel->get('gym_phone', '01904-485009');
$gymEmail = $settingModel->get('gym_email', 'info@powersurgegym.com');
$gymAddress = $settingModel->get('gym_address', '123 Fitness Ave, Suite 100, City');
$gymWebsite = 'www.powersurgegym.com';

// Universal Image Resolver: Ensures all image URIs are clean, valid Base64 Data URIs without raw HTML/XML markup
$resolveImageBase64 = function (?string $path, string $fallbackAsset) use ($basePath): string {
    if (!empty($path)) {
        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            $clean = ltrim(preg_replace('/^(uploads\/|assets\/)/', '', $path), '/');
            $uploadFile = $basePath . '/uploads/' . $clean;
            $assetFile = $basePath . '/assets/' . $clean;

            if (file_exists($uploadFile) && is_file($uploadFile)) {
                $ext = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
                $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($uploadFile));
            }
            if (file_exists($assetFile) && is_file($assetFile)) {
                $ext = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
                $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($assetFile));
            }
        }
    }

    $fallbackFile = $basePath . '/assets/' . ltrim($fallbackAsset, '/');
    if (file_exists($fallbackFile) && is_file($fallbackFile)) {
        $ext = strtolower(pathinfo($fallbackFile, PATHINFO_EXTENSION));
        $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fallbackFile));
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
};

$logoSetting = $settingModel->get('gym_logo');
$gymLogo = $resolveImageBase64($logoSetting, 'images/logo/logo.png');

$customerName = $sale['member_name'] ?? 'Walk-in Customer';
$servedBy = $sale['sold_by_name'] ?? 'Staff';

$subtotal = (float) $sale['subtotal'];
$discount = (float) $sale['discount'];
$tax = (float) ($sale['tax'] ?? 0);
$grandTotal = (float) $sale['total'];
$isPaid = ($sale['payment_status'] === 'paid');
$amountPaid = $isPaid ? $grandTotal : 0.00;
$amountDue = $isPaid ? 0.00 : $grandTotal;

$paymentMethodLabel = strtoupper(str_replace('_', ' ', (string) $sale['payment_method']));
$paymentStatusLabel = strtoupper((string) $sale['payment_status']);
?>
<style>
  /* Universal Invoice Layout Styles */
  .invoice-wrapper {
    max-width: 860px;
    width: 100%;
    margin: 0 auto;
    background: #ffffff;
    color: #111827;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 24px 32px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }
  .invoice-header-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 14px;
    margin-bottom: 18px;
  }
  .invoice-brand-logo {
    max-height: 48px;
    width: auto;
    display: inline-block;
    vertical-align: middle;
  }
  .invoice-title {
    font-size: 24px;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.5px;
    text-transform: uppercase;
  }
  .invoice-badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    border-radius: 4px;
    letter-spacing: 0.5px;
  }
  .invoice-badge-paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
  .invoice-badge-pending { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }

  .info-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 12px 0;
    margin-bottom: 18px;
  }
  .info-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px 14px;
    vertical-align: top;
  }
  .info-box-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 4px;
  }

  .invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
    margin-bottom: 18px;
  }
  .invoice-table th {
    background: #f3f4f6;
    color: #374151;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 10px;
    border-top: 1px solid #d1d5db;
    border-bottom: 2px solid #d1d5db;
    text-align: left;
  }
  .invoice-table td {
    padding: 9px 10px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    color: #1f2937;
    vertical-align: middle;
  }
  .item-thumb {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    display: inline-block;
  }

  .totals-layout-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
  }
  .totals-table {
    width: 100%;
    max-width: 320px;
    margin-left: auto;
    border-collapse: collapse;
  }
  .totals-table td {
    padding: 4px 8px;
    font-size: 12px;
    color: #374151;
  }
  .totals-table td.amount {
    text-align: right;
    font-weight: 600;
    color: #111827;
  }
  .totals-table tr.grand-total td {
    border-top: 2px solid #111827;
    border-bottom: 2px solid #111827;
    font-size: 15px;
    font-weight: 800;
    color: #111827;
    padding: 7px 8px;
  }

  .signature-table {
    width: 100%;
    margin-top: 36px;
    border-collapse: collapse;
  }
  .signature-line {
    width: 180px;
    border-top: 1px solid #6b7280;
    text-align: center;
    padding-top: 4px;
    font-size: 10px;
    font-weight: 600;
    color: #4b5563;
    text-transform: uppercase;
  }

  /* Optimized A4 Print Stylesheet: 190mm Printable Width & 10mm Margins */
  @media print {
    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    html, body {
      background: #ffffff !important;
      color: #111827 !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      overflow: visible !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .admin-sidebar, .admin-topbar, .admin-overlay, .admin-breadcrumb, .no-print, .btn, nav, header, footer {
      display: none !important;
    }
    .admin-shell, .admin-main, .admin-content, .admin-page-shell, .admin-body {
      display: block !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      background: #ffffff !important;
      box-shadow: none !important;
      border: none !important;
    }
    .invoice-wrapper {
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      box-shadow: none !important;
      border: none !important;
      background: #ffffff !important;
    }
    .invoice-table thead {
      display: table-header-group !important;
    }
    .invoice-table tr {
      page-break-inside: avoid !important;
    }
  }
</style>

<div class="invoice-wrapper">
  <?php if (!$isPdfMode): ?>
    <!-- Top Action Bar & Print Tip Notice (Screen Only) -->
    <div class="no-print mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
        <a href="<?= url('/admin/pos') ?>" class="btn btn-ps-outline btn-sm">
          <i class="bi bi-arrow-left"></i> Back to POS
        </a>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-ps-outline btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Invoice (A4)
          </button>
          <a href="<?= url('/admin/pos/receipt/' . $sale['id'] . '/pdf') ?>" class="btn btn-ps btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
          </a>
        </div>
      </div>
      <div class="p-2 px-3 bg-light border rounded-3 d-flex align-items-center gap-2" style="font-size: 11px; color: #4b5563;">
        <i class="bi bi-info-circle text-primary fs-6"></i>
        <span><strong>Clean Print Tip:</strong> In your browser print dialog, uncheck <em>"Headers and footers"</em> (to remove date/URL) and check <em>"Background graphics"</em> (for full-color badges).</span>
      </div>
    </div>
  <?php endif; ?>

  <!-- Invoice Header -->
  <table class="invoice-header-table">
    <tr>
      <td style="vertical-align: top; text-align: left;">
        <div style="margin-bottom: 4px;">
          <?php if ($gymLogo): ?>
            <img src="<?= $gymLogo ?>" alt="<?= e($gymName) ?>" class="invoice-brand-logo" />
          <?php endif; ?>
          <span style="font-size: 20px; font-weight: 800; color: #111827; text-transform: uppercase; vertical-align: middle; margin-left: 6px;">
            <?= e($gymName) ?>
          </span>
        </div>
        <div style="font-size: 11px; color: #4b5563; line-height: 1.4;">
          <div><?= e($gymAddress) ?></div>
          <div>Phone: <?= e($gymPhone) ?> | Email: <?= e($gymEmail) ?></div>
          <div>Web: <?= e($gymWebsite) ?></div>
        </div>
      </td>

      <td style="vertical-align: top; text-align: right;">
        <div class="invoice-title">POS INVOICE</div>
        <div style="font-size: 13px; font-weight: 700; color: #111827;">INV-<?= e($sale['invoice_no']) ?></div>
        <div style="font-size: 11px; color: #4b5563; margin-top: 4px;">
          <div><strong>Date:</strong> <?= format_date($sale['sale_date'], 'd M Y, h:i A') ?></div>
        </div>
        <div style="margin-top: 6px;">
          <span class="invoice-badge <?= $isPaid ? 'invoice-badge-paid' : 'invoice-badge-pending' ?>">
            Payment: <?= e($paymentStatusLabel) ?>
          </span>
        </div>
      </td>
    </tr>
  </table>

  <!-- Customer & Sale Information (2-Column Box) -->
  <table class="info-table">
    <tr>
      <td class="info-box" style="width: 50%;">
        <div class="info-box-title">CUSTOMER INFORMATION</div>
        <div style="font-size: 12px; line-height: 1.5; color: #1f2937;">
          <div style="font-weight: 700; font-size: 13px; color: #111827;"><?= e($customerName) ?></div>
          <?php if (!empty($sale['member_id'])): ?>
            <div><span style="color:#6b7280;">Member ID:</span> <strong>MEM-<?= (int) $sale['member_id'] ?></strong></div>
          <?php endif; ?>
          <div><span style="color:#6b7280;">Type:</span> <?= !empty($sale['member_id']) ? 'Gym Member' : 'Store Customer' ?></div>
        </div>
      </td>

      <td class="info-box" style="width: 50%;">
        <div class="info-box-title">SALE & PAYMENT DETAILS</div>
        <div style="font-size: 12px; line-height: 1.5; color: #1f2937;">
          <div><span style="color:#6b7280;">Served By:</span> <strong><?= e($servedBy) ?></strong></div>
          <div><span style="color:#6b7280;">Payment Method:</span> <strong><?= e($paymentMethodLabel) ?></strong></div>
          <div><span style="color:#6b7280;">Channel:</span> Point of Sale (In-Store)</div>
        </div>
      </td>
    </tr>
  </table>

  <!-- Itemized Product Table -->
  <table class="invoice-table">
    <thead>
      <tr>
        <th style="width: 15%;">SKU</th>
        <th style="width: 10%; text-align:center;">IMAGE</th>
        <th style="width: 45%;">PRODUCT NAME</th>
        <th style="width: 10%; text-align:center;">QTY</th>
        <th style="width: 10%; text-align:right;">UNIT PRICE</th>
        <th style="width: 10%; text-align:right;">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <?php
          $itemImg = $resolveImageBase64($item['product_image'] ?? null, 'images/defaults/product.png');
          $itemUnitPrice = (float) $item['unit_price'];
          $itemQty = (int) $item['qty'];
          $itemSubtotal = (float) $item['subtotal'];
        ?>
        <tr>
          <td style="font-size: 11px; font-family: monospace; color: #4b5563;"><?= e($item['sku'] ?? 'N/A') ?></td>
          <td style="text-align: center;">
            <img src="<?= $itemImg ?>" alt="<?= e($item['product_name']) ?>" class="item-thumb" />
          </td>
          <td>
            <div style="font-weight: 600; color: #111827;"><?= e($item['product_name']) ?></div>
          </td>
          <td style="text-align: center; font-weight: 600;"><?= $itemQty ?></td>
          <td style="text-align: right;"><?= money($itemUnitPrice) ?></td>
          <td style="text-align: right; font-weight: 700; color: #111827;"><?= money($itemSubtotal) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Totals Summary & Terms Section -->
  <table class="totals-layout-table">
    <tr>
      <td style="vertical-align: top; width: 55%; padding-right: 20px;">
        <div style="font-size: 11px; color: #4b5563; line-height: 1.4;">
          <strong style="color: #111827;">Terms & Return Policy:</strong>
          <div style="margin-top: 4px;">
            Thank you for shopping at POWERSURGE GYM & NUTRITION! We appreciate your business.
          </div>
          <div style="margin-top: 4px;">
            Returns or exchanges are accepted within 7 days of purchase with original sales receipt. Unopened items only.
          </div>
        </div>
      </td>

      <td style="vertical-align: top; width: 45%;">
        <table class="totals-table">
          <tr>
            <td>Subtotal</td>
            <td class="amount"><?= money($subtotal) ?></td>
          </tr>
          <?php if ($discount > 0): ?>
            <tr>
              <td>Discount</td>
              <td class="amount" style="color:#16a34a;">-<?= money($discount) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($tax > 0): ?>
            <tr>
              <td>Tax / VAT</td>
              <td class="amount"><?= money($tax) ?></td>
            </tr>
          <?php endif; ?>
          <tr class="grand-total">
            <td>Grand Total</td>
            <td class="amount"><?= money($grandTotal) ?></td>
          </tr>
          <tr>
            <td>Amount Paid</td>
            <td class="amount" style="color:#16a34a;"><?= money($amountPaid) ?></td>
          </tr>
          <tr>
            <td>Amount Due</td>
            <td class="amount" style="<?= $amountDue > 0 ? 'color:#dc2626; font-weight:700;' : 'color:#6b7280;' ?>">
              <?= money($amountDue) ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Signature Section -->
  <table class="signature-table">
    <tr>
      <td style="width: 50%; text-align: left;">
        <div class="signature-line">Customer Signature</div>
      </td>
      <td style="width: 50%; text-align: right;">
        <div class="signature-line" style="margin-left: auto;">Authorized Signature</div>
      </td>
    </tr>
  </table>

  <!-- Page Footer -->
  <div style="margin-top: 26px; border-top: 1px solid #e5e7eb; padding-top: 6px; text-align: center; font-size: 9px; color: #6b7280;">
    <?= e($gymName) ?> | Phone: <?= e($gymPhone) ?> | Website: <?= e($gymWebsite) ?> | Page 1 of 1
  </div>
</div>
