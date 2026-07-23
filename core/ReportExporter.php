<?php

/** Streams any report's tabular data as a CSV (opens directly in Excel) or a simple PDF — shared by every report in the admin panel. */
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

    /** @param array<int, array<int, string>> $rows */
    public static function pdf(string $title, array $headers, array $rows, string $subtitle = ''): never
    {
        $pdf = new SimplePdf();
        $pdf->addPage();
        $left = 50;
        $right = 545;
        $y = 50;

        $pdf->text($left, $y, (new Setting())->get('gym_name', 'PowerSurge Gym'), 16);
        $y += 22;
        $pdf->text($left, $y, $title, 12);
        $pdf->text($right - 120, $y, format_date(date('Y-m-d'), 'd M Y'), 9);
        $y += 14;
        if ($subtitle !== '') {
            $pdf->text($left, $y, $subtitle, 9);
            $y += 14;
        }
        $y += 6;
        $pdf->line($left, $y, $right, $y);
        $y += 16;

        $colWidth = ($right - $left) / max(1, count($headers));
        foreach ($headers as $i => $header) {
            $pdf->text($left + $i * $colWidth, $y, $header, 9);
        }
        $y += 6;
        $pdf->line($left, $y, $right, $y);
        $y += 16;

        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $cell) {
                $pdf->text($left + $i * $colWidth, $y, (string) $cell, 9);
            }
            $y += 16;
            if ($y > $pdf->pageHeight() - 60) {
                $pdf->addPage();
                $y = 50;
            }
        }

        $content = $pdf->output();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . self::slug($title) . '.pdf"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    private static function slug(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-');
    }
}
