<?php

/**
 * Pure-PHP Code128 barcode generator.
 *
 * Renders a Code128-B encoded barcode as an inline SVG string.
 * No external library, no raster image, no internet call required.
 * The SVG is safe to embed in HTML <img src="..."> (data URI) or
 * directly in page markup — both survive browser Print and Save-as-PDF.
 *
 * Usage:
 *   echo Barcode::svg('PS-20260802-0001');
 *   // or as a data URI for <img> tags:
 *   echo '<img src="' . Barcode::dataUri('PS-20260802-0001') . '">';
 */
final class Barcode
{
    // Code128-B encoding table: index = ASCII code - 32
    private const CODE128B_PATTERNS = [
        '11011001100','11001101100','11001100110','10010011000','10010001100',
        '10001001100','10011001000','10011000100','10001100100','11001001000',
        '11001000100','11000100100','10110011100','10011011100','10011001110',
        '10111001100','10011101100','10011100110','11001110010','11001011100',
        '11001001110','11011100100','11001110100','11101101110','11101001100',
        '11100101100','11100100110','11101100100','11100110100','11100110010',
        '11011011000','11011000110','11000110110','10100011000','10001011000',
        '10001000110','10110001000','10001101000','10001100010','11010001000',
        '11000101000','11000100010','10110111000','10110001110','10001101110',
        '10111011000','10111000110','10001110110','11101110110','11010001110',
        '11000101110','11011101000','11011100010','11011101110','11101011000',
        '11101000110','11100010110','11101101000','11101100010','11100011010',
        '11101111010','11001000010','11110001010','10100110000','10100001100',
        '10010110000','10010000110','10000101100','10000100110','10110010000',
        '10110000100','10011010000','10011000010','10000110100','10000110010',
        '11000010010','11001010000','11110111010','11000010100','10001111010',
        '10100111100','10010111100','10010011110','10111100100','10011110100',
        '10011110010','11110100100','11110010100','11110010010','11011011110',
        '11011110110','11110110110','10101111000','10100011110','10001011110',
        '10111101000','10111100010','11110101000','11110100010','10111011110',
        '10111101110','11101011110','11110101110','11010000100','11010010000',
        '11010011100','1100011101011',
    ];

    private const START_B   = 104;
    private const CODE_STOP = 106;

    /**
     * Generates an inline SVG Code128-B barcode for $text.
     *
     * @param string $text        The string to encode.
     * @param int    $barHeight   Bar height in pixels.
     * @param int    $barWidth    Width multiplier (narrow bar = 1 unit).
     * @param int    $quietZone   Quiet-zone width in units on each side.
     */
    public static function svg(
        string $text,
        int $barHeight = 60,
        int $barWidth  = 2,
        int $quietZone = 10
    ): string {
        $codes = self::encode($text);

        // Build bar pattern string
        $bars  = '';
        foreach ($codes as $code) {
            $bars .= self::CODE128B_PATTERNS[$code] ?? '';
        }
        // Termination bar
        $bars .= '11';

        // Convert pattern to SVG rects
        $x      = $quietZone * $barWidth;
        $rects  = '';
        $len    = strlen($bars);
        for ($i = 0; $i < $len; $i++) {
            if ($bars[$i] === '1') {
                $run = 1;
                while ($i + $run < $len && $bars[$i + $run] === '1') {
                    $run++;
                }
                $rects .= sprintf(
                    '<rect x="%d" y="0" width="%d" height="%d"/>',
                    $x,
                    $run * $barWidth,
                    $barHeight
                );
                $i += $run - 1;
            }
            $x += $barWidth;
        }

        $totalWidth = (strlen($bars) + 2 * $quietZone) * $barWidth;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="Barcode">'
            . '<rect width="%d" height="%d" fill="white"/>'
            . '<g fill="black">%s</g>'
            . '</svg>',
            $totalWidth, $barHeight,
            $totalWidth, $barHeight,
            $totalWidth, $barHeight,
            $rects
        );
    }

    /**
     * Returns a Base64-encoded data URI for an SVG barcode.
     * Safe for use in <img src="...">, even inside PDFs.
     */
    public static function dataUri(string $text, int $barHeight = 60, int $barWidth = 2): string
    {
        $svg = self::svg($text, $barHeight, $barWidth);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** Encode $text as a sequence of Code128 code indices (B set). */
    private static function encode(string $text): array
    {
        $codes    = [self::START_B];
        $checksum = self::START_B;
        $pos      = 1;

        for ($i = 0, $len = strlen($text); $i < $len; $i++) {
            $ord  = ord($text[$i]);
            $code = $ord - 32;          // Code128-B: ASCII 32-127
            if ($code < 0 || $code > 95) {
                $code = 0;              // Replace unsupported chars with space
            }
            $codes[]   = $code;
            $checksum += $code * $pos;
            $pos++;
        }

        $codes[] = $checksum % 103;     // Check character
        $codes[] = self::CODE_STOP;

        return $codes;
    }
}
