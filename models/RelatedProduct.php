<?php

/** Admin-curated related products — supplements (never replaces) the automatic category/co-occurrence algorithms already on Product. */
final class RelatedProduct extends Model
{
    public function forProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.* FROM related_products r
             JOIN products p ON p.id = r.related_product_id
             WHERE r.product_id = :product_id AND p.status = 'published'
             ORDER BY r.sort_order ASC"
        );
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /** @param array<int> $relatedProductIds */
    public function setForProduct(int $productId, array $relatedProductIds): void
    {
        $this->db->prepare('DELETE FROM related_products WHERE product_id = :product_id')->execute(['product_id' => $productId]);

        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO related_products (product_id, related_product_id, sort_order) VALUES (:product_id, :related_id, :sort_order)'
        );
        foreach (array_values($relatedProductIds) as $i => $relatedId) {
            $relatedId = (int) $relatedId;
            if ($relatedId <= 0 || $relatedId === $productId) {
                continue;
            }
            $stmt->execute(['product_id' => $productId, 'related_id' => $relatedId, 'sort_order' => $i]);
        }
    }
}
