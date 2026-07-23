<?php

/**
 * Minimal single-file PDF writer for plain text/line invoices — no external
 * dependency, no composer package. Supports exactly what a receipt needs:
 * positioned text (top-left origin, like a normal page layout) and straight
 * lines, using the built-in PDF "Helvetica" font (no font embedding needed).
 */
final class SimplePdf
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;

    private array $pages = [];
    private string $currentContent = '';
    private float $fontSize = 10;

    public function addPage(): void
    {
        if ($this->currentContent !== '') {
            $this->pages[] = $this->currentContent;
        }
        $this->currentContent = '';
    }

    public function setFont(float $size): void
    {
        $this->fontSize = $size;
    }

    /** $x/$y are measured from the top-left of the page, in points. */
    public function text(float $x, float $y, string $text, ?float $size = null): void
    {
        $size ??= $this->fontSize;
        $escaped = $this->escape($text);
        $yFromBottom = self::PAGE_HEIGHT - $y;
        $this->currentContent .= "BT /F1 {$size} Tf {$x} {$yFromBottom} Td ({$escaped}) Tj ET\n";
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.5): void
    {
        $y1b = self::PAGE_HEIGHT - $y1;
        $y2b = self::PAGE_HEIGHT - $y2;
        $this->currentContent .= "{$width} w {$x1} {$y1b} m {$x2} {$y2b} l S\n";
    }

    public function pageHeight(): float
    {
        return self::PAGE_HEIGHT;
    }

    public function pageWidth(): float
    {
        return self::PAGE_WIDTH;
    }

    public function output(): string
    {
        if ($this->currentContent !== '') {
            $this->pages[] = $this->currentContent;
            $this->currentContent = '';
        }
        if (!$this->pages) {
            $this->pages[] = '';
        }

        $fontObjNum = 3;
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            $fontObjNum => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $nextObjNum = 4;
        $kids = [];
        foreach ($this->pages as $content) {
            $pageNum = $nextObjNum++;
            $contentNum = $nextObjNum++;
            $kids[] = "$pageNum 0 R";
            $objects[$pageNum] = '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 ' . $fontObjNum . ' 0 R >> >> '
                . '/MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Contents ' . $contentNum . ' 0 R >>';
            $objects[$contentNum] = ['stream' => $content];
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($this->pages) . ' >>';

        ksort($objects);
        $maxObjNum = max(array_keys($objects));

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        for ($num = 1; $num <= $maxObjNum; $num++) {
            if (!isset($objects[$num])) {
                continue;
            }
            $offsets[$num] = strlen($pdf);
            $body = $objects[$num];
            if (is_array($body)) {
                $stream = $body['stream'];
                $pdf .= "$num 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream\nendobj\n";
            } else {
                $pdf .= "$num 0 obj\n$body\nendobj\n";
            }
        }

        $xrefOffset = strlen($pdf);
        $count = $maxObjNum + 1;
        $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
        for ($num = 1; $num <= $maxObjNum; $num++) {
            $pdf .= isset($offsets[$num]) ? sprintf("%010d 00000 n \n", $offsets[$num]) : "0000000000 00000 f \n";
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
