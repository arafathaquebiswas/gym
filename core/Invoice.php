<?php

/** Lays out a POS sale or an online order as a printable PDF invoice on top of SimplePdf. */
final class Invoice
{
    public static function generate(array $sale, array $items): string
    {
        $pdf = new SimplePdf();
        $pdf->addPage();
        $gymName = self::gymName();
        $y = self::drawHeader($pdf, 'INVOICE #' . $sale['invoice_no'], $sale['sale_date']);

        if (!empty($sale['member_name'])) {
            $pdf->text(50, $y, 'Member: ' . $sale['member_name'], 10);
            $y += 14;
        }
        $pdf->text(50, $y, 'Served by: ' . ($sale['sold_by_name'] ?? '—'), 10);
        $y += 20;

        $y = self::drawItemsTable($pdf, $y, $items);
        $y = self::drawTotals($pdf, $y, $sale);

        $pdf->text(50, $y, 'Thank you for shopping at ' . $gymName . '!', 10);
        return $pdf->output();
    }

    public static function generateForOrder(array $order, array $items): string
    {
        $pdf = new SimplePdf();
        $pdf->addPage();
        $gymName = self::gymName();
        $y = self::drawHeader($pdf, 'ORDER #' . $order['order_no'], $order['created_at']);

        $customerName = $order['account_name'] ?? $order['guest_name'] ?? '—';
        $pdf->text(50, $y, 'Customer: ' . $customerName, 10);
        $y += 14;
        $pdf->text(50, $y, ($order['fulfillment_method'] === 'pickup' ? 'Pickup at: ' : 'Delivery Address: ') . order_delivery_label($order), 10);
        $y += 14;
        if (!empty($order['time_slot_label'])) {
            $pdf->text(50, $y, 'Preferred Time: ' . $order['time_slot_label'], 10);
            $y += 14;
        }
        if ($order['fulfillment_method'] === 'pickup' && !empty($order['pickup_pin'])) {
            $pdf->text(50, $y, 'Pickup PIN: ' . $order['pickup_pin'], 10);
            $y += 14;
        }
        $pdf->text(50, $y, 'Order Status: ' . ucfirst(str_replace('_', ' ', $order['status'])), 10);
        $y += 20;

        $y = self::drawItemsTable($pdf, $y, $items);
        $y = self::drawTotals($pdf, $y, $order);

        $pdf->text(50, $y, 'Thank you for shopping at ' . $gymName . '!', 10);
        return $pdf->output();
    }

    private static function gymName(): string
    {
        return (new Setting())->get('gym_name', 'PowerSurge Gym');
    }

    private static function drawHeader(SimplePdf $pdf, string $title, string $dateValue): float
    {
        $settings = new Setting();
        $gymName = self::gymName();
        $gymPhone = $settings->get('gym_phone', '01904-485009');
        $gymAddress = $settings->get('gym_address', '');

        $left = 50;
        $y = 50;

        $pdf->text($left, $y, $gymName, 18);
        $y += 22;
        if ($gymAddress !== '') {
            $pdf->text($left, $y, $gymAddress, 9);
            $y += 14;
        }
        if ($gymPhone !== '') {
            $pdf->text($left, $y, 'Phone: ' . $gymPhone, 9);
            $y += 14;
        }

        $y += 12;
        $pdf->text($left, $y, $title, 12);
        $pdf->text(400, $y, format_date($dateValue, 'd M Y, h:i A'), 9);
        $y += 16;

        return $y;
    }

    private static function drawItemsTable(SimplePdf $pdf, float $y, array $items): float
    {
        $left = 50;
        $right = 545;

        $pdf->line($left, $y, $right, $y);
        $y += 16;
        $pdf->text($left, $y, 'Item', 10);
        $pdf->text(300, $y, 'Qty', 10);
        $pdf->text(360, $y, 'Unit Price', 10);
        $pdf->text(460, $y, 'Subtotal', 10);
        $y += 6;
        $pdf->line($left, $y, $right, $y);
        $y += 16;

        foreach ($items as $item) {
            $pdf->text($left, $y, (string) $item['product_name'], 10);
            $pdf->text(300, $y, (string) $item['qty'], 10);
            $pdf->text(360, $y, money((float) $item['unit_price']), 10);
            $pdf->text(460, $y, money((float) $item['subtotal']), 10);
            $y += 18;
            if ($y > $pdf->pageHeight() - 150) {
                $pdf->addPage();
                $y = 50;
            }
        }

        $y += 6;
        $pdf->line($left, $y, $right, $y);
        $y += 20;

        return $y;
    }

    /** @param array{subtotal:float|string,discount:float|string,total:float|string,payment_method:string} $record */
    private static function drawTotals(SimplePdf $pdf, float $y, array $record): float
    {
        $pdf->text(360, $y, 'Subtotal:', 10);
        $pdf->text(460, $y, money((float) $record['subtotal']), 10);
        $y += 16;
        $pdf->text(360, $y, 'Discount:', 10);
        $pdf->text(460, $y, money((float) $record['discount']), 10);
        $y += 16;
        if (isset($record['shipping_charge'])) {
            $pdf->text(360, $y, 'Shipping:', 10);
            $pdf->text(460, $y, (float) $record['shipping_charge'] > 0 ? money((float) $record['shipping_charge']) : 'Free', 10);
            $y += 16;
        }
        if (isset($record['tax'])) {
            $pdf->text(360, $y, 'Tax:', 10);
            $pdf->text(460, $y, money((float) $record['tax']), 10);
            $y += 16;
        }
        $pdf->text(360, $y, 'Total:', 12);
        $pdf->text(460, $y, money((float) $record['total']), 12);
        $y += 20;
        $pdf->text(360, $y, 'Payment:', 10);
        $pdf->text(460, $y, strtoupper(str_replace('_', ' ', $record['payment_method'])), 10);
        $y += 30;

        return $y;
    }
}
