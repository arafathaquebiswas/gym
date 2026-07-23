<?php

final class VariantImage extends Model
{
    public function forVariant(int $variantId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM variant_images WHERE variant_id = :variant_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['variant_id' => $variantId]);
        return $stmt->fetchAll();
    }

    public function add(int $variantId, string $imagePath): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO variant_images (variant_id, image_path, sort_order) VALUES (:variant_id, :image_path, 0)'
        );
        $stmt->execute(['variant_id' => $variantId, 'image_path' => $imagePath]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $imageId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM variant_images WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $imageId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $imageId): void
    {
        $this->db->prepare('DELETE FROM variant_images WHERE id = :id')->execute(['id' => $imageId]);
    }
}
