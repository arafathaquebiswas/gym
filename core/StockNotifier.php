<?php

/** Fires "back in stock" emails to everyone who subscribed while a product was out of stock. */
final class StockNotifier
{
    public static function notifyBackInStock(array $product): int
    {
        $notificationModel = new StockNotification();
        $pending = $notificationModel->pendingForProduct((int) $product['id']);
        if (!$pending) {
            return 0;
        }

        $gymName = (new Setting())->get('gym_name', 'PowerSurge Gym');
        $productUrl = url('/store/' . $product['slug']);

        $body = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#222;">
            <h2 style="color:#ff6a1a;">' . e($gymName) . '</h2>
            <p>Good news — <strong>' . e($product['name']) . '</strong> is back in stock!</p>
            <p style="margin-top:24px;">
                <a href="' . e($productUrl) . '" style="background:#ff6a1a;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">Shop Now</a>
            </p>
        </div>';

        $sent = 0;
        foreach ($pending as $sub) {
            if (Mailer::send($sub['email'], $sub['email'], $product['name'] . ' is back in stock!', $body)) {
                $sent++;
            }
        }

        $notificationModel->markNotified(array_column($pending, 'id'));
        return $sent;
    }
}
