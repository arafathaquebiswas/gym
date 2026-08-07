<?php
/**
 * Delivery Label / Courier Invoice
 * ---------------------------------
 * Intended audience: Warehouse packing team, courier staff, delivery man.
 * This is NOT the customer invoice — it carries no price breakdown.
 *
 * @var array  $order
 * @var array  $items
 * @var string $labelNo   Unique delivery label identifier
 */

/* ── Settings ──────────────────────────────────────────────────────────── */
$settingModel = new Setting();
$gymName      = $settingModel->get('gym_name',    'POWERSURGE GYM & NUTRITION');
$gymPhone     = $settingModel->get('gym_phone',   '01904-485009');
$gymEmail     = $settingModel->get('gym_email',   'info@powersurgegym.com');
$gymAddress   = $settingModel->get('gym_address', 'Fitness Ave, Dhaka, Bangladesh');
$gymWebsite   = $settingModel->get('gym_website', 'www.powersurgegym.com');

/* ── Logo (Base64 — survives print & PDF) ───────────────────────────────── */
$basePath     = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
$logoSetting  = $settingModel->get('gym_logo');

$resolveBase64 = function (?string $path, string $fallback) use ($basePath): string {
    foreach (array_filter([$path]) as $p) {
        if (str_starts_with($p, 'data:image/')) {
            return $p;
        }
        $clean = ltrim(preg_replace('/^(uploads\/|assets\/)/', '', $p), '/');
        foreach ([$basePath . '/uploads/' . $clean, $basePath . '/assets/' . $clean] as $f) {
            if (is_file($f)) {
                $ext  = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($f));
            }
        }
    }
    $fb = $basePath . '/assets/' . ltrim($fallback, '/');
    if (is_file($fb)) {
        $ext  = strtolower(pathinfo($fb, PATHINFO_EXTENSION));
        $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fb));
    }
    // Inline fallback SVG dumbbell icon
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 30" fill="none">'
         . '<rect x="0" y="8" width="80" height="14" rx="4" fill="#f97316"/>'
         . '<rect x="8"  y="0" width="10" height="30" rx="3" fill="#1f2937"/>'
         . '<rect x="62" y="0" width="10" height="30" rx="3" fill="#1f2937"/>'
         . '<text x="40" y="22" text-anchor="middle" font-size="9" font-family="Arial" font-weight="bold" fill="white">POWERSURGE</text>'
         . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
};

$gymLogo = $resolveBase64($logoSetting, 'images/logo/logo.png');

/* ── Order fields ───────────────────────────────────────────────────────── */
$customerName  = $order['account_name']  ?? $order['guest_name']  ?? 'N/A';
$customerPhone = $order['account_phone'] ?? $order['guest_phone']  ?? 'N/A';
$altPhone      = $order['guest_phone']   ?? ($order['account_phone'] ?? 'N/A');
// If both are the same, show nothing for alt
if ($altPhone === $customerPhone) {
    $altPhone = '—';
}

$deliveryAddress = $order['delivery_address']     ?? 'N/A';
$deliveryArea    = $order['delivery_area']         ?? '';
$deliveryCity    = $order['delivery_city']         ?? 'N/A';
$postalCode      = $order['delivery_postal_code']  ?? '';

$shippingMethod  = ucwords(str_replace('_', ' ', $order['fulfillment_method'] ?? 'delivery'));
$zoneName        = $order['zone_name']             ?? '—';
$deliveryStaff   = $order['delivery_person_name']  ?? '—';
$productCount    = array_sum(array_column($items, 'qty'));

