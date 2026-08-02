<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExportService
{
    /** @param array<int, array<string, mixed>> $rows */
    public static function download(string $module, string $format, array $rows, array $filters): never
    {
        $title = Modules::label($module);
        $filename = self::filename($title, $format);
        self::audit($module, $format, $filename, count($rows), $filters);

        $headers = $rows ? array_keys($rows[0]) : ['No records'];
        if ($format === 'xlsx') {
            self::xlsx($filename, $title, $headers, $rows);
        }
        if ($format === 'pdf') {
            ReportExporter::pdf($title, $headers, self::values($rows, $headers), self::filterSummary($filters));
        }
        self::csv($filename, $headers, $rows);
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

    /** @param array<int, string> $headers @param array<int, array<string, mixed>> $rows */
    private static function csv(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn ($value) => self::scalar($value), $row));
        }
        fclose($out);
        exit;
    }

    /** @param array<int, string> $headers @param array<int, array<string, mixed>> $rows */
    private static function xlsx(string $filename, string $title, array $headers, array $rows): never
    {
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $title) ?: 'Export', 0, 31));
        $sheet->fromArray($headers, null, 'A1');
        foreach ($rows as $index => $row) {
            $sheet->fromArray(array_map(static fn ($value) => self::scalar($value), $row), null, 'A' . ($index + 2));
        }
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        (new Xlsx($sheet->getParent()))->save('php://output');
        exit;
    }

    private static function filename(string $title, string $format): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-');
        return strtolower($base) . '-' . date('Ymd-His') . '.' . $format;
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

    private static function filterSummary(array $filters): string
    {
        $filters = array_filter($filters, static fn ($value) => $value !== '' && $value !== null && $value !== []);
        return $filters ? 'Filters: ' . json_encode($filters, JSON_UNESCAPED_SLASHES) : '';
    }
}
