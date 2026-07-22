<?php

final class Testimonial extends Model
{
    public function approved(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY is_featured DESC, created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
