<?php

/** Builds and sends the order-confirmation email — the customer-facing counterpart to Invoice. */
final class OrderMailer
{
    public static function sendConfirmation(array $order, array $items): bool
    {
        $settings = new Setting();
        $gymName = $settings->get('gym_name', 'PowerSurge Gym');
        $gymPhone = $settings->get('gym_phone', '01904-485009');
        $gymEmail = $settings->get('gym_email', '');

        $toEmail = $order['account_email'] ?? $order['guest_email'] ?? null;
        $toName = $order['account_name'] ?? $order['guest_name'] ?? 'Customer';
        if (!$toEmail) {
            return false;
        }

        $trackingLink = url('/track-order');

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td style="padding:8px;border-bottom:1px solid #333;">' . e($item['product_name']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #333;text-align:center;">' . (int) $item['qty'] . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #333;text-align:right;">' . money((float) $item['subtotal']) . '</td>'
                . '</tr>';
        }

        $body = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#222;">
            <h2 style="color:#ff6a1a;">' . e($gymName) . '</h2>
            <p>Hi ' . e($toName) . ',</p>
            <p>Thanks for your order! Here\'s a summary of <strong>#' . e($order['order_no']) . '</strong>:</p>
            <table style="width:100%;border-collapse:collapse;margin:16px 0;">
                <thead>
                    <tr style="background:#f4f4f4;">
                        <th style="padding:8px;text-align:left;">Item</th>
                        <th style="padding:8px;text-align:center;">Qty</th>
                        <th style="padding:8px;text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p style="text-align:right;margin:4px 0;">Subtotal: ' . money((float) $order['subtotal']) . '</p>
            <p style="text-align:right;margin:4px 0;">Discount: ' . money((float) $order['discount']) . '</p>
            <p style="text-align:right;margin:4px 0;">Shipping: ' . ((float) $order['shipping_charge'] > 0 ? money((float) $order['shipping_charge']) : 'Free') . '</p>
            <p style="text-align:right;margin:4px 0;">Tax: ' . money((float) $order['tax']) . '</p>
            <p style="text-align:right;margin:4px 0;font-size:18px;font-weight:bold;color:#ff6a1a;">Total: ' . money((float) $order['total']) . '</p>
            <p>Payment Method: <strong>' . e(strtoupper(str_replace('_', ' ', $order['payment_method']))) . '</strong></p>
            <p>' . ($order['fulfillment_method'] === 'pickup' ? 'Pickup at' : 'Delivering to') . ': ' . e(order_delivery_label($order)) . '</p>
            <p style="margin-top:24px;">
                <a href="' . e($trackingLink) . '" style="background:#ff6a1a;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">Track Your Order</a>
            </p>
            <hr style="margin:24px 0;border:none;border-top:1px solid #ddd;">
            <p style="font-size:13px;color:#666;">
                ' . e($gymName) . '<br>
                Phone: ' . e($gymPhone) . ($gymEmail ? '<br>Email: ' . e($gymEmail) : '') . '
            </p>
        </div>';

        $pdf = Invoice::generateForOrder($order, $items);

        return Mailer::send($toEmail, $toName, 'Order Confirmation — ' . $order['order_no'], $body, [
            ['content' => $pdf, 'filename' => $order['order_no'] . '.pdf'],
        ]);
    }
}
