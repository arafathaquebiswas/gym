<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/** Lays out a POS sale or an online order as a printable PDF invoice using Dompdf. */
final class Invoice
{
    public static function generate(array $sale, array $items): string
    {
        $html = self::renderView('pos/receipt', [
            'sale' => $sale,
            'items' => $items,
            'isPdf' => true,
        ]);

        return self::htmlToPdf($html);
    }

    public static function generateForOrder(array $order, array $items): string
    {
        $html = self::renderView('orders/receipt', [
            'order' => $order,
            'items' => $items,
            'isPdf' => true,
        ]);

        return self::htmlToPdf($html);
    }

    private static function renderView(string $view, array $data): string
    {
        extract($data);
        ob_start();
        require __DIR__ . '/../views/admin/' . $view . '.php';
        return ob_get_clean();
    }

    private static function htmlToPdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);

        $document = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page { size: A4 portrait; margin: 10mm; } body { font-family: sans-serif; background: #ffffff; color: #111827; margin: 0; padding: 0; } .no-print { display: none !important; }</style></head><body>' . $html . '</body></html>';

        $dompdf->loadHtml($document);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
