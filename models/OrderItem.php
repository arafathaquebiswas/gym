<?php

final class OrderItem extends Model
{
    public function forOrder(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}
