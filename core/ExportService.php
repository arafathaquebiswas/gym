<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

final class ExportService
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $options
     */
    public static function download(string $module, string $format, array $rows, array $filters, array $options = []): never
    {
        $title = Modules::label($module);

        // Sanitize rows to ensure ONLY string keys are used (fixes PDO FETCH_BOTH key alignment bug)
        $cleanRows = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $assoc = [];
            foreach ($r as $k => $v) {
                if (is_string($k)) {
                    $assoc[$k] = $v;
                }
            }
            if ($assoc) {
                $cleanRows[] = $assoc;
            }
        }
        $rows = $cleanRows;
        
        $customName = trim((string) ($options['filename'] ?? ''));
        if ($customName !== '') {
            $filename = self::sanitizeFilename($customName, $format);
        } else {
            $filename = self::filename($title, $format);
        }

        self::audit($module, $format, $filename, count($rows), $filters);

        $headers = $rows ? array_keys($rows[0]) : ['No records'];
        if ($format === 'xlsx') {
            self::xlsx($filename, $title, $headers, $rows, $filters, $options);
        }
        if ($format === 'pdf') {
            ReportExporter::pdf($title, $headers, self::values($rows, $headers), self::formatAppliedFilters($filters), array_merge($options, ['filters' => $filters]));
        }
        self::csv($filename, $title, $headers, $rows, $filters, $options);
    }

    /** @param array<string, mixed> $filters */
    public static function formatAppliedFilters(array $filters): string
    {
        $parts = [];
        if (!empty($filters['search'])) {
            $parts[] = 'Search: "' . trim((string) $filters['search']) . '"';
        }
        if (!empty($filters['status'])) {
            $parts[] = 'Status: ' . ucfirst(str_replace('_', ' ', trim((string) $filters['status'])));
        }
        if (!empty($filters['from']) || !empty($filters['to'])) {
            $fromStr = !empty($filters['from']) ? date('d M Y', strtotime((string) $filters['from'])) : 'Start';
            $toStr = !empty($filters['to']) ? date('d M Y', strtotime((string) $filters['to'])) : 'Present';
            $parts[] = 'Date: ' . $fromStr . ' to ' . $toStr;
        }
        if (!empty($filters['scope']) && $filters['scope'] === 'selected') {
            $parts[] = 'Scope: Selected Rows';
        } elseif (!empty($filters['scope']) && $filters['scope'] === 'current') {
            $parts[] = 'Scope: Current Page';
        }
        return $parts ? implode(' | ', $parts) : 'None';
    }

    /** @param array<string, mixed> $filters */
    public static function getExportMetadata(string $moduleTitle, string $format, int $recordCount, array $filters): array
    {
        $user = Auth::user() ?? [];
        $userName = !empty($user['name']) ? $user['name'] : 'Arafat Biswas';
        $userEmail = !empty($user['email']) ? $user['email'] : 'admin@powersurgegym.com';
        $roleLabel = ucfirst(str_replace('_', ' ', $user['role'] ?? 'Main Admin'));
        $userIdStr = !empty($user['id']) ? ('ADM-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT)) : 'ADM-00001';
        $branch = 'Head Office';
        $genOn = date('d M Y'); // e.g. 02 Aug 2026
        $genTime = date('h:i A'); // e.g. 04:40 PM
        
        $filterSummary = self::formatAppliedFilters($filters);
        $exportId = 'EXP-' . date('Ymd') . '-' . str_pad((string) rand(100, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'Export Report'      => $moduleTitle,
            'Export Format'      => match(strtolower($format)) { 'xlsx' => 'Excel (.xlsx)', 'pdf' => 'PDF (.pdf)', default => 'CSV (.csv)' },
            'Generated On'       => $genOn,
            'Generated Time'     => $genTime,
            'Exported By'        => $userName,
            'User ID'            => $userIdStr,
            'Role'               => $roleLabel,
            'Email'              => $userEmail,
            'Branch'             => $branch,
            'Applied Filters'    => $filterSummary,
            'Records Exported'   => (string) $recordCount,
            'Export ID'          => $exportId,
        ];
    }

    public static function formatCellValue(string $key, mixed $val): string
    {
        $strVal = self::scalar($val);
        if ($strVal === '') return '—';

        // Format ISO Date or Datetime strings into human-readable representation
        if (preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2}(\.\d+)?)?$/', trim($strVal))) {
            $ts = strtotime($strVal);
            if ($ts !== false) {
                return str_contains($strVal, ':') ? date('d M Y, h:i A', $ts) : date('d M Y', $ts);
            }
        }
        return $strVal;
    }

    /** @param array<string, mixed> $filters */
    private static function audit(string $module, string $format, string $filename, int $count, array $filters): void
    {
        $user = Auth::user() ?? [];
        $stmt = Database::connection()->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, user_name, user_role, module_key, export_format, file_name, record_count, filters_json, user_agent, created_at)
             VALUES (:user_id, :action, :description, :ip, :name, :role, :module, :format, :file, :count, :filters, :agent, NOW())'
        );
        $summary = sprintf('Exported %s (%d records)', $filename, $count);
        $stmt->execute([
            'user_id' => $user['id'] ?? null,
            'action' => 'export_' . $module,
            'description' => $summary,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'name' => $user['name'] ?? null,
            'role' => $user['role'] ?? null,
            'module' => $module,
            'format' => $format,
            'file' => $filename,
            'count' => $count,
            'filters' => json_encode($filters, JSON_UNESCAPED_SLASHES),
            'agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
        ]);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $options
     */
    private static function csv(string $filename, string $title, array $headers, array $rows, array $filters, array $options): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');

        $setting = new Setting();
        $companyName = $setting->get('gym_name', 'PowerSurge Gym');

        // 1. Company Header
        fputcsv($out, [$companyName]);
        fputcsv($out, ['Fitness Management & POS Management System']);
        fputcsv($out, ['PowerSurge Gym Official Report']);
        fputcsv($out, []);

        // 2. Report Title
        fputcsv($out, [strtoupper($title) . ' REPORT']);
        fputcsv($out, ['-----------------------------------------------------------------------']);
        fputcsv($out, []);

        // 3. Data Table Section Title & Headers
        fputcsv($out, [strtoupper($title) . ' INFORMATION']);
        fputcsv($out, array_map(static fn ($h) => ucfirst(str_replace('_', ' ', (string) $h)), $headers));

        // Data Rows
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($headers as $h) {
                $rowValues[] = self::formatCellValue((string) $h, $row[$h] ?? '');
            }
            fputcsv($out, $rowValues);
        }

        fputcsv($out, []);
        fputcsv($out, ['=======================================================================']);
        fputcsv($out, ['EXPORT INFORMATION']);

        // 4. Export Information Block (At END of Report!)
        $meta = self::getExportMetadata($title, 'csv', count($rows), $filters);
        foreach ($meta as $k => $v) {
            fputcsv($out, [sprintf('%-20s : %s', $k, $v)]);
        }

        fputcsv($out, ['=======================================================================']);
        fputcsv($out, ['Generated automatically by PowerSurge Gym Management System.']);
        fputcsv($out, ['This report is confidential and intended for authorized personnel only.']);
        fputcsv($out, [$companyName . '  •  Website: www.powersurgegym.com  •  Email: support@powersurgegym.com']);

        fclose($out);
        exit;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $options
     */
    private static function xlsx(string $filename, string $title, array $headers, array $rows, array $filters, array $options): never
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $cleanSheetTitle = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $title) ?: 'Export', 0, 31);
        $sheet->setTitle($cleanSheetTitle);

        // Page Setup: Printable A4 Landscape, Fit to 1 Page Wide, Centered Horizontally
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setHorizontalCentered(true);

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.5);
        $margins->setBottom(0.5);
        $margins->setLeft(0.5);
        $margins->setRight(0.5);

        $colLetters = range('A', 'Z');
        $colCount = max(6, count($headers));
        $lastCol = $colCount <= 26 ? $colLetters[$colCount - 1] : 'A' . $colLetters[$colCount - 27];

        $midColIdx = (int) floor($colCount / 2);
        $midCol = $colLetters[max(2, $midColIdx - 1)];
        $rightStartCol = $colLetters[max(3, $midColIdx)];

        $setting = new Setting();
        $companyName = $setting->get('gym_name', 'PowerSurge Gym');

        $user = Auth::user() ?? [];
        $userName = !empty($user['name']) ? $user['name'] : 'Arafat Biswas';
        $userEmail = !empty($user['email']) ? $user['email'] : 'admin@powersurgegym.com';
        $roleLabel = ucfirst(str_replace('_', ' ', $user['role'] ?? 'Main Admin'));
        $userIdStr = !empty($user['id']) ? ('ADM-' . str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT)) : 'ADM-00001';
        $branch = 'Head Office';
        $genOn = date('d M Y');
        $genTime = date('h:i A');
        $exportId = 'EXP-' . date('Ymd') . '-' . str_pad((string) rand(100, 999999), 6, '0', STR_PAD_LEFT);
        $filterSummary = self::formatAppliedFilters($filters);
        $recordCount = count($rows);

        $navyColor = 'FF003366'; // Corporate Dark Navy Blue #003366
        $lightBorderColor = 'FFD0D7DE';

        $rowIdx = 1;

        // --- ROW 1: Merged Left Company Name | Merged Right Report Title ---
        $sheet->mergeCells("A{$rowIdx}:{$midCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, $companyName);
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(18)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));
        
        $sheet->mergeCells("{$rightStartCol}{$rowIdx}:{$lastCol}{$rowIdx}");
        $sheet->setCellValue($rightStartCol . $rowIdx, strtoupper($title) . ' REPORT');
        $sheet->getStyle($rightStartCol . $rowIdx)->getFont()->setBold(true)->setSize(22)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($navyColor));
        $sheet->getStyle($rightStartCol . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($rowIdx)->setRowHeight(32);
        $rowIdx++;

        // --- ROW 2: Merged Left Slogan | Right Metadata Box ---
        $sheet->mergeCells("A{$rowIdx}:{$midCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, 'Fitness Management & POS Management System');
        $sheet->getStyle('A' . $rowIdx)->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

        // Metadata Table Box on Right (Date, Export ID, User ID, Role)
        $metaStartRow = $rowIdx;
        $metaItems = [
            ['Date:', $genOn],
            ['Report #:', $exportId],
            ['Exported By:', $userName . ' (' . $userIdStr . ')'],
            ['Role:', $roleLabel],
        ];

        // Fill Metadata table on right (Columns E & F)
        $rCol1 = $colLetters[max(0, $colCount - 2)];
        $rCol2 = $lastCol;
        
        $mRow = $metaStartRow;
        foreach ($metaItems as $mItem) {
            $sheet->setCellValue($rCol1 . $mRow, $mItem[0]);
            $sheet->getStyle($rCol1 . $mRow)->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
            $sheet->getStyle($rCol1 . $mRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($lightBorderColor));
            
            $sheet->setCellValue($rCol2 . $mRow, $mItem[1]);
            $sheet->getStyle($rCol2 . $mRow)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));
            $sheet->getStyle($rCol2 . $mRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($lightBorderColor));
            $mRow++;
        }

        // Subtitle line below Slogan
        $rowIdx++;
        $sheet->mergeCells("A{$rowIdx}:{$midCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, '⚡ PowerSurge Gym Official Verified Report');
        $sheet->getStyle('A' . $rowIdx)->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

        $rowIdx = max($rowIdx + 2, $mRow + 1);

        // --- SUBHEADER CARDS: Left "Report Details" | Right "Report Summary" ---
        $sheet->mergeCells("A{$rowIdx}:{$midCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, ' Report Details');
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($navyColor);
        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells("{$rightStartCol}{$rowIdx}:{$lastCol}{$rowIdx}");
        $sheet->setCellValue($rightStartCol . $rowIdx, ' Report Summary');
        $sheet->getStyle($rightStartCol . $rowIdx)->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($navyColor);
        $sheet->getStyle("{$rightStartCol}{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($navyColor);
        $sheet->getStyle("{$rightStartCol}{$rowIdx}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($rowIdx)->setRowHeight(24);
        $rowIdx++;

        // Subheader Details Content
        $leftDetails = [
            ['Target Module:', $title],
            ['Applied Filters:', $filterSummary],
            ['Branch:', $branch],
        ];
        $rightDetails = [
            ['Records Exported:', (string) $recordCount],
            ['Export Format:', 'Excel (.xlsx)'],
            ['Generated Time:', $genTime],
        ];

        for ($k = 0; $k < 3; $k++) {
            // Left detail row
            $sheet->setCellValue('A' . $rowIdx, $leftDetails[$k][0]);
            $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
            $sheet->mergeCells("B{$rowIdx}:{$midCol}{$rowIdx}");
            $sheet->setCellValue('B' . $rowIdx, $leftDetails[$k][1]);
            $sheet->getStyle('B' . $rowIdx)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));

            // Right detail row
            $sheet->setCellValue($rightStartCol . $rowIdx, $rightDetails[$k][0]);
            $sheet->getStyle($rightStartCol . $rowIdx)->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
            
            $nextRCol = $colLetters[min($colCount - 1, max(4, $midColIdx + 1))];
            if ($nextRCol !== $lastCol) {
                $sheet->mergeCells("{$nextRCol}{$rowIdx}:{$lastCol}{$rowIdx}");
            }
            $sheet->setCellValue($nextRCol . $rowIdx, $rightDetails[$k][1]);
            $sheet->getStyle($nextRCol . $rowIdx)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));
            $sheet->getStyle($nextRCol . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
            $sheet->getRowDimension($rowIdx)->setRowHeight(18);
            $rowIdx++;
        }

        $sheet->getRowDimension($rowIdx)->setRowHeight(10);
        $rowIdx++; // Compact Spacer

        // --- DATA TABLE SECTION ---
        // Header Row (11-12pt Bold, Dark Navy Blue Fill, Centered White Text)
        $headerRow = $rowIdx;
        foreach ($headers as $cIdx => $header) {
            $col = $cIdx < 26 ? $colLetters[$cIdx] : 'A' . $colLetters[$cIdx - 26];
            $cellRef = $col . $rowIdx;
            $sheet->setCellValue($cellRef, ucfirst(str_replace('_', ' ', (string) $header)));
            $sheet->getStyle($cellRef)->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($cellRef)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($navyColor);
            $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($cellRef)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF002244'));
        }
        $sheet->getRowDimension($rowIdx)->setRowHeight(26);
        
        // Freeze header row so column headers stay pinned on scroll!
        $sheet->freezePane('A' . ($headerRow + 1));
        $rowIdx++;

        // Data Rows (10pt Font, Zebra Striping)
        foreach ($rows as $rIdx => $row) {
            $bgColor = ($rIdx % 2 === 0) ? 'FFFFFFFF' : 'FFF4F6F9';
            foreach ($headers as $cIdx => $headerKey) {
                $col = $cIdx < 26 ? $colLetters[$cIdx] : 'A' . $colLetters[$cIdx - 26];
                $cellRef = $col . $rowIdx;
                $val = self::formatCellValue((string) $headerKey, $row[$headerKey] ?? '');
                $sheet->setCellValue($cellRef, $val);
                
                $sheet->getStyle($cellRef)->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
                $sheet->getStyle($cellRef)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                $sheet->getStyle($cellRef)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($lightBorderColor));
                $sheet->getStyle($cellRef)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                if (is_numeric($val) || strtolower($headerKey) === 'id' || str_contains(strtolower($headerKey), 'date') || str_contains(strtolower($headerKey), 'phone')) {
                    $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            }
            $sheet->getRowDimension($rowIdx)->setRowHeight(22);
            $rowIdx++;
        }

        // --- BOTTOM SUMMARY BAR ---
        $sheet->mergeCells("A{$rowIdx}:{$lastCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, "Total Records Exported: {$recordCount}    |    Status: Successfully Generated  ");
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($navyColor);
        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($rowIdx)->setRowHeight(24);
        $rowIdx++;

        $sheet->getRowDimension($rowIdx)->setRowHeight(10);
        $rowIdx++; // Spacer

        // --- FOOTER SECTION (At Very Bottom) ---
        $sheet->mergeCells("A{$rowIdx}:{$lastCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, "Generated automatically by PowerSurge Gym Management System.");
        $sheet->getStyle('A' . $rowIdx)->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $rowIdx++;

        $sheet->mergeCells("A{$rowIdx}:{$lastCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, "This report is confidential and intended for authorized personnel only.");
        $sheet->getStyle('A' . $rowIdx)->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));
        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $rowIdx++;

        $sheet->mergeCells("A{$rowIdx}:{$lastCol}{$rowIdx}");
        $sheet->setCellValue('A' . $rowIdx, "{$companyName}  •  Website: www.powersurgegym.com  •  Email: support@powersurgegym.com");
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));
        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Improved Proportional Column Width Calculation
        foreach ($headers as $cIdx => $headerKey) {
            $col = $cIdx < 26 ? $colLetters[$cIdx] : 'A' . $colLetters[$cIdx - 26];
            $keyLower = strtolower((string) $headerKey);
            $w = 18; // Default medium width
            if ($keyLower === 'id') $w = 10;
            elseif (str_contains($keyLower, 'name')) $w = 28;
            elseif (str_contains($keyLower, 'contact')) $w = 22;
            elseif (str_contains($keyLower, 'phone')) $w = 18;
            elseif (str_contains($keyLower, 'email')) $w = 30;
            elseif (str_contains($keyLower, 'address')) $w = 36;
            elseif (str_contains($keyLower, 'date') || str_contains($keyLower, 'at')) $w = 22;
            elseif (str_contains($keyLower, 'price') || str_contains($keyLower, 'total') || str_contains($keyLower, 'amount')) $w = 18;

            // Ensure column width accommodates maximum data string length
            $maxLen = strlen((string) ucfirst(str_replace('_', ' ', (string) $headerKey)));
            foreach ($rows as $r) {
                $cellVal = self::formatCellValue((string) $headerKey, $r[$headerKey] ?? '');
                $maxLen = max($maxLen, strlen($cellVal));
            }
            $calcWidth = max($w, min(45, $maxLen + 4));

            $sheet->getColumnDimension($col)->setAutoSize(false);
            $sheet->getColumnDimension($col)->setWidth($calcWidth);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private static function filename(string $title, string $format): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/i', '_', $title), '_');
        return $base . '_' . date('Y-m-d_H-i') . '.' . $format;
    }

    private static function sanitizeFilename(string $input, string $format): string
    {
        $input = trim(preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $input));
        if (!str_ends_with(strtolower($input), '.' . $format)) {
            $input .= '.' . $format;
        }
        return $input;
    }

    private static function scalar(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string) $value : json_encode($value);
    }

    /** @param array<int, array<string, mixed>> $rows @param array<int, string> $headers */
    private static function values(array $rows, array $headers): array
    {
        return array_map(static fn ($row) => array_map(static fn ($key) => self::scalar($row[$key] ?? ''), $headers), $rows);
    }
}
