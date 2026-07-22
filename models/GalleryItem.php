<?php

final class GalleryItem extends Model
{
    public function all(?string $category = null): array
    {
        if ($category) {
            $stmt = $this->db->prepare('SELECT * FROM gallery WHERE category = :category ORDER BY created_at DESC');
            $stmt->execute(['category' => $category]);
        } else {
            $stmt = $this->db->query('SELECT * FROM gallery ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 8): array
    {
        $stmt = $this->db->prepare('SELECT * FROM gallery ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
