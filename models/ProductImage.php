<?php

final class ProductImage extends Model
{
    public function forProduct(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    public function add(int $productId, string $imagePath): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:product_id, :image_path, 0)'
        );
        $stmt->execute(['product_id' => $productId, 'image_path' => $imagePath]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $imageId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_images WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $imageId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $imageId): void
    {
        $this->db->prepare('DELETE FROM product_images WHERE id = :id')->execute(['id' => $imageId]);
    }
}
