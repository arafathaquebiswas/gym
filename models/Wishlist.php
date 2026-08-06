<?php

final class Wishlist extends Model
{
    public function forUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT w.id AS wishlist_id, w.created_at AS added_at, p.*
             FROM wishlist w JOIN products p ON p.id = w.product_id
             WHERE w.user_id = :user_id ORDER BY w.id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function has(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function add(int $userId, int $productId): void
    {
        $stmt = $this->db->prepare(
            self::insertOrIgnoreInto() . ' wishlist (user_id, product_id) VALUES (:user_id, :product_id)'
        );
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    }

    public function remove(int $userId, int $productId): void
    {
        $stmt = $this->db->prepare('DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    }

    public function count(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
