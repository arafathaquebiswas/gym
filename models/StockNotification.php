<?php

final class StockNotification extends Model
{
    /** Re-subscribing (e.g. after a previous restock cycle already notified this email) resets notified_at. */
    public function subscribe(int $productId, string $email): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO stock_notifications (product_id, email) VALUES (:product_id, :email)
             ON DUPLICATE KEY UPDATE notified_at = NULL'
        );
        $stmt->execute(['product_id' => $productId, 'email' => $email]);
    }

    public function pendingForProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM stock_notifications WHERE product_id = :product_id AND notified_at IS NULL'
        );
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    public function markNotified(array $ids): void
    {
        if (!$ids) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE stock_notifications SET notified_at = NOW() WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $ids));
    }
}
