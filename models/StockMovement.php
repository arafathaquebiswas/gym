<?php

final class StockMovement extends Model
{
    public function record(int $productId, int $changeQty, string $type, ?int $referenceId = null, ?string $note = null, ?int $createdBy = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO stock_movements (product_id, change_qty, type, reference_id, note, created_by)
             VALUES (:product_id, :change_qty, :type, :reference_id, :note, :created_by)'
        );
        $stmt->execute([
            'product_id' => $productId,
            'change_qty' => $changeQty,
            'type' => $type,
            'reference_id' => $referenceId,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }

    public function forProduct(int $productId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, u.name AS created_by_name FROM stock_movements m
             LEFT JOIN users u ON u.id = m.created_by
             WHERE m.product_id = :product_id
             ORDER BY m.created_at DESC, m.id DESC LIMIT :limit'
        );
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
