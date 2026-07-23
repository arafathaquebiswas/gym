<?php

/** Emails the gym when a product's stock crosses from above its minimum down to at-or-below it — fires once per crossing, not on every sale while it stays low. */
final class LowStockAlerter
{
    public static function checkAndNotify(array $productBefore, int $newStockQty): void
    {
        $settingModel = new Setting();
        if (!$settingModel->getBool('auto_low_stock_alerts', false)) {
            return;
        }

        $minStock = (int) $productBefore['min_stock'];
        $crossedDown = (int) $productBefore['stock_qty'] > $minStock && $newStockQty <= $minStock;
        if (!$crossedDown) {
            return;
        }

        $gymEmail = $settingModel->get('gym_email');
        if (!$gymEmail) {
            return;
        }

        $gymName = $settingModel->get('gym_name', 'PowerSurge Gym');
        $productUrl = url('/admin/products');

        $body = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#222;">
            <h2 style="color:#ff6a1a;">' . e($gymName) . ' — Low Stock Alert</h2>
            <p><strong>' . e($productBefore['name']) . '</strong> has dropped to <strong>' . (int) $newStockQty . '</strong> unit(s) in stock (minimum: ' . $minStock . ').</p>
            <p style="margin-top:20px;">
                <a href="' . e($productUrl) . '" style="background:#ff6a1a;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">Restock Now</a>
            </p>
        </div>';

        Mailer::send($gymEmail, $gymName, 'Low Stock Alert: ' . $productBefore['name'], $body);
    }
}
