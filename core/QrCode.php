<?php

use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use chillerlan\QRCode\QROptions;

/**
 * Thin wrapper around chillerlan/php-qrcode so callers never touch the
 * underlying library directly. Encodes plain text (order number + PIN) —
 * no external API calls, generated entirely on-server. Renders as an SVG
 * data URI (default output) — used only in HTML contexts (web pages,
 * HTML email); the hand-rolled invoice PDF writer has no image-embedding
 * support, so the PIN is shown there as plain text instead.
 */
final class QrCode
{
    public static function dataUri(string $text): string
    {
        $options = new QROptions(['scale' => 6]);
        return (new ChillerlanQRCode($options))->render($text);
    }
}
