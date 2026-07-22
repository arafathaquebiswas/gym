<?php

final class TrainerGallery extends Model
{
    public function forTrainer(int $trainerId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM trainer_gallery WHERE trainer_id = :trainer_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['trainer_id' => $trainerId]);
        return $stmt->fetchAll();
    }

    public function add(int $trainerId, string $imagePath): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO trainer_gallery (trainer_id, image_path, sort_order, created_at) VALUES (:trainer_id, :image_path, 0, NOW())'
        );
        $stmt->execute(['trainer_id' => $trainerId, 'image_path' => $imagePath]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $imageId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM trainer_gallery WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $imageId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $imageId): void
    {
        $stmt = $this->db->prepare('DELETE FROM trainer_gallery WHERE id = :id');
        $stmt->execute(['id' => $imageId]);
    }
}