$paymentMethod  = strtoupper(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'));
$paymentStatus  = strtolower($order['payment_status'] ?? 'pending');
$isCOD          = ($order['payment_method'] ?? '') === 'cod'
               || ($order['payment_method'] ?? '') === 'cash_on_delivery'
               || ($paymentStatus !== 'paid' && ($order['fulfillment_method'] ?? '') !== 'pickup');
$isPaid         = ($paymentStatus === 'paid');
$collectAmount  = (float) ($order['total'] ?? 0);

$orderDate      = format_date($order['created_at'], 'd M Y, h:i A');
$orderNo        = $order['order_no'] ?? '—';
$orderId        = (int) ($order['id'] ?? 0);

// Tracking number = order_no (no separate tracking_number column in schema)
$trackingNo     = $orderNo;

// Delivery label number = DL- prefix + zero-padded order id
$labelNo        = 'DL-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);

// Estimated delivery: 1–3 business days from order date
$estDelivery    = date('d M Y', strtotime($order['created_at'] . ' +2 days'));

/* ── Barcode (Code128 SVG) ──────────────────────────────────────────────── */
$barcodeSvg = Barcode::svg($trackingNo, 56, 2, 8);

/* ── QR Code ────────────────────────────────────────────────────────────── */
$qrData = implode("\n", [
    'Order ID: '     . $orderId,
    'Order No: '     . $orderNo,
    'Tracking: '     . $trackingNo,
    'Customer: '     . $customerName,
    'Phone: '        . $customerPhone,
    'Address: '      . $deliveryAddress . ', ' . $deliveryArea . ', ' . $deliveryCity,
    'Postal: '       . $postalCode,
    'Instructions: ' . ($order['order_notes'] ?? 'None'),
]);
$qrDataUri = QrCode::dataUri($qrData);

/* ── Fragile check ──────────────────────────────────────────────────────── */
$fragileKeywords = ['glass', 'bottle', 'shaker', 'jar', 'liquid', 'fragile', 'ceramic'];
$isFragile = false;
foreach ($items as $item) {
    foreach ($fragileKeywords as $kw) {
        if (stripos($item['product_name'] ?? '', $kw) !== false) {
            $isFragile = true;
            break 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    /* ── Screen wrapper ─────────────────────────────────── */
    @media screen {
      body { background: #f1f5f9; font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 0; }
      .dl-no-print { display: flex; gap: 10px; max-width: 794px; margin: 0 auto 16px; }
      .dl-wrap { max-width: 794px; margin: 0 auto; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,.12); border-radius: 6px; padding: 20px; }
    }

    /* ── Print / A4 portrait ────────────────────────────── */
    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    @media print {
      html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #000000 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 9pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .dl-no-print { display: none !important; }
      .admin-sidebar, .admin-topbar, .admin-overlay,
      .admin-breadcrumb, .no-print, .btn,
      .admin-shell > :not(.admin-main),
      .admin-topbar, .admin-footer { display: none !important; }
      .admin-shell, .admin-main, .admin-content, .admin-page-shell {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        background: #ffffff !important;
        box-shadow: none !important;
      }
      .dl-wrap {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        border: none !important;
        background: #ffffff !important;
      }
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }

    /* ── Base label typography ──────────────────────────── */
    .dl-wrap * { box-sizing: border-box; }
    .dl-wrap {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
      color: #111;
      background: #fff;
    }

    /* ── Header ─────────────────────────────────────────── */
    .dl-header {
      display: table;
      width: 100%;
      border-bottom: 2.5px solid #111;
      padding-bottom: 10px;
      margin-bottom: 10px;
    }
    .dl-header-left, .dl-header-right {
      display: table-cell;
      vertical-align: top;
    }
    .dl-header-right { text-align: right; width: 44%; }
    .dl-logo { max-height: 48px; width: auto; }
    .dl-company-name { font-size: 15pt; font-weight: 900; letter-spacing: -0.5px; margin: 4px 0 2px; text-transform: uppercase; color: #111; }
    .dl-company-detail { font-size: 7.5pt; color: #333; line-height: 1.5; }

    .dl-label-title {
      display: inline-block;
      background: #111;
      color: #fff;
      font-size: 11pt;
      font-weight: 900;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 3px 12px;
      border-radius: 3px;
      margin-bottom: 6px;
    }
    .dl-header-meta { font-size: 7.5pt; color: #333; margin-top: 4px; line-height: 1.6; }
    .dl-header-meta strong { color: #111; }

    .dl-barcode-wrap { display: inline-block; }
    .dl-barcode-wrap img,
    .dl-barcode-wrap svg { display: block; max-width: 100%; }
    .dl-barcode-no { font-family: 'Courier New', monospace; font-size: 8pt; font-weight: 700; letter-spacing: 1px; text-align: center; margin-top: 2px; color: #111; }

    .dl-qr-wrap { display: inline-block; margin-left: 10px; vertical-align: top; }
    .dl-qr-wrap img { width: 70px; height: 70px; display: block; }

    /* ── Section grid ────────────────────────────────────── */
    .dl-sections {
      display: table;
      width: 100%;
      border-collapse: collapse;
      margin-top: 0;
    }
    .dl-section-row { display: table-row; }
    .dl-section-cell {
      display: table-cell;
      vertical-align: top;
      padding: 8px 8px;
      border: 1px solid #d1d5db;
    }
    .dl-section-cell:first-child { border-left: none; }
    .dl-section-cell:last-child  { border-right: none; }
    .dl-section-title {
      font-size: 7pt;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #555;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 3px;
      margin-bottom: 5px;
    }
    .dl-field { display: table; width: 100%; margin-bottom: 2px; }
    .dl-field-label { display: table-cell; font-size: 7.5pt; color: #555; white-space: nowrap; padding-right: 5px; width: 1%; }
    .dl-field-value { display: table-cell; font-size: 8pt; font-weight: 700; color: #111; }

    /* ── COD / PAID badges ───────────────────────────────── */
    .dl-cod-box {
      border: 2.5px solid #dc2626;
      border-radius: 5px;
      padding: 6px 10px;
      margin-top: 6px;
      text-align: center;
      background: #fff5f5;
    }
    .dl-cod-label { font-size: 8pt; color: #dc2626; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .dl-cod-amount { font-size: 18pt; font-weight: 900; color: #dc2626; letter-spacing: 1px; line-height: 1.1; }
    .dl-paid-badge {
      display: inline-block;
      border: 3px solid #16a34a;
      border-radius: 6px;
      padding: 6px 20px;
      margin-top: 6px;
      text-align: center;
      background: #f0fdf4;
      width: 100%;
    }
    .dl-paid-text { font-size: 20pt; font-weight: 900; color: #16a34a; letter-spacing: 3px; }
    .dl-paid-sub  { font-size: 7pt; color: #16a34a; }

    /* ── Product table ───────────────────────────────────── */
    .dl-product-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
      font-size: 8pt;
    }
    .dl-product-table th {
      background: #f3f4f6;
      color: #374151;
      font-size: 7pt;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 4px 6px;
      border: 1px solid #d1d5db;
      text-align: left;
    }
    .dl-product-table td {
      padding: 4px 6px;
      border: 1px solid #e5e7eb;
      color: #111;
      vertical-align: middle;
    }
    .dl-product-table tr:nth-child(even) td { background: #f9fafb; }

    /* ── Full-width barcode row ───────────────────────────── */
    .dl-barcode-row {
      border-top: 2px solid #111;
      border-bottom: 2px solid #111;
      padding: 8px 0;
      text-align: center;
      margin: 10px 0;
    }
    .dl-barcode-row svg { max-width: 100%; height: 60px; }

    /* ── Handling notices ────────────────────────────────── */
    .dl-notices {
      display: table;
      width: 100%;
      border-collapse: collapse;
      margin: 10px 0;
    }
    .dl-notice-cell {
      display: table-cell;
      border: 1px solid #d1d5db;
      padding: 5px 8px;
      font-size: 7.5pt;
      font-weight: 700;
      vertical-align: middle;
      text-align: center;
    }
    .dl-notice-icon { font-size: 12pt; display: block; }
    .dl-notice-fragile { border-color: #dc2626; color: #dc2626; background: #fff5f5; }

    /* ── Signature grid ──────────────────────────────────── */
    .dl-sig-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    .dl-sig-table td {
      border: 1px solid #d1d5db;
      padding: 6px 8px;
      vertical-align: top;
      width: 20%;
    }
    .dl-sig-label { font-size: 7pt; color: #555; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 18px; }
    .dl-sig-line  { border-top: 1px solid #9ca3af; margin-top: 22px; }

    /* ── Footer ──────────────────────────────────────────── */
    .dl-footer {
      border-top: 1px solid #d1d5db;
      margin-top: 10px;
      padding-top: 5px;
      font-size: 7pt;
      color: #6b7280;
      text-align: center;
    }
  </style>
</head>
<body>

<!-- ── Screen-only toolbar ─────────────────────────────────────────────── -->
<div class="print-control-bar dl-no-print" style="background: #0f172a; color: #fff; padding: 12px 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 24px;">
  <div class="container-fluid max-w-6xl d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= url('/admin/orders/' . $orderId) ?>" class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Order
      </a>
      <span class="badge bg-secondary font-monospace px-2.5 py-1.5 fs-7">
        Label #<?= e($labelNo) ?>
      </span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button type="button" onclick="window.print()" class="btn btn-sm btn-warning font-weight-bold d-inline-flex align-items-center gap-1.5 shadow-sm px-3">
        <i class="bi bi-printer-fill"></i> Print Delivery Label
      </button>
      <button type="button" onclick="downloadPdf()" class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
      </button>
    </div>
  </div>
</div>

<!-- ── Label wrapper ───────────────────────────────────────────────────── -->
<div class="dl-wrap">

  <!-- ── HEADER ─────────────────────────────────────────────────────────── -->
  <div class="dl-header">
    <div class="dl-header-left">
      <img src="<?= e($gymLogo) ?>" alt="<?= e($gymName) ?> Logo" class="dl-logo">
      <div class="dl-company-name"><?= e($gymName) ?></div>
      <div class="dl-company-detail">
        <?= e($gymAddress) ?><br>
        Tel: <?= e($gymPhone) ?> &nbsp;|&nbsp;
        <?= e($gymEmail) ?><br>
        <?= e($gymWebsite) ?>
      </div>
    </div>
    <div class="dl-header-right">
      <div>
        <span class="dl-label-title">Delivery Label</span>
      </div>
      <!-- QR + Barcode side-by-side -->
      <div style="display:inline-block; text-align:right;">
        <div class="dl-barcode-wrap" style="display:inline-block; vertical-align:top;">
          <?= $barcodeSvg ?>
          <div class="dl-barcode-no"><?= e($trackingNo) ?></div>
        </div>
        <div class="dl-qr-wrap">
          <img src="<?= e($qrDataUri) ?>" alt="QR Code" width="70" height="70">
        </div>
      </div>
      <div class="dl-header-meta">
        <strong>Label #:</strong> <?= e($labelNo) ?> &nbsp;
        <strong>Order #:</strong> <?= e($orderNo) ?><br>
        <strong>Tracking:</strong> <?= e($trackingNo) ?><br>
        <strong>Date:</strong> <?= e($orderDate) ?>
      </div>
    </div>
  </div><!-- /dl-header -->

  <!-- ── CUSTOMER + SHIPPING + PAYMENT ─────────────────────────────────── -->
  <div class="dl-sections">
    <div class="dl-section-row">

      <!-- Customer -->
      <div class="dl-section-cell" style="width:36%">
        <div class="dl-section-title">📦 Ship To (Customer)</div>
        <div class="dl-field"><span class="dl-field-label">Name:</span><span class="dl-field-value"><?= e($customerName) ?></span></div>
        <div class="dl-field"><span class="dl-field-label">Phone:</span><span class="dl-field-value"><?= e($customerPhone) ?></span></div>
        <div class="dl-field"><span class="dl-field-label">Alt Phone:</span><span class="dl-field-value"><?= e($altPhone) ?></span></div>
        <div style="margin-top:4px; font-size:8.5pt; font-weight:700; line-height:1.4; color:#111;">
          <?= e($deliveryAddress) ?>
          <?php if ($deliveryArea): ?>, <?= e($deliveryArea) ?><?php endif; ?><br>
          <?= e($deliveryCity) ?>
          <?php if ($postalCode): ?> — <?= e($postalCode) ?><?php endif; ?>
        </div>
        <?php if (!empty($order['order_notes'])): ?>
        <div style="margin-top:4px; font-size:7.5pt; color:#374151; border-top:1px dashed #d1d5db; padding-top:3px;">
          <strong>Note:</strong> <?= nl2br(e($order['order_notes'])) ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Shipping Info -->
      <div class="dl-section-cell" style="width:32%">
        <div class="dl-section-title">🚚 Shipping Info</div>
        <div class="dl-field"><span class="dl-field-label">Method:</span><span class="dl-field-value"><?= e($shippingMethod) ?></span></div>
        <div class="dl-field"><span class="dl-field-label">Zone:</span><span class="dl-field-value"><?= e($zoneName) ?></span></div>
        <div class="dl-field"><span class="dl-field-label">Assigned To:</span><span class="dl-field-value"><?= e($deliveryStaff) ?></span></div>
        <div class="dl-field"><span class="dl-field-label">No. of Items:</span><span class="dl-field-value"><?= (int) $productCount ?> pcs</span></div>
        <div class="dl-field"><span class="dl-field-label">Est. Delivery:</span><span class="dl-field-value"><?= e($estDelivery) ?></span></div>
        <?php if (!empty($order['time_slot_label'])): ?>
        <div class="dl-field"><span class="dl-field-label">Time Slot:</span><span class="dl-field-value"><?= e($order['time_slot_label']) ?></span></div>
        <?php endif; ?>
      </div>

      <!-- Payment Info -->
      <div class="dl-section-cell" style="width:32%">
        <div class="dl-section-title">💳 Payment Info</div>
        <div class="dl-field"><span class="dl-field-label">Method:</span><span class="dl-field-value"><?= e($paymentMethod) ?></span></div>
        <div class="dl-field"><span class="dl-field-label">Status:</span>
          <span class="dl-field-value" style="color:<?= $isPaid ? '#16a34a' : '#dc2626' ?>">
            <?= $isPaid ? 'PAID' : strtoupper($paymentStatus) ?>
          </span>
        </div>

        <?php if ($isPaid): ?>
          <div class="dl-paid-badge">
            <div class="dl-paid-text">PAID</div>
            <div class="dl-paid-sub">Online Payment Verified</div>
          </div>
        <?php else: ?>
          <div class="dl-cod-box">
            <div class="dl-cod-label">⚠ Collect on Delivery</div>
            <div class="dl-cod-amount">BDT <?= number_format($collectAmount, 2) ?></div>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dl-section-row -->
  </div><!-- /dl-sections -->

  <!-- ── PRODUCTS SUMMARY ───────────────────────────────────────────────── -->
  <div style="margin-top:10px; border:1px solid #d1d5db; padding:8px;">
    <div class="dl-section-title" style="border-bottom:1px solid #e5e7eb; padding-bottom:3px; margin-bottom:5px;">📋 Product Summary (<?= (int) count($items) ?> line<?= count($items) !== 1 ? 's' : '' ?>)</div>
    <table class="dl-product-table">
      <thead>
        <tr>
          <th style="width:20%">SKU</th>
          <th>Product Name</th>
          <th style="width:12%; text-align:center;">Qty</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td style="font-family:'Courier New',monospace; font-size:7.5pt;"><?= e($item['sku'] ?? '—') ?></td>
          <td><?= e($item['product_name']) ?><?= !empty($item['variant_label']) ? ' (' . e($item['variant_label']) . ')' : '' ?></td>
          <td style="text-align:center; font-weight:700;"><?= (int) $item['qty'] ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="2" style="text-align:right; font-weight:700; font-size:7.5pt; color:#374151;">Total Pieces:</td>
          <td style="text-align:center; font-weight:900; color:#111;"><?= (int) $productCount ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- ── HANDLING NOTICES ───────────────────────────────────────────────── -->
  <div class="dl-notices" style="margin-top:10px;">
    <div class="dl-notice-cell">
      <span class="dl-notice-icon">🤲</span>
      Handle With Care
    </div>
    <?php if ($isFragile): ?>
    <div class="dl-notice-cell dl-notice-fragile">
      <span class="dl-notice-icon">🍶</span>
      Fragile — Handle Gently
    </div>
    <?php endif; ?>
    <div class="dl-notice-cell">
      <span class="dl-notice-icon">📞</span>
      Verify Phone Before Delivery
    </div>
    <div class="dl-notice-cell">
      <span class="dl-notice-icon">✍️</span>
      Collect Customer Signature
    </div>
    <div class="dl-notice-cell">
      <span class="dl-notice-icon">❌</span>
      Do Not Accept Damaged Package
    </div>
  </div>

  <!-- ── SIGNATURE AREA ─────────────────────────────────────────────────── -->
  <table class="dl-sig-table">
    <tr>
      <td>
        <div class="dl-sig-label">Packed By</div>
        <div class="dl-sig-line"></div>
        <div style="font-size:7pt; color:#9ca3af; margin-top:2px;">Name &amp; Signature</div>
      </td>
      <td>
        <div class="dl-sig-label">Checked By</div>
        <div class="dl-sig-line"></div>
        <div style="font-size:7pt; color:#9ca3af; margin-top:2px;">Name &amp; Signature</div>
      </td>
      <td>
        <div class="dl-sig-label">Delivery Man</div>
        <div class="dl-sig-line"></div>
        <div style="font-size:7pt; color:#9ca3af; margin-top:2px;">Signature</div>
      </td>
      <td>
        <div class="dl-sig-label">Customer Signature</div>
        <div class="dl-sig-line"></div>
        <div style="font-size:7pt; color:#9ca3af; margin-top:2px;">Signature</div>
      </td>
      <td>
        <div class="dl-sig-label">Delivery Date</div>
        <div style="margin-top:8px; font-size:7.5pt; color:#555;">Date: ___ / ___ / _______</div>
        <div style="margin-top:6px; font-size:7.5pt; color:#555;">Time: ______ : ______</div>
      </td>
    </tr>
  </table>

  <!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
  <div class="dl-footer">
    <?= e($gymName) ?> &nbsp;|&nbsp; <?= e($gymPhone) ?> &nbsp;|&nbsp; <?= e($gymEmail) ?> &nbsp;|&nbsp; <?= e($gymWebsite) ?>
    &nbsp;&mdash;&nbsp; Label generated: <?= date('d M Y, h:i A') ?>
  </div>

</div><!-- /dl-wrap -->

<script>
function downloadPdf() {
  var btn = document.querySelector('[onclick="downloadPdf()"]');
  if (btn) { btn.innerHTML = '<i class="bi bi-hourglass"></i> Opening PDF...'; }
  setTimeout(function() { window.print(); }, 200);
}

if (window.location.search.indexOf('autoprint=1') !== -1 || window.location.search.indexOf('print=1') !== -1) {
  window.onload = function() {
    setTimeout(function() { window.print(); }, 250);
  };
}
</script>
</body>
</html>
