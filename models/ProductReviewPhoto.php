<?php

final class ProductReviewPhoto extends Model
{
    public function forReview(int $reviewId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_review_photos WHERE review_id = :review_id ORDER BY id ASC');
        $stmt->execute(['review_id' => $reviewId]);
        return $stmt->fetchAll();
    }

    public function add(int $reviewId, string $imagePath): int
    {
        $stmt = $this->db->prepare('INSERT INTO product_review_photos (review_id, image_path) VALUES (:review_id, :image_path)');
        $stmt->execute(['review_id' => $reviewId, 'image_path' => $imagePath]);
        return (int) $this->db->lastInsertId();
    }
}
