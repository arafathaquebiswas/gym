<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/** Streams any report's tabular data as a CSV or PDF — shared by every report in the admin panel. */
final class ReportExporter
{
    /** @param array<int, array<int, string>> $rows */
    public static function csv(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . self::slug($filename) . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /**
     * Reuses the POS receipt template visual design language for all system PDF reports.
     * @param array<int, array<int, string>> $rows
     * @param array<string, mixed> $options
     */
    public static function pdf(string $title, array $headers, array $rows, string $subtitle = '', array $options = []): never
    {
        $settingModel = new Setting();
        $gymName = $settingModel->get('gym_name', 'POWERSURGE GYM & NUTRITION');
        $gymPhone = $settingModel->get('gym_phone', '01904-485009');
        $gymEmail = $settingModel->get('gym_email', 'info@powersurgegym.com');
        $gymAddress = $settingModel->get('gym_address', '123 Fitness Ave, Suite 100, City');
        $gymWebsite = 'www.powersurgegym.com';

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

        $resolveImageBase64 = function (?string $path) use ($basePath): string {
            $logoFile = $basePath . '/assets/images/logo/logo.png';
            if (file_exists($logoFile)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
            }
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#003366" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        };

        $logoSetting = $settingModel->get('gym_logo');
        $gymLogo = $resolveImageBase64($logoSetting);

        $user = Auth::user() ?? [];
        $userName = !empty($user['name']) ? $user['name'] : 'Arafat Biswas';
        $roleLabel = ucfirst(str_replace('_', ' ', $user['role'] ?? 'Main Admin'));
        $userIdStr = !empty($user['id']) ? ('ADM-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT)) : 'ADM-00001';
        $branch = 'Head Office';
        $genOn = date('d M Y');
        $genTime = date('h:i A');
        $exportId = 'EXP-' . date('Ymd') . '-' . str_pad((string) rand(100, 999999), 6, '0', STR_PAD_LEFT);

        $filters = !empty($options['filters']) && is_array($options['filters']) 
            ? $options['filters'] 
            : [];
        $filterText = ExportService::formatAppliedFilters($filters);
        if ($filterText === 'None' && $subtitle !== '') {
            $filterText = $subtitle;
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <style>
            @page {
              size: A4 landscape;
              margin: 10mm 10mm 12mm 10mm;
            }
            body {
              font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
              color: #111827;
              background: #ffffff;
              margin: 0;
              padding: 0;
              font-size: 10px;
            }
            .invoice-header-table {
              width: 100%;
              border-collapse: collapse;
              border-bottom: 2px solid #e5e7eb;
              padding-bottom: 10px;
              margin-bottom: 12px;
            }
            .invoice-brand-logo {
              max-height: 40px;
              width: auto;
              vertical-align: middle;
            }
            .invoice-title {
              font-size: 20px;
              font-weight: 800;
              color: #003366;
              letter-spacing: -0.5px;
              text-transform: uppercase;
            }
            .invoice-badge {
              display: inline-block;
              padding: 3px 8px;
              font-size: 9px;
              font-weight: 700;
              text-transform: uppercase;
              border-radius: 4px;
              letter-spacing: 0.5px;
              background: #dcfce7;
              color: #15803d;
              border: 1px solid #bbf7d0;
            }
            .info-table {
              width: 100%;
              border-collapse: separate;
              border-spacing: 10px 0;
              margin-bottom: 12px;
            }
            .info-box {
              background: #f9fafb;
              border: 1px solid #e5e7eb;
              border-radius: 6px;
              padding: 8px 10px;
              vertical-align: top;
            }
            .info-box-title {
              font-size: 9px;
              font-weight: 700;
              text-transform: uppercase;
              color: #6b7280;
              letter-spacing: 0.8px;
              margin-bottom: 4px;
              border-bottom: 1px solid #e5e7eb;
              padding-bottom: 3px;
            }
            .invoice-table {
              width: 100%;
              border-collapse: collapse;
              margin-top: 8px;
              margin-bottom: 12px;
            }
            .invoice-table thead {
              display: table-header-group;
            }
            .invoice-table tr {
              page-break-inside: avoid;
            }
            .invoice-table th {
              background: #003366;
              color: #ffffff;
              font-size: 9px;
              font-weight: 700;
              text-transform: uppercase;
              letter-spacing: 0.5px;
              padding: 7px 8px;
              border: 1px solid #002244;
              text-align: center;
            }
            .invoice-table td {
              padding: 7px 8px;
              border-bottom: 1px solid #e5e7eb;
              border-right: 1px solid #f3f4f6;
              border-left: 1px solid #f3f4f6;
              font-size: 9px;
              color: #1f2937;
              vertical-align: middle;
            }
            .invoice-table tr:nth-child(even) td {
              background-color: #f9fafb;
            }
            .summary-bar {
              background: #003366;
              color: #ffffff;
              padding: 7px 10px;
              font-weight: 700;
              font-size: 9px;
              text-align: right;
              border-radius: 4px;
              margin-bottom: 12px;
            }
            .footer-notice {
              text-align: center;
              font-size: 8px;
              color: #6b7280;
              line-height: 1.4;
              border-top: 1px solid #e5e7eb;
              padding-top: 6px;
              margin-top: 12px;
            }
          </style>
        </head>
        <body>
          <!-- Invoice / Report Header -->
          <table class="invoice-header-table">
            <tr>
              <td style="vertical-align: top; text-align: left;">
                <div style="margin-bottom: 4px;">
                  <?php if ($gymLogo): ?>
                    <img src="<?= $gymLogo ?>" alt="<?= e($gymName) ?>" class="invoice-brand-logo" />
                  <?php endif; ?>
                  <span style="font-size: 16px; font-weight: 800; color: #111827; text-transform: uppercase; vertical-align: middle; margin-left: 6px;">
                    <?= e($gymName) ?>
                  </span>
                </div>
                <div style="font-size: 9px; color: #4b5563; line-height: 1.4;">
                  <div>Fitness Management & POS Management System</div>
                  <div><?= e($gymAddress) ?></div>
                  <div>Phone: <?= e($gymPhone) ?> | Email: <?= e($gymEmail) ?></div>
                  <div>Web: <?= e($gymWebsite) ?></div>
                </div>
              </td>

              <td style="vertical-align: top; text-align: right;">
                <div class="invoice-title"><?= strtoupper(e($title)) ?> REPORT</div>
                <div style="font-size: 11px; font-weight: 700; color: #111827; margin-top: 2px;">Report #: <?= e($exportId) ?></div>
                <div style="font-size: 9px; color: #4b5563; margin-top: 2px;">
                  <div><strong>Date:</strong> <?= e($genOn) ?> | <strong>Time:</strong> <?= e($genTime) ?></div>
                </div>
                <div style="margin-top: 4px;">
                  <span class="invoice-badge">
                    Status: Successfully Generated
                  </span>
                </div>
              </td>
            </tr>
          </table>

          <!-- 2-Column Info Boxes -->
          <table class="info-table">
            <tr>
              <td class="info-box" style="width: 50%;">
                <div class="info-box-title">REPORT & EXPORT DETAILS</div>
                <div style="font-size: 9px; line-height: 1.4; color: #1f2937;">
                  <div><span style="color:#6b7280;">Target Module:</span> <strong><?= e($title) ?></strong></div>
                  <div><span style="color:#6b7280;">Exported By:</span> <strong><?= e($userName) ?> (<?= e($userIdStr) ?>)</strong></div>
                  <div><span style="color:#6b7280;">Role:</span> <?= e($roleLabel) ?></div>
                  <div><span style="color:#6b7280;">Branch:</span> <?= e($branch) ?></div>
                </div>
              </td>

              <td class="info-box" style="width: 50%;">
                <div class="info-box-title">EXPORT METADATA & SUMMARY</div>
                <div style="font-size: 9px; line-height: 1.4; color: #1f2937;">
                  <div><span style="color:#6b7280;">Report ID:</span> <strong><?= e($exportId) ?></strong></div>
                  <div><span style="color:#6b7280;">Export Format:</span> PDF (.pdf)</div>
                  <div><span style="color:#6b7280;">Applied Filters:</span> <?= e($filterText) ?></div>
                  <div><span style="color:#6b7280;">Total Records:</span> <strong><?= count($rows) ?></strong></div>
                </div>
              </td>
            </tr>
          </table>

          <!-- Data Table -->
          <table class="invoice-table">
            <thead>
              <tr>
                <?php foreach ($headers as $header): ?>
                  <th><?= e(ucfirst(str_replace('_', ' ', (string) $header))) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <?php foreach (array_values($row) as $cIdx => $cell): ?>
                    <?php 
                      $headerKey = $headers[$cIdx] ?? '';
                      $val = ExportService::formatCellValue((string) $headerKey, $cell);
                      $isCenter = (is_numeric($val) || strtolower((string)$headerKey) === 'id' || str_contains(strtolower((string)$headerKey), 'date') || str_contains(strtolower((string)$headerKey), 'phone'));
                    ?>
                    <td style="<?= $isCenter ? 'text-align: center;' : 'text-align: left;' ?>">
                      <?= e($val) ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <!-- Summary Bar -->
          <div class="summary-bar">
            Total Records Exported: <?= count($rows) ?> &nbsp;&nbsp;|&nbsp;&nbsp; Status: Successfully Generated
          </div>

          <!-- Confidential Footer -->
          <div class="footer-notice">
            <div>Generated automatically by POWERSURGE GYM & NUTRITION Management System.</div>
            <div>This report is confidential and intended for authorized personnel only.</div>
            <div>Report ID: <?= e($exportId) ?> &nbsp;|&nbsp; Printed on: <?= e($genOn) ?>, <?= e($genTime) ?> &nbsp;|&nbsp; <?= e($companyName) ?> &nbsp;•&nbsp; www.powersurgegym.com</div>
          </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $dompdfOptions = new Options();
        $dompdfOptions->set('isHtml5ParserEnabled', true);
        $dompdfOptions->set('isRemoteEnabled', true);
        $dompdfOptions->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $content = $dompdf->output();
        $filename = !empty($options['filename']) ? $options['filename'] : (strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $title), '_')) . '_' . date('Y-m-d_H-i') . '.pdf');
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    private static function slug(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-');
    }
}
